<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Passport;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Manual Passport routes
Route::post('oauth/token', '\Laravel\Passport\Http\Controllers\AccessTokenController@issueToken');
Route::get('oauth/clients', '\Laravel\Passport\Http\Controllers\ClientController@forUser');
Route::post('oauth/clients', '\Laravel\Passport\Http\Controllers\ClientController@store');
Route::put('oauth/clients/{client_id}', '\Laravel\Passport\Http\Controllers\ClientController@update');
Route::delete('oauth/clients/{client_id}', '\Laravel\Passport\Http\Controllers\ClientController@destroy');
Route::get('oauth/scopes', '\Laravel\Passport\Http\Controllers\ScopeController@all');
Route::get('oauth/personal-access-tokens', '\Laravel\Passport\Http\Controllers\PersonalAccessTokenController@forUser');
Route::post('oauth/personal-access-tokens', '\Laravel\Passport\Http\Controllers\PersonalAccessTokenController@store');
Route::delete('oauth/personal-access-tokens/{token_id}', '\Laravel\Passport\Http\Controllers\PersonalAccessTokenController@destroy');

// Auth routes
Route::prefix('auth')->group(function () {
    Route::post('login', [App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('verify-sms', [App\Http\Controllers\Api\AuthController::class, 'verifySms']);
    Route::post('login-password', [App\Http\Controllers\Api\AuthController::class, 'loginPassword']);
    Route::middleware('auth:api')->group(function () {
        Route::post('set-pin', [App\Http\Controllers\Api\AuthController::class, 'setPin']);
        Route::post('refresh', [App\Http\Controllers\Api\AuthController::class, 'refresh']);
        Route::post('logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);
    });
});

// Protected routes
Route::middleware('auth:api')->group(function () {
    // Compte
    Route::get('compte', [App\Http\Controllers\Api\CompteController::class, 'show']);
    Route::get('historique', [App\Http\Controllers\Api\TransactionController::class, 'historique']);

    // Transactions
    Route::prefix('transactions')->group(function () {
        Route::post('depot', [App\Http\Controllers\Api\TransactionController::class, 'depot']);
        Route::post('retrait', [App\Http\Controllers\Api\TransactionController::class, 'retrait']);
        Route::post('transfert', [App\Http\Controllers\Api\TransactionController::class, 'transfert']);
        Route::post('paiement', [App\Http\Controllers\Api\TransactionController::class, 'paiement']);
    });

    // Marchand
    Route::prefix('marchand')->group(function () {
        Route::post('generate-qr', [App\Http\Controllers\Api\MarchandController::class, 'generateQr']);
    });

    // QR
    Route::get('qr/{code}', [App\Http\Controllers\Api\QrController::class, 'show']);

    // Admin
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('users', [App\Http\Controllers\Api\AdminController::class, 'users']);
        Route::get('transactions', [App\Http\Controllers\Api\AdminController::class, 'transactions']);
        Route::post('create-marchand', [App\Http\Controllers\Api\AdminController::class, 'createMarchand']);
    });
});
