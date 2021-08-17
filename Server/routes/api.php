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

Route::middleware('auth:sanctum')->namespace('User')->prefix('user')->group(function() {
    Route::prefix('contract')->group(function() {
        Route::post('/token/generate', 'Contract@generate_withdrawal_req_token');
        Route::post('/shipment/approve', 'Contract@send_proven_shipment_receipt');
        Route::post('/withdrawal/pending-balance', 'Contract@withdrawal_pending_balance');
        Route::post('/cancel', 'Contract@cancel_contract');
        Route::post('/review', 'Contract@review_user');
    });

    Route::prefix('membership')->group(function() {
        Route::get('/check/{user_id}', 'Membership@is_memebrship_expired');
        Route::get('/buy/{user_id}/{membership_id}', 'Membership@buy_membership_from_wallet');
    });

    Route::prefix('profile')->group(function() {
        Route::prefix('fetch')->group(function () {
            Route::get('/{user_id}', 'Profile@fetch_user_data');
            Route::get('/membership', 'Profile@fetch_available_memberships');
        });

        Route::patch('/update', 'Profile@update_user_data');
    });

    Route::prefix('store')->group(function() {
        Route::prefix('fetch')->group(function () {
            Route::post('/{offset}/{limit}/{get_filters}', 'Products@render_product_page');
            Route::post('/detail/{product_id}/{user_id}', 'Products@render_product_detail');
        });
    });
});

Route::middleware('auth:sanctum')->namespace('Admin')->prefix('cms')->group(function() {
    Route::prefix('contract')->group(function() {
        Route::prefix('shipment')->group(function () {
            Route::post('/approve', 'Contract@approve_shipment');
            Route::post('/disapprove', 'Contract@disapprove_of_receipt');
        });

        Route::prefix('fetch')->group(function () {
            Route::get('/{status}/{offset}/{limit}/{searched}', 'Contract@fetch_contracts');
            Route::get('/detail/{contract_id}', 'Contract@fetch_contract_detail');
        });

        Route::post('/expire', 'Contract@expire_contracts');
    });

    Route::prefix('membership')->group(function() {
        Route::get('/fetch/{status}/{offset}/{limit}/{searched}', 'Membership@fetch_memberships');
        Route::patch('/update/{membership_id}', 'Membership@update_membership');
        Route::delete('/delete/{membership_id}', 'Membership@delete_memebrship');
    });

    Route::prefix('profile')->group(function() {
        Route::get('/fetch/{is_admin}/{offset}/{limit}/{searched}', 'Profile@fetch_users');
        Route::patch('/identity/{user_id}', 'Profile@identify_profile');
    });

    Route::prefix('store')->group(function() {
        Route::prefix('fetch')->group(function () {
            Route::get('/{status}/{offset}/{limit}/{searched}', 'Products@fetch_products');
            Route::get('/detail/{product_id}', 'Contract@fetch_contract_detail');
        });

        Route::post('/create', 'Products@create_product');
        Route::patch('/update/{product_id}', 'Products@update_product');
        Route::delete('/delete/{product_id}', 'Products@delete_product');
    });
});
