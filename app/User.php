<?php
/**
 * User Model.
 *
 * @author     German Gonzalez Rodriguez <ger@gergonzalez.com>
 * @copyright  German Gonzalez Rodriguez
 *
 * @version    1.0
 */
namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email', 'password', 'created_at', 'active',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'userable_id',
    ];

    /**
     * Get all of the owning userable models.
     *
     * @return \Illuminate\Database\Eloquent\Relations\morphTo
     */
    public function userable()
    {
        return $this->morphTo();
    }

    /**
     * Get all the orders associated with the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get all the orders registered today associated with the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function todayOrders()
    {
        $from = \Carbon\Carbon::today();
        $to = \Carbon\Carbon::today()->setTime(23, 59, 59);

        return $this->orders()->whereBetween('created_at', array($from, $to));
    }

    /**
     * Get the quantiy of the orders registered today.
     *
     * @return int
     */
    public function todayOrdersQuantity()
    {
        return $this->todayOrders()->sum('quantity');
    }

    /**
     * Check if user is admin.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->userable_type === 'App\Admin';
    }

    /**
     * Check if user is website user.
     *
     * @return bool
     */
    public function isWebsiteUser()
    {
        return $this->userable_type === 'App\WebsiteUser';
    }

    /**
     * Check if user is provider.
     *
     * @return bool
     */
    public function isProvider()
    {
        return $this->userable_type === 'App\Provider';
    }

    /**
     * Check if user is retailer.
     *
     * @return bool
     */
    public function isRetailer()
    {
        return $this->userable_type === 'App\Retailer';
    }
}
