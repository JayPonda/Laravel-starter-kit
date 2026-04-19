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

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $files = $user->files()->wherePivot('permission', 'owner')->latest()->take(5)->get();
        return view('dashboard', compact('files'));
    })->name('dashboard');

    Route::get('/files', [\App\Http\Controllers\FileController::class, 'index'])->name('files.index');
    Route::post('/files', [\App\Http\Controllers\FileController::class, 'store'])->name('files.store');
    Route::post('/files/{file}/share', [\App\Http\Controllers\FileController::class, 'share'])->name('files.share');
    Route::delete('/files/{file}/share/{user}', [\App\Http\Controllers\FileController::class, 'unshare'])->name('files.unshare');
    Route::delete('/files/{file}', [\App\Http\Controllers\FileController::class, 'destroy'])->name('files.destroy');
});
