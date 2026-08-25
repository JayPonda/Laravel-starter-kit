<?php

use Illuminate\Support\Facades\Route;

// Public frontend mode: no Blade web routes. The app is served as static HTML
// from ./public and talks to the REST API (routes/api.php). We still resolve
// "/" to the static entry page so the root URL is valid (e.g. for smoke tests).
Route::get('/', fn () => response()->file(public_path('index.html')));
