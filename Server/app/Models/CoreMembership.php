<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CoreMembership extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @param string
     */
    protected $table = 'core_memberships';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'days',
        'status',
        'meta'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    /**
     * The model's default values for attributes.
     *
     * @param array
     */
    protected $attributes = [
        'days'  => null,
        'status'=> 0, // it means, it's disabled

        //* meta example when it has been filled *//
        // [
        //     'cost'   => 0, @int
        // ]
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta'  => AsCollection::class
    ];

    /**
     * Get the wallets records associated with the membership.
     */
    public function user_wallet() {
        return $this->hasMany('App\Models\UserWallet', 'membership_id');
    }
}
