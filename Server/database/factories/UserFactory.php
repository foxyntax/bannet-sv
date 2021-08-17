<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'full_name' => $this->faker->name(),
            'tell'      => '09' . rand(1, 3) . rand(1000000, 9999999),
            'password'  => null,
            'meta'  => [
                'personal'  => [
                    'avatar'     => null,
                    'province'   => $this->faker->city(),
                    'city'       => $this->faker->city(),
                    'address'    => $this->faker->address(),
                    'postal_code'=> $this->faker->numberBetween(10000, 99999),
                ],
                'financial' => [
                    'shabaa'        => $this->faker->numberBetween(10000, 99999) . $this->faker->numberBetween(10000, 99999) . rand(1000, 9999),
                    'debit_card'    => [
                        'img'       => $this->faker->imageUrl(640, 640, 'debit_card', true),
                        'value'     => $this->faker->numberBetween(10000, 99999),
                        'validated' => rand(0,1)
                    ],
                    'national_id'   => [
                        'img'       => $this->faker->imageUrl(640, 640, 'national_id', true),
                        'value'     => $this->faker->nationalCode(),
                        'validated' => rand(0,1)
                    ],
                    'license_card'  => [
                        'img'       => $this->faker->imageUrl(640, 640, 'license_card', true),
                        'value'     => $this->faker->numberBetween(10000, 99999),
                        'validated' => rand(0,1)
                    ],
                ],
                'scores'    => $this->get_scores(),
                'favorites' => $this->get_fav()
            ],
            'is_admin'   => 0
        ];
    }

    /**
     * Indicate that the model's "is_admin" should be 1 (true).
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function admin()
    {
        return $this->state(function (array $attributes) {
            return [
                'tell'      => '09156284764',
                'password'  => Hash::make('password'),
                'is_admin'  => 1,
                'meta'      => []
            ];
        });
    }

    /**
     * Create fav product id
     * 
     * @return array
     */
    public function get_scores()
    {
        $indexes = rand(0, 10);
        $scores = [];

        for ($i=0; $i <= $indexes; $i++) { 
            array_push($scores, [
                'sender_id' => rand(1, 50),
                'rate'      => rand(0, 5),
                'desc'      => $this->faker->sentence(rand(3, 6)),
                'is_seller' => rand(0, 1)]
            );
        }

        return $scores;
    }

    /**
     * Create fav product id
     * 
     * @return array
     */
    public function get_fav()
    {
        $indexes = rand(0, 10);
        $ids = [];

        for ($i=0; $i <= $indexes; $i++) { 
            array_push($ids, rand(0, 50));
        }

        return $ids;
    }
}
