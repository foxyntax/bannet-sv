<?php

namespace Database\Factories;

use App\Models\CoreMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

class CoreMembershipFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CoreMembership::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => $this->faker->word(),
            'days'  => rand(5, 30),
            'status'=> rand(0, 1),
            'meta'  => [
                'cost'  => rand(100, 99999) * 1000,
            ]
        ];
    }
}
