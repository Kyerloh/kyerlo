<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Session;
use App\Start;
use App\User;
use App\Country;
use App\Carts;
use App\Package;
use App\Order;
use App\OrderProduct;
use DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderPlaced;
use App\Http\Requests\CheckoutRequest;
class CheckoutController extends Controller
{
    public function Checkout(Request $request){
        if (auth()->user() && request()->is('/guest-checkout')) {
            return redirect()->url('/checkout');
        }

    	$user_id = Auth::User()->id;
        $userDetails = User::find($user_id);
        //echo "<pre>";print_r($userDetails);die;
       
       if ($request->isMethod('post')) {
            $data = $request->all();
            //echo "<pre>";print_r($data);die;
 
            $user = User::find($user_id);
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->address = $data['address'];
            $user->country = $data['country'];
            $user->mobile = $data['mobile'];
            $user->save();

            return redirect()->back()->with('flash_message_success','Your Account Details have been Updated Succesfully');
       }
        $countries = Country::get();
        $details = Package::first();
        $session_id = Session::get('session_id'); 
        $projects = DB::table('starts')
                ->where('session_id', 'like', '%')
                ->first();

        $userCart = DB::table('carts')->where(['session_id'=>$session_id])->get();
         // Get Cart Total Amount
        $total_amount = 0;
        foreach($userCart as $item){
            $total_amount = $total_amount + ($item->price );
        }
        return view('services.checkout')->with(compact('details','projects','total_amount','countries','userDetails','userCart'));
    }

    public function guestCheckout(Request $request, $id=null){
         $userDetails = User::first();
        //echo "<pre>";print_r($userDetails);die;
       
       if ($request->isMethod('post')) {
            $data = $request->all();
            //echo "<pre>";print_r($data);die;
 
            $user = User::find($user_id);
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->address = $data['address'];
            $user->country = $data['country'];
            $user->mobile = $data['mobile'];
            $user->save();

            return redirect()->back()->with('flash_message_success','Your Account Details have been Updated Succesfully');
       }
        $countries = Country::get();
        $details = Package::first();
        //$projects = Start::where(['id'=>$id])->get();
        $session_id = Session::get('session_id'); 
        $projects = DB::table('starts')
                ->where('session_id', 'like', '%')
                ->first();
        $userCart = DB::table('carts')->where(['session_id'=>$session_id])->get();
        // Get Cart Total Amount
        $total_amount = 0;
        foreach($userCart as $item){
            $total_amount = $total_amount + ($item->price );
        }
        //echo "<pre>";print_r($projects);die;

        
        return view('services.checkout')->with(compact('details','projects','total_amount','countries','userDetails','userCart'));

    }
      //create a charge but for now its cash on delivery
    public function storePayment(Request $request){
        $data = $request->all();
        //echo "<pre>";print_r($data); die;
      $order = $this->addToOrdersTables($request, null);
      Mail::send(new OrderPlaced($order));

      return view('thankyou');
      
    }
      protected function addToOrdersTables($request, $error){
        //insert into orders table
          $session_id = Session::get('session_id'); 
        $items = DB::table('carts')->where(['session_id'=>$session_id])->get();

          foreach ($items as $item) {
          $order = Order::create([
            'user_id' => auth()->user() ? auth()->user()->id : null,
            'name' => $request->name,
             'email' => $request->email,
             'address' => $request->address,
             'country' => $request->country,
             'mobile' => $request->mobile,
             'total' =>$item->price,
             'status'=>0,
             'error'=> $error
          ]);
        }
          //insert into orderproducts table
          foreach ($items as $item) {
            OrderProduct::create([
              'order_id' => $order->id,
              'product_id' => $item->product_id,
              'designs' => $item->designs
            ]);
          }
            return $order;

          }
}