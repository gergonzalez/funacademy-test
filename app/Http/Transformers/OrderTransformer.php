<?php
/**
 * Manage requests to 'admins*'.
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
    ];

    /**
     * Turn Order object into a generic array.
     *
     * @return array
     */
    public function transform(Order $order)
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
        ];
    }
}
