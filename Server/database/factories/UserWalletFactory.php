<?php

namespace Database\Factories;

use Carbon\Carbon;
use App\Models\User;
use App\Models\UserWallet;
use App\Models\CoreMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserWalletFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserWallet::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id'           => User::factory(),
            'membership_id'     => rand(1, CoreMembership::count()),
            'pending_balance'   => rand(100, 99999) * 1000,
            'available_balance' => rand(100, 9999) * 1000,
            'withdraw_balance'  => rand(100, 9999) * 1000,
            'transactions'      => $this->get_transaction(),
            'expired_at'        => Carbon::parse(Carbon::now('Asia/Tehran')->timestamp + rand(10000, 999999))->toDateTimeString()
        ];
    }

    /**
     * Create fav transaction lists
     * 
     * @return array
     */
    public function get_transaction()
    {
        $indexes = rand(0, 80);
        $trans = [];

        for ($i=0; $i <= $indexes; $i++) { 
            array_push($trans, [
                'title'         => 'یک خرید تست',
                'price'         => rand(100, 99999) * 1000,
                'status'        => rand(0,3),
                'refrence_id'   => rand(1000000, 9999999), 
                'date'          => Carbon::parse(Carbon::now('Asia/Tehran')->timestamp + rand(10000, 999999))->toDateTimeString()
            ]);
        }

        return (count($trans) === 0) ? null : $trans;
    }
}
