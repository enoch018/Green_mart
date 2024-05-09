<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\Cart;
use App\Models\Order;
use Illuminate\Support\Facades\Session;
use App\Models\Comment;
use RealRashid\SweetAlert\Facades\Alert;
use App\Models\Reply;
use Stripe;

class HomeController extends Controller
{

    public function index()
    {
        $comments = Comment::orderBy('id', 'desc')->get();
        // Fetch comments from the database or wherever they are stored
    $product = Product::paginate(10);
    $reply= Reply::all();
    return view('home.userpage', compact('product', 'comments','reply')); 
    }

    public function redirect()
    {
        $usertype = Auth::user()->usertype;
        if ($usertype == '1') {
            $total_products = Product::all()->count();
            $total_orders = Order::all()->count();
            $total_customers = User::all()->count();
            $order = Order::all();
            $total_delivered = Order::where('delivery_status', '=', 'delivered')->count();
            $total_processing = Order::where('delivery_status', '=', 'processing')->count();
            $total_revenue = 0;
            foreach ($order as $order) {
                $total_revenue += $order->price;
            }
            return view('admin.home', compact('total_products', 'total_orders', 'total_customers', 'total_revenue', 'total_delivered', 'total_processing'));
        } else {
            $reply= Reply::all();
            $comments = Comment::orderBy('id', 'desc')->get();

            $product = Product::paginate(10);
            return view('home.userpage', compact('product', 'comments','reply'));
        }
    }

    public function product_details($id)
    {
        $product = Product::find($id);
        return view('home.product_details', compact('product'));
    }

    public function add_cart(Request $request, $id)
{
    if (Auth::check()) {
        $user = Auth::user();
        $product = Product::find($id);
        $userid = $user->id;
        $product_exists_id = Cart::where('product_id', '=', $id)->where('user_id', '=', $userid)->pluck('id')->first();

        if ($product_exists_id != null) {
            $cart = Cart::find($product_exists_id);
            $quantity = $cart->quantity;
            $new_quantity = $quantity + $request->quantity;
            $cart->quantity = $new_quantity;
            if ($product->discount_price != null) {
                $cart->price = $product->discount_price * $new_quantity;
            } else {
                $cart->price = $product->price * $new_quantity;
            }
            Alert::success('product Added Successfully','we have added product to the cart');
            $cart->save();
            return redirect()->back();
        } else {
            $cart = new Cart;
            $cart->name = $user->name;
            $cart->email = $user->email;
            $cart->phone = $user->phone;
            $cart->address = $user->address;
            $cart->user_id = $user->id;
            $cart->product_title = $product->title;
            $cart->product_id = $product->id;
            if ($product->discount_price != null) {
                $cart->price = $product->discount_price * $request->quantity;
            } else {
                $cart->price = $product->price * $request->quantity;
            }
            $cart->image = $product->image;
            $cart->quantity = $request->quantity;
            $cart->save();
            Alert::success('product Added Successfully','we have added product to the cart');
        }

        return redirect()->back();
    } else {
        return redirect('login')->with('error', 'You need to login first.');
    }
}


    public function show_cart()
    {
        if (Auth::id()) {
            $id = Auth::user()->id;
            $cart = Cart::where('user_id', '=', $id)->get();
            return view('home.showcart', compact('cart'));
        } else {
            return redirect('login');
        }
    }

    public function remove_cart($id)
    {
        $cart = Cart::find($id);
        $cart->delete();
        
        return redirect()->back();
    }

    public function cash_order()
    {
        $user = Auth::user();
        $userid = $user->id;
        $data = Cart::where('user_id', '=', $userid)->get();
        foreach ($data as $data) {
            $order = new Order;
            $order->name = $data->name;
            $order->price = $data->price;
            $order->phone = $data->phone;
            $order->address = $data->address;
            $order->email = $data->email;
            $order->product_id = $data->product_id;
            $order->product_title = $data->product_title;
            $order->quantity = $data->quantity;
            $order->image = $data->image;
            $order->payment_status = "cash on delivery";
            $order->delivery_status = "processing";
            $order->user_id = $data->user_id;
            $order->save();
            $cart_id = $data->id;
            $cart = Cart::find($cart_id);
            $cart->delete();
        }
        return redirect()->back()->with('message', 'We have received your order. We will connect with you soon..');
    }

    public function stripe($totalprice)
    {
        return view('home.stripe', compact('totalprice'));
    }

    public function stripePost(Request $request, $totalprice)
    {
        Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
        Stripe\Charge::create([
            "amount" =>   $totalprice * 100,
            "currency" => "usd",
            "source" => $request->stripeToken,
            "description" => "Test payment from itsolutionstuff.com."
        ]);
        $user = Auth::user();
        $userid = $user->id;
        $data = Cart::where('user_id', '=', $userid)->get();
        foreach ($data as $data) {
            $order = new Order;
            $order->name = $data->name;
            $order->price = $data->price;
            $order->phone = $data->phone;
            $order->address = $data->address;
            $order->email = $data->email;
            $order->product_id = $data->product_id;
            $order->quantity = $data->quantity;
            $order->image = $data->image;
            $order->status = 'not paid';
            $order->product_title = $data->product_title;
            $order->delivery_status = "processing";
            $order->user_id = $data->user_id;
            $order->save();
            $cart_id = $data->id;
            $cart = Cart::find($cart_id);
            $cart->delete();
        }
        Session::flash('success', 'Payment successful!');
        return back();
    }

    public function show_order()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $userid = $user->id;
            $orders = Order::where('user_id', $userid)->get();
            return view('home.order', compact('orders'));
        } else {
            return redirect('login');
        }
    }

    public function cancel_order($id)
    {
        $order = Order::find($id);
        $order->delivery_status = 'you canceled the order';
        $order->save();
        return redirect()->back();
    }

    public function add_comment(Request $request)
    {
        $request->validate([
            'comment' => 'required|string|min:1', // Ensure that the comment is not empty
        ]);
        if (Auth::id()) {
            $comment = new Comment;
            $comment->name = Auth::user()->name;
            $comment->user_id = Auth::user()->id;
            $comment->comment = $request->comment;
            $comment->save();
            return redirect()->back();
        } else {
            return redirect('login');
        }
    }

    public function add_reply(Request $request) {
        if (Auth::id()) {
            $reply = new reply;
            $reply->name = Auth::user()->name;
            $reply->user_id = Auth::user()->id;
    
            // Ensure the correct case for 'commentId'
            $reply->comment_id = $request->CommentId; // 'CommentId' should match the input name in the HTML
    
            $reply->reply = $request->reply_text; // 'reply_text' should match the textarea name in the HTML
    
            $reply->save();
            return redirect()->back();
        } else {
            return redirect("login");
        }
    }


    public function shop(){

     $product=Product::all();
      return view ('home.shop',compact('product')); 

    }


    public function search_products(Request $request)
    {
        $reply= Reply::all();
        $comments = Comment::orderBy('id', 'desc')->get();
        // Retrieve the search input from the request
        $search = $request->search;
    
        // Perform the search query
        $product = Product::where('title', 'LIKE', "%$search%")
                           ->orWhere('category', 'LIKE', "%$search%")
                           ->get();
    
        // Pass the search results to the view
        return view('home.shop', compact('product', 'comments','reply')); 
    }
    
    
}
