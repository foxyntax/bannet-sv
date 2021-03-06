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

// Route::middleware('auth:sanctum')

Route::namespace('User')->prefix('user')->group(function() {

    Route::middleware(['auth:sanctum', 'idcheck'])->group(function () {
        Route::prefix('contract')->group(function() {
            Route::post('/create', 'Contract@create');
            Route::post('/token/generate', 'Contract@generate_withdrawal_req_token');
            Route::post('/shipment/approve', 'Contract@send_proven_shipment_receipt');
            Route::post('/withdrawal/pending-balance', 'Contract@withdrawal_pending_balance');
            Route::post('/cancel', 'Contract@cancel');
            Route::post('/review', 'Contract@review_user');
            Route::patch('/buy', 'Contract@buy_ad_by_using_wallet');
            Route::patch('/update/{contract_id}', 'Contract@update');
        });
    
        Route::prefix('membership')->group(function() {
            Route::get('/fetch', 'Membership@fetch_available_memberships');
            Route::get('/check/{user_id}', 'Membership@is_membership_expired');
            Route::patch('/buy', 'Membership@buy_membership_by_using_wallet');
        });
    
        Route::prefix('profile')->group(function() {
            Route::get('/public-fetch/{id}', 'Profile@fetch_profile_as_guest');
            Route::get('/fetch/{user_id}', 'Profile@fetch_user_data');
            Route::post('/update/{user_id}/{mode}', 'Profile@update_user_data');
            Route::patch('/withdrawal/{user_id}', 'Profile@withdrawal_request');
        });
    });
    
    Route::prefix('store')->group(function() {
        Route::prefix('fetch')->group(function () {
            Route::get('/product/{offset}/{limit}/{get_filters}/{city}', 'Products@render_product_page');
            Route::get('/detail/{product_id}/{user_id}', 'ProductDetail@render_product_detail');
        });
    });
});

Route::middleware(['auth:sanctum', 'idcheck'])->namespace('Admin')->prefix('cms')->group(function() {
    Route::prefix('contract')->group(function() {
        Route::prefix('shipment')->group(function () {
            Route::patch('/approve', 'Contract@approve_shipment');
            Route::patch('/disapprove', 'Contract@disapprove_of_receipt');
        });

        Route::prefix('fetch')->group(function () {
            Route::get('/{status}/{offset}/{limit}/{searched?}', 'Contract@fetch');
            Route::get('/detail/{contract_id}', 'Contract@fetch_detail');
        });

        Route::patch('/expire', 'Contract@expire');
    });

    // All tested
    Route::prefix('membership')->group(function() {
        Route::get('/fetch/{status}/{offset}/{limit}/{searched?}', 'Membership@fetch');
        Route::get('/detail/{membership_id}', 'Membership@fetch_detail');
        Route::post('/create', 'Membership@create');
        Route::patch('/update/{membership_id}', 'Membership@update');
        Route::delete('/delete/{membership_id}', 'Membership@delete');
    });

    // All tested
    Route::prefix('profile')->group(function() {
        Route::get('/fetch/{mode}/{offset}/{limit}/{searched?}', 'Profile@fetch');
        Route::patch('/identity/{user_id}', 'Profile@identify_profile');
        Route::patch('/accept-withdrawal/{user_id}', 'Profile@accept_withdrawal_req');
    });

    Route::prefix('store')->group(function() {
        Route::prefix('fetch')->group(function () {
            Route::get('/{type}/{offset}/{limit}/{searched?}', 'Products@fetch');
            Route::get('/detail/{product_id}', 'Products@fetch_detail');
        });

        Route::post('/create', 'Products@create_product');
        Route::post('/update/{product_id}', 'Products@update_product');
        // All tested
        Route::delete('/delete/{product_id}', 'Products@delete_product');
    });
});


// 
// 
// ===================== API ROUTE of MODULES =================
// 
//

// Settings
Route::middleware(['auth:sanctum', 'idcheck'])
    ->namespace('Settings')
    ->prefix('setting')
    ->group(function() {
        Route::patch('/set', 'Options@set_option');
        Route::get('/get', 'Options@get_options');
});

// Transaction
Route::middleware(['auth:sanctum', 'idcheck'])
    ->namespace('Transaction')
    ->prefix('transaction')
    ->group(function() {
        Route::post('/zarinpal/checkout', 'Transactions@pay_by_zarinpal');
        Route::post('/verify', 'Transactions@verify_payment');
});

// AUTH
Route::namespace('Auth')->prefix('auth')->group(function () {
    
    // Register
    Route::prefix('register')->group(function () {
        Route::post('/with-pass', 'RegisterController@register_by_pass');
        Route::post('/without-pass', 'RegisterController@register_without_pass');
        // if you have extra actions like create new records in another database,
        // please develope them whitin the above functions
    });

    // OTP Actions
    Route::prefix('otp')->group(function () {
        Route::post('/check', 'LoginController@check_user'); // [if you wanna check user existance before login/register and send a token]
        Route::post('/send', 'LoginController@send_otp_token'); // [if you just wanna send sms to user]
    });
    
    // Login
    Route::prefix('login')->group(function () {
    
        // Route::post('/by-mail', 'LoginController@logout_by_mail');
        Route::post('/basic', 'LoginController@login_basic');
        Route::prefix('otp')->group(function () {
            Route::post('/verify', 'LoginController@verify_user'); // step two
        });
    
    });
    
    // Logout
    Route::get('/logout/{user_id}', 'LogoutController@logout')->middleware('auth:sanctum');
    
    // Reset Contract & Security Data
    Route::middleware(['auth:sanctum', 'idcheck'])->prefix('reset')->group(function () {
    
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
        Route::post('check/{mode}', 'ApiToken@check_api_token');
    });
});

