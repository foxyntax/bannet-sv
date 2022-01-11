<?php

namespace Database\Factories;

use App\Models\CoreProduct;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class CoreProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CoreProduct::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'type'  => rand(1, 3),
            'features' => [
                'name'        => Str::random(10),
                'design_name' => Str::random(8),
                'brand'       => $this->faker->company(),
                'diameter'    => $this->faker->randomFloat(1, 15, 45),
                'color'       => $this->faker->colorName(),
                'country'     => $this->faker->country(),
                'for_back'    => rand(0, 1),
                'for_front'   => rand(0, 1),
                'height'      => $this->faker->randomFloat(1, 8, 20),
                // 'tire_height' => $this->faker->randomFloat(1, 10, 20),
                'tubless'     => rand(0, 1),
                // 'speed'       => $this->faker->randomFloat(0, 140, 220),
                'width'       => $this->faker->randomFloat(1, 10, 20),
                // 'weight'      => $this->faker->randomFloat(1, 6, 25),
                'src'         => [
                    '/product/test/1.jpg',
                    '/product/test/2.jpg',
                    '/product/test/3.jpg',
                ]
            ]
        ];
    }
}
