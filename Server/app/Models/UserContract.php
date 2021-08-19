<?php

namespace App\Models;

use Morilog\Jalali\Jalalian;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserContract extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @param string
     */
    protected $table = 'user_contracts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'status',
        'meta',
        'expired_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['product_id', 'user_id'];

    /**
     * The model's default values for attributes.
     *
     * @param array
     */
    protected $attributes = [
        'status'    => 0, // default is 0, paid by customer: 1, shipment has been arrived and seller gives money: 2, has been cancelled: 3, has been expired: 4
        'meta'      => []

        //* Meta example when it has been filled *//
        // [
        //     'province'       => '',
        //     'city'           => '',
        //     'desc'           => '',
        //     'tyre_year'      => '',
        //     'count'          => '',
        //     'token'          => '',
        //     'shipment_day'   => 1, @int
        //     'cost'           => 0, @int
        //     'customer_id'    => 0, @int
        //     'proven_shipment'=> null, @string [file address]
        // ]  
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta'  => AsArrayObject::class
    ];

     /**
     * Convert Expired_at To Jalali Date
     * 
     * @param string value
     * @return string
     */
    public function getExpiredAtAttribute($value) {
        if(! is_null($value)) {
            return Jalalian::forge($value)->format('%d %B ماه %y');
        }
    }

    /**
     * Convert Expired_at To Jalali Date
     * 
     * @param string value
     * @return string
     */
    public function getCreatedAtAttribute($value) {
        if(! is_null($value)) {
            return Jalalian::forge($value)->format('%d %B ماه %y');
        }
    }

    /**
     * Convert Expired_at To Jalali Date
     * 
     * @param string value
     * @return string
     */
    public function getUpdatedAtAttribute($value) {
        if(! is_null($value)) {
            return Jalalian::forge($value)->format('%d %B ماه %y');
        }
    }

    /**
     * Get the products records associated with the contract records.
     */
    public function core_product() {
        return $this->belongsTo('App\Models\CoreProduct', 'product_id');
    }

    /**
     * Get the user that owns the user's contracts records.
     */
    public function user() {
        return $this->belongsTo('App\Models\User', 'user_id');
    }
}
