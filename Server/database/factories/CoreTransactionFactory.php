<?php

namespace Modules\Transaction\Database\Factories;

use App\Models\User;
use Modules\Transaction\Models\CoreTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class CoreTransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CoreTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id'       => rand(1, User::count()),
            'authority'     => rand(1000000, 9999999),
            'refrence_id'   => rand(1000000, 9999999),
            'amount'        => rand(100, 99999) * 100,
            'driver'        => 'zarinpal',
            'description'   => 'یک خرید تست',
            'verified'      => rand(0, 1),
            'meta'          => []
        ];
    }
}
