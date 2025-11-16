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

// Nouveaux endpoints d'authentification
Route::prefix('auth')->group(function () {
    // Routes publiques (sans authentification)
    Route::post('send-otp', [\App\Http\Controllers\Api\AuthController::class, 'sendOtp']);
    Route::post('verify-otp', [\App\Http\Controllers\Api\AuthController::class, 'verifyOtp']);
    Route::post('login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
});

// Routes protégées (require auth:api)
Route::prefix('auth')->middleware('auth:api')->group(function () {
    Route::post('register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
    Route::get('me', [\App\Http\Controllers\Api\AuthController::class, 'me']);
    Route::post('logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
});

// Endpoints des comptes (protégés)
Route::middleware('auth:api')->group(function () {
    Route::prefix('comptes')->group(function () {
        Route::get('{num}/dashboard', [\App\Http\Controllers\Api\CompteController::class, 'dashboard']);
        Route::get('{num}/solde', [\App\Http\Controllers\Api\CompteController::class, 'solde']);
        Route::get('{num}/transactions', [\App\Http\Controllers\Api\CompteController::class, 'transactions']);
        Route::post('{num}/transfert', [\App\Http\Controllers\Api\CompteController::class, 'transfert']);
        Route::post('{num}/paiement', [\App\Http\Controllers\Api\CompteController::class, 'paiement']);
    });

    // Endpoints de transactions (API principale)
    Route::get('compte', [\App\Http\Controllers\Api\TransactionController::class, 'getCompte']);
    Route::get('historique', [\App\Http\Controllers\Api\TransactionController::class, 'historique']);
    Route::prefix('transactions')->group(function () {
        Route::post('depot', [\App\Http\Controllers\Api\TransactionController::class, 'depot']);
        Route::post('retrait', [\App\Http\Controllers\Api\TransactionController::class, 'retrait']);
        Route::post('transfert', [\App\Http\Controllers\Api\TransactionController::class, 'transfert']);
        Route::post('paiement', [\App\Http\Controllers\Api\TransactionController::class, 'paiement']);
    });

    // Endpoints marchands
    Route::prefix('marchand')->group(function () {
        Route::post('generate-qr', [\App\Http\Controllers\Api\MarchandController::class, 'generateQr']);
    });
});

// Endpoints QR codes (publics - pas d'authentification requise)
Route::get('qr/{code}', [\App\Http\Controllers\Api\QrController::class, 'show']);

// Endpoints administrateur (protégés par middleware admin)
Route::middleware(['auth:api', 'admin'])->prefix('admin')->group(function () {
    Route::get('users', [\App\Http\Controllers\Api\AdminController::class, 'users']);
    Route::get('users/{id}', [\App\Http\Controllers\Api\AdminController::class, 'userDetails']);
    Route::get('transactions', [\App\Http\Controllers\Api\AdminController::class, 'transactions']);
    Route::get('statistiques', [\App\Http\Controllers\Api\AdminController::class, 'statistiques']);
    Route::get('marchands', [\App\Http\Controllers\Api\AdminController::class, 'marchands']);
    Route::put('marchands/{id}/toggle-status', [\App\Http\Controllers\Api\AdminController::class, 'toggleMarchandStatus']);
    Route::delete('users/{id}', [\App\Http\Controllers\Api\AdminController::class, 'deleteUser']);
});

// Endpoints de test sans authentification (pour développement)
Route::prefix('test')->group(function () {
    Route::post('login', function () {
        return response()->json([
            'message' => 'Code SMS envoyé (mode test)',
            'session_id' => 'test-session-' . uniqid(),
            'note' => 'Mode test - pas de SMS envoyé'
        ]);
    });
    
    Route::post('verify-sms', function () {
        return response()->json([
            'access_token' => 'test-token-' . uniqid(),
            'token_type' => 'Bearer',
            'user' => [
                'id' => 'test-user-' . uniqid(),
                'nom' => 'Test',
                'prenom' => 'Utilisateur',
                'telephone' => '785052217',
                'type' => 'utilisateur'
            ],
            'note' => 'Mode test - authentification simulée'
        ]);
    });
    
    Route::get('compte', function () {
        return response()->json([
            'solde' => 100000, // 1000 FCFA en centimes
            'type' => 'utilisateur'
        ]);
    });
});
