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
            ], [
                'option'=> 'CONTRACT_EXPIRATION',
                'value' => '5' 
            ], [
                'option'=> 'SAVED_BRAND',
                'value' => "['تولیدی حسابی', 'راه و ساختمان پستا', 'شرکت توسعه معادن رخساره', 'سازمان سرمایه گذاری پریسوز', 'سرمایه گذاری شیروانی']",
            ], [
                'option'=> 'SAVED_WIDTH',
                'value' => "[17.4, 18.2, 13.8, 14.5, 13.9]",
            ], [
                'option'=> 'SAVED_WEIGHT',
                'value' => "[23.5, 29, 13.8, 24.8, 28.5]",
            ], [
                'option'=> 'SAVED_HEIGHT',
                'value' => "[13.5, 9, 10.5, 9.5, 9.8, 10.8, 14.1]",
            ], [
                'option'=> 'SAVED_TYRE_HEIGHT',
                'value' => "[13.5, 15, 16.5, 18.6, 16, 15.3, 13.8]",
            ]
        ]);
    }
}
