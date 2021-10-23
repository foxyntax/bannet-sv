<?php

namespace Modules\Transaction\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Transaction\Models\CoreTransaction;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        CoreTransaction::factory()
                        ->count(1500)
                        ->create();
    }
}
