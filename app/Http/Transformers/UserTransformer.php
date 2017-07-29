<?php
/**
 * Transform User Data.
 *
 * @author     German Gonzalez Rodriguez <ger@gergonzalez.com>
 * @copyright  German Gonzalez Rodriguez
 *
 * @version    1.0
 */
namespace App\Http\Transformers;

use League\Fractal\TransformerAbstract;
use App\User;

class UserTransformer extends TransformerAbstract
{
    protected $availableIncludes = [
    ];

    protected $defaultIncludes = [
      'info',
    ];

    /**
     * Turn User object into a generic array.
     *
     * @return array
     */
    public function transform(User $user)
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'type' => $user->getType(),
            'activated' => $user->active,
            'createdAt' => $user->created_at->toDayDateTimeString(),
        ];
    }

    /**
     * Include User Type Data.
     *
     * @return League\Fractal\ItemResource
     */
    public function includeInfo(User $user)
    {
        if ($user->isAdmin()) {
            return $this->item($user->userable, new AdminTransformer());
        } elseif ($user->isWebsiteUser()) {
            return $this->item($user->userable, new WebsiteUserTransformer());
        } elseif ($user->isRetailer()) {
            return $this->item($user->userable, new RetailerTransformer());
        } elseif ($user->isProvider()) {
            return $this->item($user->userable, new ProviderTransformer());
        }
    }
}
