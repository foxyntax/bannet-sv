<?php

namespace Database\Factories;

use Carbon\Carbon;
use App\Models\User;
use App\Models\CoreProduct;
use App\Models\UserContract;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserContractFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserContract::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $user_count = User::count();
        return [
            'user_id'   => rand(2, $user_count),
            'product_id'=> rand(1, CoreProduct::count()),
            'status'    => rand(0, 1),
            'meta'      => [
                'province'       => $this->faker->city(),
                'city'           => $this->faker->city(),
                'desc'           => $this->faker->sentence(rand(3, 6)),
                'tyre_year'      => rand(2013, 2022),
                'count'          => rand(1, 80),
                'token'          => rand(10000, 99999),
                'shipment_day'   => rand(1, 30),
                'cost'           => rand(100, 99999) * 1000,
                'customer_id'    => rand(2, $user_count),
                'proven_shipment'=> $this->get_proven_shipment()
            ],
            'expired_at' => Carbon::parse(Carbon::now('Asia/Tehran')->timestamp + rand(10000, 999999))->toDateTimeString()
        ];
    }

    /**
     * Create proven shipment
     * 
     * @return array
     */
    public function get_proven_shipment()
    {
        $indexes = rand(0, 10);
        $shipment = [null, $this->faker->imageUrl(640, 640, 'shipment image', true)];
        return $shipment[array_rand($shipment)];
    }
}
