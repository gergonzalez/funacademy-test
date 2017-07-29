<?php
/**
 * Retailer Model.
 *
 * @author     German Gonzalez Rodriguez <ger@gergonzalez.com>
 * @copyright  German Gonzalez Rodriguez
 *
 * @version    1.0
 */
namespace App;

use Illuminate\Database\Eloquent\Model;

class Retailer extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'responsible_name', 'phone', 'mobile', 'address', 'iban',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
      'id',
    ];

    /**
     * Get the user record polymorphically associated with the retailer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function user()
    {
        return $this->morphOne('App\User', 'userable');
    }

    /**
     * Get all the providers associated with the retailer
     * with the accepted pivot.
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsToMany
     */
    public function providers()
    {
        return $this->belongsToMany(Provider::class)->withPivot('accepted');
    }

    /**
     * Caculate the retailer order total amount.
     *
     * @return float
     */
    public function orderTotalAmount($price, $quantity, $todayQuantity)
    {

        $amount = 0;
        $discounts = $this->providers()->orderBy('discount', 'desc')->pluck('discount');

        for ($n = $todayQuantity; $n < ($todayQuantity + $quantity); ++$n) {
            $discount = $discounts[  floor($n / 5) ];
            $amount += $price * (1 - ($discount / 100));
        }

        return $amount;
    }
}
