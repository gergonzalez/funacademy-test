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
    public function orderTotalAmount($price, $quantity, $today_quantity)
    {

        // Slower
        // for ($n = $today_quantity; $n < ($today_quantity + $quantity); ++$n) {
        //     $discount = $discounts[  floor($n / 5) ];
        //     $amount += $product->price * (1 - ($discount / 100));
        // }

        $amount = 0;
        $discounts = $this->providers()->orderBy('discount', 'desc')->pluck('discount');

        $j = floor(($today_quantity + $quantity) / 5) - 1;
        $mod = ($today_quantity + $quantity) % 5;
        $mod_jj = ($today_quantity >= 5) ? $today_quantity % 5 : 5 - $today_quantity;

        if ($mod_jj) {
            $discount = $discounts[  floor($today_quantity / 5) ];
            $amount += ($mod_jj * $price) * (1 - ($discount / 100));
        }

        app('log')->info($quantity);
        app('log')->info($today_quantity);

        app('log')->info($mod_jj);
        app('log')->info($mod);
        app('log')->info($j);

        app('log')->info($amount);

        for ($n = 0; $n < $j; ++$n) {
            $discount = $discounts[ floor(($today_quantity + $quantity + ($n * 5)) / 5) - 1 ];
            $amount += (5 * $price) * (1 - ($discount / 100));
        }

        app('log')->info($amount);

        if ($mod) {
            $discount = $discounts[  floor(($today_quantity + $mod_jj + $j * 5 + $mod) / 5) ];
            $amount += ($mod * $price) * (1 - ($discount / 100));
        }

        app('log')->info($amount);

        return $amount;
    }
}
