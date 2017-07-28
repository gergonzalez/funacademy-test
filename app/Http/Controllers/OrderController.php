<?php
/**
 * Manage requests to 'orders*'.
 *
 * @author     German Gonzalez Rodriguez <ger@gergonzalez.com>
 * @copyright  German Gonzalez Rodriguez
 *
 * @version    1.0
 */
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Product;
use App\Order;

class OrderController extends Controller
{
    /**
     * Create a new OrderController instance.
     */
    public function __construct()
    {
    }

    /**
     * Store a newly created Order in db.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user->active) {
            return response()->json(['error' => 'Your account it is not active. Contact the Admin.'], 401);
        }

        try {
            $this->validate($request, [
              'product_id' => 'required|exists:products,id',
              'quantity' => 'required|integer',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => ['message' => $e->getMessage(), 'data' => $e->getResponse()->original]], 422);
        }

        $product = Product::find($request->product_id);
        $quantity = $request->quantity;
        $amount = 0;

        $today_quantity = $user->todayOrdersQuantity();

        if ($user->isAdmin()) {
            return response()->json(['error' => 'Admins can\'t buy'], 401);
        } elseif ($user->isWebsiteUser()) {
            if ($today_quantity + $quantity > 10) {
                return response()->json(['error' => 'As a website user you can\'t order more than 10 units. Today you already ordered $today_quantity. Order not processed'], 401);
            }
            $amount = $quantity * $product->price;
        } elseif ($user->isProvider()) {
            if ($today_quantity + $quantity > 50) {
                return response()->json(['error' => 'As a provider you can\'t order more than 50 units. Today you already ordered $today_quantity. Order not processed'], 401);
            }
            $provider = $user->userable;
            $amount = ($quantity * $product->price) * (1 - ($provider->discount / 100));
        } elseif ($user->isRetailer()) {
            $retailer = $user->userable;
            $n_providers = $retailer->providers()->count();
            $max_units = 5 * $n_providers;

            if ($n_providers) {
                if ($today_quantity + $quantity > $max_units) {
                    return response()->json(['error' => "As a retailer with providers you can\'t order more than $max_units units. Today you already ordered $today_quantity. Order not processed"], 401);
                }

                $amount = $retailer->orderTotalAmount($product->price, $quantity, $today_quantity);
            } else {
                if ($today_quantity + $quantity > 30) {
                    return response()->json(['error' => 'As a retailer without providers you can\'t order more than 30 units. Today you already ordered $today_quantity. Order not processed'], 401);
                }
                $amount = $quantity * $product->price;
            }
        }

        $new_order = Order::create([
          'user_id' => $user->id,
          'product_id' => $product->id,
          'quantity' => $request->quantity,
          'amount' => $amount,
          'status' => 'pending',
        ]);

        return response()->json(['data' => $new_order]);
    }

    /**
     * Update the status value in db.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $order_id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $order_id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'You are not allowed to perform this action'], 401);
        }

        try {
            $this->validate($request, [
              'status' => 'required',
              'observations' => 'required_if:status,cancelled',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => ['message' => $e->getMessage(), 'data' => $e->getResponse()->original]], 422);
        }

        try {
            $order = Order::findOrFail($order_id);
            $order->status = $request->status;
            $order->observations = $request->input('observations', '');
            $order->save();

            if ($order->status === 'cancelled') {
                $subject = 'Order Cancelled';
                $text = "Your Order has been cancelled: $order->observations";
                $email = $order->user->email;

                app('mailer')->raw($text, function ($message) use ($subject, $email) {
                    $message->from('no-reply@gergonzalez.com')->to('ger@gergonzalez.com')->subject($subject);
                });
            }

            return response()->json(['data' => $order]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => ['message' => "No user exists for id $user_id"]], 400);
        }
    }

    /**
     * Delete the specified order.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $order_id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $order_id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'You are not allowed to perform this action'], 401);
        }

        try {
            $order = Order::findOrFail($order_id);
            if ($order->delete()) {
                return response()->json(['data' => ['Order successfully deleted']]);
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => ['message' => "No user exists for id $user_id"]], 400);
        }
    }
}