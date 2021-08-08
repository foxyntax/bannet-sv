<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Register
Route::prefix('auth')->group(function () {
    
    Route::prefix('register')->group(function () {
        Route::post('/with-pass', 'RegisterController@register_by_pass');
        Route::post('/without-pass', 'RegisterController@register_without_pass');
        // if you have extra actions like create new records in another database,
        // please develope them whitin the above functions
    });
    
    // Login
    Route::prefix('login')->group(function () {
    
        // Route::post('/by-mail', 'LoginController@logout_by_mail');
        Route::post('/basic', 'LoginController@login_basic');
        Route::prefix('otp')->group(function () {
            Route::post('/check', 'LoginController@check_user'); // step one [if you want register after check]
            Route::post('/send', 'LoginController@send_otp_token'); // step one [if you just want to send sms to client]
            Route::post('/verify', 'LoginController@verify_user'); // step two
        });
    
    });
    
    // Logout
    Route::get('/logout/{user_id}', 'LogoutController@logout')->middleware('auth:sanctum');
    
    // Reset Contract & Security Data
    Route::middleware('auth:sanctum')->prefix('reset')->group(function () {
    
        Route::patch('/password/{with_old}', 'ResetController@update_pass');
        Route::prefix('tell')->group(function () {
            Route::get('/send/{by}/{info}', 'ResetController@sms_to_new_tell');
            Route::patch('/change', 'ResetController@update_tell');
        });
        Route::prefix('mail')->group(function () {
            Route::get('/send/{by}/{info}', 'ResetController@mail_token');
            Route::patch('/change', 'ResetController@update_mail');
        });
    
    });
    
    Route::prefix('api-token')->group(function () {
        Route::post('check', 'ApiToken@check_api_token');
    });
});
