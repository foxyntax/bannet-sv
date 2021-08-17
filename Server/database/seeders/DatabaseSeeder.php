<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WalletSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\ContractSeeder;
use Database\Seeders\MembershipSeeder;
use Modules\Settings\Database\Seeders\OptionSeeder;
use Modules\Transaction\Database\Seeders\TransactionSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            // From App
            UserSeeder::class, // add admin to user table first
            MembershipSeeder::class,
            ProductSeeder::class,
            WalletSeeder::class, // add wallet with users
            ContractSeeder::class,
            
            // From Modules
            OptionSeeder::class,
            TransactionSeeder::class
        ]);
    }
}
