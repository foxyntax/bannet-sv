<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoreIncoming extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @param string
     */
    protected $table = 'core_incoming';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'title',
        'status',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'user_id'
    ];

    /**
     * The model's default values for attributes.
     *
     * @param array
     */
    // protected $attributes = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    // protected $casts = [];

    /**
     * Get the users that owns the incoming records from them.
     */
    public function user() {
        return $this->belongsTo('App\Models\User');
    }
}
