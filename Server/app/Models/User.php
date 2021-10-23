<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 ** User's migration is located in Auth module
 */

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'full_name',
        'tell',
        'password',
        'otp',
        'meta',
        'is_admin',
        'is_disabled'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'is_admin',
        'password',
        'otp',
        'created_at',
        'updated_at'
    ];

    /**
     * The model's default values for attributes.
     *
     * @param array
     */
    protected $attributes = [
        'full_name'     => null,
        'password'      => null,
        'meta'  => [
            'personal'  => [
                'avatar'     => null,
                'province'   => null,
                'city'       => null,
                'address'    => null,
                'postal_code'=> null,
                'phone'      => null
            ],
            'financial' => [
                'shabaa'        => null,
                'debit_card'    => ['img'   => null, 'value'    => null, 'validated'    => 0],
                'national_id'   => ['img'   => null, 'value'    => null, 'validated'    => 0],
                'license_card'  => ['img'   => null, 'value'    => null, 'validated'    => 0],
            ],
            'scores'    => [
                //* Scores example when it has been filled *//
                // ['to' => 2, 'from' => 1, 'sender' => 'میلاد محمدی', 'receiver' => 'محمد محمدی', 'contract_id' => 5, 'rate' => 1, 'desc' => '', 'is_seller' => 0, 'created_at => '']
            ],
            'favorites' => [
                //* Store Product ID
                // [5,95,64]
            ]
        ],
        'otp'        => null,
        'is_admin'   => 0,
        'is_disabled'=> 0
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
     * Get the wallet records associated with the user.
     */
    public function user_wallet() {
        return $this->hasOne('App\Models\UserWallet', 'user_id');
    }

    /**
     * Get the incoming records associated with the user.
     */
    public function user_contracts() {
        return $this->hasMany('App\Models\UserContract', 'user_id');
    }

    /**
     * Get the incoming records associated with the user.
     */
    public function core_incoming() {
        return $this->hasMany('App\Models\CoreIncoming', 'user_id');
    }

    /**
     * Get the transaction records associated with the user.
     */
    public function core_transaction() {
        return $this->hasMany('Modules\Transaction\Models\CoreTransaction', 'user_id');
    }
}
