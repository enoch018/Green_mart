<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use App\Notifications\SendEmailNotification;
use Illuminate\Support\Facades\Notification;
use Barryvdh\DomPDF\Facade\Pdf as FacadePdf;


use  PDF;


class AdminController extends Controller
{
    public function view_category(){
          if(Auth::id()){
        $data = category::all();

        return view('admin.category',compact('data'));
     
    }else{
    return redirect('login')->with('error','You must login first');
    }
}

    public function add_category(Request $request){

        if(Auth::id()){
       $data = new category;
       $data->Category_name=$request->category;
       $data->save();
       return redirect()->back()->with('message', 'Data added Successfully!');
        }else{
    return redirect('login')->with('error','You must login first');
}
    }
    


    public function delete_category($id)
    {
        if(Auth::id()){

       $data = category::find($id);
       $data ->delete();
       return redirect()->back()->with('message','Data Deleted Successfully!');
    }
    else{
        return redirect('login')->with('error','You must login first');
    }
}
    public function view_product(){
        if(Auth::id()){

        $category = category::all();
       
        return view('admin.product', compact('category' ));
    }else{
        return redirect('login')->with('error','You must login first');
    }
    }
    public function add_product(Request $request){
        if(Auth::id()){
        $product = new product;
        $product->title = $request->title;
        $product->price = $request->price;
        $product->description = $request->description;
        $product->category = $request->category;
        $product->quantity = $request->quantity;
        $product->discount_price = $request->dis_price;
    
        if ($request->hasFile('image')) { // Check if a file was uploaded
            $image = $request->file('image');
            $imagename = time().'.'.$image->getClientOriginalExtension();
            
            // Create the directory if it doesn't exist
            $directory = public_path('product');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            
            $image->move($directory, $imagename); // Move file to public/product directory
            $product->image = $imagename;
        }
    
        $product->save();
        return redirect()->back();
    }else{
        return redirect('login')->with('error','You must login first');
    }
    }
    public function show_product(){

        if(Auth::id()){
        $product = product::all();
        return view('admin.show_product',compact('product'));
    }else{
    
        return redirect('login')->with('error','You must login first');
    }
}
    
    public function delete_product($id){

        if(Auth::id()){
        $product=product::find($id);
        $product->delete();
        return redirect()->back();
       
    }else{

        return redirect('login')->with('error','You must login first');
    }
    }


    public function update_product($id){
if(Auth::id()){
    $product =product::find($id);
    $category=category::all();
    return view('admin.update_product',compact('product','category'));

    }else{
        return redirect('login')->with('error','You must login first');
    }
}

    public function update_product_confirm(Request $request, $id){
        if(Auth::id()){
        $product = Product::find($id);
        $product->title = $request->product_title;
        $product->description = $request->description;
        $product->price = $request->price;
        $product->quantity = $request->quantity;
        $product->discount_price = $request->dis_price;
        $product->category = $request->category;
        
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time().'.'.$image->getClientOriginalExtension();
            $image->move('product', $imageName);
            $product->image = $imageName;
        }
      
        $product->save();
        return redirect()->back();
    }else{
        return redirect('login')->with('error','You must login first');
    }
    }
    public function order()
    {
        if(Auth::id()){
        $order=order::all();

 return view ('admin.order',compact('order')); 
    }else{
        return redirect('login')->with('error','You must login first');
    }
    }


    public function delivered($id){
if(Auth::id()){
        $order=order::find($id);
        $order->delivery_status ="delivered";
        $order->payment_status ="paid";
        $order->save();
        return redirect()->back();

    }else{
        return redirect('login')->with('error','You must login first');
    }
    }

    public function print_pdf($id){
        if(Auth::id()){
      $order =order::find($id);
    
    $pdf = FacadePdf::loadView('admin.print_pdf',compact('order'));

 
    return $pdf->download('order_details.pdf');
    }else{
        return redirect('login')->with('error','You must login first');
    }
    }
    public function send_email($id){
        if(Auth::id()){
        $order = Order::find($id);
        
        if (!$order) {
            // Order not found, handle this scenario gracefully
            return redirect()->back()->with('error', 'Order not found.');
        }
    
        return view('admin.email_info', compact('order'));
    }else{
        return redirect('login')->with('error','You must login first');
    }
}

    public function send_user_email(Request $request, $id){
        if(Auth::id()){
        $order=order::find($id);
        $details = [
            'email_greeting' => $request->email_greeting,
            'email_firstline' => $request->email_firstline,
            'email_body' => $request->email_body,
            'email_button' => $request->email_button,
            'email_url' => $request->email_url,
            'email_lastline' => $request->email_lastline,
            'any_questions' => $request->any_questions,
        ];
    
        // Send the notification to the $order instance
        Notification::send($order, new SendEmailNotification($details));
    
        // Optionally, return a response to indicate success or failure
        return redirect()->back();
}else{
    return redirect('login')->with('error','You must login first');
}
    }
public function searchdata(Request $request) {
    // Retrieve the search query from the request
    if(Auth::id()){

    $searchText = $request->input('Something');

    // Perform the search query using the retrieved search text
    $order = Order::where('name', 'LIKE', "%$searchText%")
                  ->orWhere('phone', 'LIKE', "%$searchText%")->orWhere('product_title', 'LIKE', "%$searchText%")
                  ->get();

    // Pass the search results to the view
    return view('admin.order', compact('order'));
}else{
    return redirect('login')->with('error','You must login first');
}
}
}