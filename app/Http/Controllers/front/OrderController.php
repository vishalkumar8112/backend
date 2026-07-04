<?php

namespace App\Http\Controllers\front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class OrderController extends Controller
{
    public function saveOrder(Request $request)
    {
        // Check cart
        if (empty($request->cart) || count($request->cart) == 0) {
            return response()->json([
                'status' => 400,
                'message' => 'Your cart is empty.',
            ], 400);
        }

        // Create Order
        $order = new Order();

        $order->name = $request->name;
        $order->email = $request->email;
        $order->address = $request->address;
        $order->mobile = $request->mobile;
        $order->state = $request->state;
        $order->zip = $request->zip;
        $order->city = $request->city;

        $order->grand_total = $request->grand_total;
        $order->subtotal = $request->sub_total;
        $order->discount = $request->discount ?? 0;
        $order->shipping = $request->shipping;

        $order->payment_status = $request->payment_status ?? 'not paid';
        $order->payment_method = $request->payment_method;
        $order->status = $request->status ?? 'pending';

        $order->user_id = $request->user()->id;

        $order->save();

        // Save Order Items
        foreach ($request->cart as $item) {

            $orderItem = new OrderItem();

            $orderItem->order_id = $order->id;
            $orderItem->product_id = $item['product_id'];

            $orderItem->name =
                $item['name'] ??
                ($item['title'] ?? 'Product');

            $orderItem->size = $item['size'] ?? null;
            $orderItem->unit_price = $item['price'];
            $orderItem->qty = $item['qty'];
            $orderItem->price = $item['price'] * $item['qty'];

            $orderItem->save();
        }

        return response()->json([
            'status' => 200,
            'id' => $order->id,
            'message' => 'You have successfully placed your order.',
        ], 200);
    }

    public function createPaymentIntent(Request $request) {
        try {
            if ($request->amount > 0) {
                Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

                $paymentIntent = PaymentIntent::create([
                    // Frontend already sends the amount in the smallest
                    // currency unit (paise) i.e. grandTotal() * 100,
                    // so we must NOT multiply by 100 again here.
                    'amount' => (int) $request->amount,
                    'currency' => 'inr',
                    'payment_method_types' => ['card'],
                ]);

                $clientSecret = $paymentIntent->client_secret;

                return response()->json([
                    'status' => 200,
                    // camelCase key so it matches `result.clientSecret`
                    // on the frontend.
                    'clientSecret' => $clientSecret,
                ], 200);

            } else {
                return response()->json([
                    'status' => 400,
                    'message' => 'Amount must be greater than 0.',
                ], 400);
            }


        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Unable to create payment intent.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}