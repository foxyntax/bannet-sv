<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Transaction\Database\Factories\CoreTransactionFactory;

class CoreTransaction extends Model
{
    // use HasFactory;
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
    protected $hidden = [
        'user_id',
        'authority',
        'verified',
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
     * Get the user that owns the user meta record.
     */
    public function user() {
        return $this->belongsTo('App\User');
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    // protected static function newFactory()
    // {
    //     return CoreTransactionFactory::new();
    // }
}
