<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('transaction')->group(function() {
    Route::post('/zarinpal/checkout', 'Transactions@pay_by_zarinpal');
    Route::post('/verify', 'Transactions@verify_payment');
});