<?php

use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\WebAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HealthCheckController::class, 'index'])->name('health');

Route::middleware('guest')->group(function () {
    Route::get('login', [WebAuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [WebAuthController::class, 'login']);
    Route::get('register', [WebAuthController::class, 'showRegisterForm'])->name('register');
    Route::post('register', [WebAuthController::class, 'register']);
});

Route::post('logout', [WebAuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');
