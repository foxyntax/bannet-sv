<?php

namespace Database\Seeders;

use App\Models\CoreProduct;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        CoreProduct::factory()
                    ->count(313)
                    ->create();
    }
}
