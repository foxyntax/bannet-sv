<?php

namespace Database\Seeders;

use App\Models\CoreMembership;
use Illuminate\Database\Seeder;

class MembershipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        CoreMembership::factory()
                        ->count(4)
                        ->create();
    }
}
