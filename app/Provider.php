<?php
/**
 * Provider Model.
 *
 * @author     German Gonzalez Rodriguez <ger@gergonzalez.com>
 * @copyright  German Gonzalez Rodriguez
 *
 * @version    1.0
 */
namespace App;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
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
        'company_name', 'phone', 'iban', 'company_description', 'created_at',
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
     * Get the user record polymorphically associated with the provider.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function user()
    {
        return $this->morphOne('App\User', 'userable');
    }

    /**
     * Get all the retailers associated with the provider 
     * with the accepted pivot.
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsToMany
     */
    public function retailers()
    {
        return $this->belongsToMany(Retailer::class)->withPivot('accepted');
    }
}
