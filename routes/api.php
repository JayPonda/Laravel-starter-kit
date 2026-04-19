<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HealthCheckController::class, 'api']);

Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/reset-password', [LoginController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [ProfileController::class, 'me']);
    Route::post('/logout', [ProfileController::class, 'logout']);
    Route::apiResource('files', FileController::class);
});
