<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\TranslationController;

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

// Authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Translation routes
    Route::apiResource('translations', TranslationController::class);

    // Search translations
    Route::get('/translations/search/tags/{tag}', [TranslationController::class, 'searchByTag']);
    Route::get('/translations/search/keys/{key}', [TranslationController::class, 'searchByKey']);
    Route::get('/translations/search/content/{content}', [TranslationController::class, 'searchByContent']);

    Route::get('/translations/export/{locale?}', [TranslationController::class, 'export']);
});
