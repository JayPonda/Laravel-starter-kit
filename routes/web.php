<?php

use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\WebLoginController;
use App\Http\Controllers\WebRegisterController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HealthCheckController::class, 'index'])->name('health');

Route::middleware('guest')->group(function () {
    Route::get('login', [WebLoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [WebLoginController::class, 'login']);
    Route::get('register', [WebRegisterController::class, 'showRegisterForm'])->name('register');
    Route::post('register', [WebRegisterController::class, 'register']);
});

Route::post('logout', [WebLoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');
