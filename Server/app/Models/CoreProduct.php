<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CoreProduct extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @param string
     */
    protected $table = 'core_products';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'features'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['type'];

    /**
     * The model's default values for attributes.
     *
     * @param array
     */
    protected $attributes = [
        'features' => [], // it means, it's disabled

        //* features example when it has been filled *//
        /* [
            'name'        => '',
            'design_name' => '',
            'brand'      => '',
            'diameter'    => 0, @int
            'color'       => '',
            'country'     => '',
            'for_back'    => 1, is true
            'for_front'   => 0, is faLse
            'height'      => 0, @int
            'tire_height' => 0, @int
            'tubless'     => 0, @int
            'speed'       => 0, @int
            'width'       => 0, @int
            'weight'      => 0  @int
        * relative addresses of img src:
            'src'         => '' @array 
        ] */
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'features'  => AsCollection::class
    ];
}
