<?php

namespace Database\Seeders;

use App\Models\UserWallet;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        UserWallet::factory()
                  ->count(50)
                  ->create();
    }
}
