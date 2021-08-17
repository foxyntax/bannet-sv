<?php

namespace Database\Seeders;

use App\Models\UserContract;
use Illuminate\Database\Seeder;

class ContractSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        UserContract::factory()
                    ->count(500)
                    ->create();
    }
}
