<?php

namespace App\Models;

use Morilog\Jalali\Jalalian;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserWallet extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @param string
     */
    protected $table = 'user_wallet';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'membership_id',
        'pending_balance',
        'available_balance',
        'withdraw_balance',
        'transactions',
        'expired_at', // memebership's expiration
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'user_id',
        'membership_id',
        'created_at',
        'updated_at',
    ];

    /**
     * The model's default values for attributes.
     *
     * @param array
     */
    protected $attributes = [
        'pending_balance'   => 0,
        'available_balance' => 0,
        'withdraw_balance'  => 0,
        'transactions'      => '',
        'expired_at'        => null

        //* Transaction example when it has been filled *//
        // ['title' => '', 'price' => '', 'status' => '', 'refrence_id' => '', 'date' => '']
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'transactions'  => AsArrayObject::class
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
     * Get the user that owns the user's wallet record.
     */
    public function user() {
        return $this->belongsTo('App\Models\User');
    }

    /**
     * Get the membership that owns the user's wallet record.
     */
    public function core_membership() {
        return $this->belongsTo('App\Models\CoreMembership', 'membership_id');
    }
}
