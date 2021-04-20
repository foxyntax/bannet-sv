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

    Route::prefix('fetch')->group(function() {

    });

    Route::prefix('save')->group(function() {
        
    });

    Route::prefix('update')->group(function() {
        
    });

    Route::prefix('delete')->group(function() {

    });
});

Route::namespace('Admin')->prefix('cms')->group(function() {

    Route::prefix('fetch')->group(function() {

    });

    Route::prefix('save')->group(function() {
        
    });

    Route::prefix('update')->group(function() {
        
    });

    Route::prefix('delete')->group(function() {

    });
});
