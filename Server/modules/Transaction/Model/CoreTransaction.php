<?php

namespace Modules\Transaction\Models;

use Illuminate\Database\Eloquent\Model;

class CoreTransaction extends Model
{
    /**
     * The table associated with the model.
     *
     * @param string
     */
    protected $table = 'core_transactions';

    /**
    * The model's default values for attributes.
    *
    * @param array
    */
    protected $attributes = [
        'verified'      => 0,
        'refrence_id'   => null
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @param array
     */
    protected $fillable = [
        'user_id',
        'authority',
        'refrence_id',
        'amount',
        'driver',
        'description',
        'verified',
        'meta'
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @param array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @param array
     */
    protected $hidden = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta'  => AsCollection::class
    ];


    /**
     * Get the user that owns the user meta record.
     */
    public function user() {
        return $this->belongsTo('App\User');
    }
}
