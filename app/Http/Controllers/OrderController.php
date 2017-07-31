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
use App\Http\Transformers\OrderTransformer;
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
     * Display the list of all the orders stored.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'You are not allowed to perform this action'], 401);
        }

        return response()->json(app('fractal')->collection(Order::all(), new OrderTransformer())->getArray());
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
              'product' => 'required|exists:products,id',
              'quantity' => 'required|integer',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => ['message' => $e->getMessage(), 'data' => $e->getResponse()->original]], 422);
        }

        $product = Product::find($request->product);
        $quantity = $request->quantity;
        $amount = 0;

        $todayQuantity = $user->todayOrdersQuantity();

        if ($user->isAdmin()) {
            return response()->json(['error' => 'Admins can\'t buy'], 401);
        } elseif ($user->isWebsiteUser()) {
            if ($todayQuantity + $quantity > 10) {
                return response()->json(['error' => "As a website user you can't order more than 10 units. Today you already ordered $todayQuantity. Order not processed"], 401);
            }
            $amount = $quantity * $product->price;
        } elseif ($user->isProvider()) {
            if ($todayQuantity + $quantity > 50) {
                return response()->json(['error' => "As a provider you can't order more than 50 units. Today you already ordered $todayQuantity. Order not processed"], 401);
            }
            $provider = $user->userable;
            $amount = ($quantity * $product->price) * (1 - ($provider->discount / 100));
        } elseif ($user->isRetailer()) {
            $retailer = $user->userable;
            $nProviders = $retailer->acceptedProviders()->count();
            $maxUnits = 5 * $nProviders;

            if ($nProviders) {
                if ($todayQuantity + $quantity > $maxUnits) {
                    return response()->json(['error' => "As a retailer with providers you can't order more than $maxUnits units. Today you already ordered $todayQuantity. Order not processed"], 401);
                }

                $amount = $retailer->orderTotalAmount($product->price, $quantity, $todayQuantity);
            } else {
                if ($todayQuantity + $quantity > 30) {
                    return response()->json(['error' => "As a retailer without providers you can't order more than 30 units. Today you already ordered $todayQuantity. Order not processed"], 401);
                }
                $amount = $quantity * $product->price;
            }
        }

        $newOrder = Order::create([
          'user_id' => $user->id,
          'product_id' => $product->id,
          'quantity' => $request->quantity,
          'amount' => $amount,
          'status' => 'pending',
        ]);

        return response()->json(app('fractal')->item($newOrder, new OrderTransformer())->getArray());
    }

    /**
     * Update the status value in db.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $orderId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $orderId)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'You are not allowed to perform this action'], 401);
        }

        try {
            $this->validate($request, [
              'status' => 'required|in:pending,proccessing,complete,cancelled',
              'observations' => 'required_if:status,cancelled',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => ['message' => $e->getMessage(), 'data' => $e->getResponse()->original]], 422);
        }

        try {
            $order = Order::findOrFail($orderId);
            $order->status = $request->status;
            $order->observations = $request->input('observations', '');
            $order->save();

            if ($order->status === 'cancelled') {
                $subject = 'Order Cancelled';
                $text = "Your Order has been cancelled: $order->observations";
                $email = $order->user->email;

                app('mailer')->raw($text, function ($message) use ($subject, $email) {
                    $message->from('no-reply@gergonzalez.com')->to($email)->subject($subject);
                });
            }

            return response()->json(app('fractal')->item($order, new OrderTransformer())->getArray());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => ['message' => "No order with id $orderId"]], 400);
        }
    }

    /**
     * Delete the specified order.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $orderId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $orderId)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'You are not allowed to perform this action'], 401);
        }

        try {
            $order = Order::findOrFail($orderId);
            if ($order->delete()) {
                return response()->json(['data' => ['Order successfully deleted']]);
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => ['message' => "No order with id $orderId"]], 400);
        }
    }
}
