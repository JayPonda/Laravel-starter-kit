<?php

use App\Http\Controllers\WebAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('login', [WebAuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [WebAuthController::class, 'login']);
    Route::get('register', [WebAuthController::class, 'showRegisterForm'])->name('register');
    Route::post('register', [WebAuthController::class, 'register']);
});

Route::post('logout', [WebAuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/dashboard', function () {
    return "<h1>Welcome to the Dashboard</h1><form method='POST' action='".route('logout')."'>".csrf_field()."<button type='submit'>Logout</button></form>";
})->middleware('auth')->name('dashboard');
