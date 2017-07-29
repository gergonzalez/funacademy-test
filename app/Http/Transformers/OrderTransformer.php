<?php
/**
 * Transform Order Data.
 *
 * @author     German Gonzalez Rodriguez <ger@gergonzalez.com>
 * @copyright  German Gonzalez Rodriguez
 *
 * @version    1.0
 */
namespace App\Http\Transformers;

use League\Fractal\TransformerAbstract;
use App\Order;

class OrderTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include.
     *
     * @var array
     */
    protected $availableIncludes = [
    ];

    /**
     * List of resources included.
     *
     * @var array
     */
    protected $defaultIncludes = [
        'user','product'
    ];

    /**
     * Turn Order object into a generic array.
     *
     * @return array
     */
    public function transform(Order $order)
    {
        return [
            'id' => $order->id,
            'quantity' => $order->quantity,
            'amount' => round($order->amount,2),
            'status' => $order->status,
            'createdAt' => $order->created_at->toDayDateTimeString(),
        ];
    }

    /**
     * Include User.
     *
     * @return League\Fractal\ItemResource
     */
    public function includeUser(Order $order)
    {
        return $this->item($order->user, new UserTransformer());
    }

    /**
     * Include Product.
     *
     * @return League\Fractal\ItemResource
     */
    public function includeProduct(Order $order)
    {
        return $this->item($order->product, new ProductTransformer());
    }

}
