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

// Tested all
Route::namespace('User')->prefix('user')->group(function() {

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('contract')->group(function() {
            Route::post('/create', 'Contract@create');
            Route::post('/token/generate', 'Contract@generate_withdrawal_req_token');
            Route::post('/shipment/approve', 'Contract@send_proven_shipment_receipt');
            Route::post('/withdrawal/pending-balance', 'Contract@withdrawal_pending_balance');
            Route::post('/cancel', 'Contract@cancel');
            Route::post('/review', 'Contract@review_user');
        });
    
        Route::prefix('membership')->group(function() {
            Route::get('/fetch', 'Profile@fetch_available_memberships');
            Route::get('/check/{user_id}', 'Membership@is_memebrship_expired');
            Route::patch('/buy/{user_id}/{membership_id}', 'Membership@buy_membership_from_wallet');
        });
    
        Route::prefix('profile')->group(function() {
            Route::get('/fetch/{user_id}', 'Profile@fetch_user_data');
            Route::patch('/update/{user_id}/{mode}', 'Profile@update_user_data');
        });
    });
    
    Route::prefix('store')->group(function() {
        Route::prefix('fetch')->group(function () {
            Route::get('/product/{offset}/{limit}/{get_filters?}', 'Products@render_product_page');
            Route::get('/detail/{product_id}/{user_id}', 'ProductDetail@render_product_detail');
        });
    });
});

Route::middleware('auth:sanctum')->namespace('Admin')->prefix('cms')->group(function() {
    // All tested
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
        Route::post('/create', 'Membership@create');
        Route::patch('/update/{membership_id}', 'Membership@update');
        Route::delete('/delete/{membership_id}', 'Membership@delete');
    });

    // All tested
    Route::prefix('profile')->group(function() {
        Route::get('/fetch/{is_admin}/{offset}/{limit}/{searched?}', 'Profile@fetch');
        Route::patch('/identity/{user_id}', 'Profile@identify_profile');
    });

    Route::prefix('store')->group(function() {
        // All tested
        Route::prefix('fetch')->group(function () {
            Route::get('/{type}/{offset}/{limit}/{searched?}', 'Products@fetch');
            Route::get('/detail/{product_id}', 'Products@fetch_detail');
        });

        Route::post('/create', 'Products@create_product');
        Route::patch('/update/{product_id}', 'Products@update_product');
        // All tested
        Route::delete('/delete/{product_id}', 'Products@delete_product');
    });
});
