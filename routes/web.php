<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Route directe /api/documentation (remplace L5-Swagger défaillant)
Route::get('/api/documentation', function () {
    $html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orange Money API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.15.5/swagger-ui.css" />
    <link rel="icon" type="image/png" href="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.15.5/favicon-32x32.png" sizes="32x32" />
    <link rel="icon" type="image/png" href="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.15.5/favicon-16x16.png" sizes="16x16" />
    <style>
    .swagger-ui .topbar { display: block; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.15.5/swagger-ui-bundle.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.15.5/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = () => {
            window.ui = SwaggerUIBundle({
                url: "' . asset('api-docs.json') . '",
                dom_id: "#swagger-ui",
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.presets.standalone,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                displayOperationId: true,
                defaultModelsExpandDepth: 2,
                defaultModelExpandDepth: 2,
                showExtensions: true,
                showCommonExtensions: true
            });
        };
    </script>
</body>
</html>';
    return $html;
});

// Alternative /docs
Route::get('/docs', function () {
    return redirect('/api/documentation');
});

// Swagger UI Documentation (solution fonctionnelle)
Route::get('/swagger-ui', function () {
    return redirect('/api/documentation');
});

// API Documentation JSON endpoint
Route::get('/api-docs.json', function () {
    $path = public_path('api-docs.json');
    abort_unless(file_exists($path), 404);
    return response()->file($path, ['Content-Type' => 'application/json']);
});

// Route de test sans base de données
Route::prefix('api/test')->group(function () {
    
    // Test login sans vérification base de données
    Route::post('login', function () {
        return response()->json([
            'message' => 'Code SMS envoyé (mode test)',
            'session_id' => 'test-session-' . uniqid(),
            'note' => 'Mode test - pas de SMS envoyé'
        ]);
    });
    
    // Test verification SMS
    Route::post('verify-sms', function () {
        return response()->json([
            'access_token' => 'test-token-' . uniqid(),
            'token_type' => 'Bearer',
            'user' => [
                'id' => 'test-user-' . uniqid(),
                'nom' => 'Test',
                'prenom' => 'Utilisateur',
                'telephone' => '785052217',
                'role' => 'client'
            ],
            'note' => 'Mode test - authentification simulée'
        ]);
    });
    
    // Test compte
    Route::get('compte', function () {
        return response()->json([
            'solde' => 100000, // 1000 FCFA en centimes
            'type' => 'client'
        ]);
    });
    
});
