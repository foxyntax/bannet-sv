<?php

namespace Modules\Settings\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset All Config Via Seeder
        if(DB::table('core_options')->count() !== 0) {
            DB::table('core_options')->truncate();
        }
        
        DB::table('core_options')->insert([
            [
                'option'=> 'APP_NAME',
                'value' => 'بنت'    
            ], [
                'option'=> 'BUSINESS_ADDRESS',
                'value' => 'یک آدرس مجازی' 
            ], [
                'option'=> 'APP_TELL',
                'value' => '05138455505' 
            ], [
                'option'=> 'APP_MAIL',
                'value' => 'support@banett.com' 
            ], [
                'option'=> 'ADMIN_TELL',
                'value' => '09156284764,0912456789' 
            ]
        ]);
    }
}
