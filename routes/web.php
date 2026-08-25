<?php

use Illuminate\Support\Facades\Route;

// Backend + Swagger only. The frontend lives in the `blade` / `html` branches.
// Redirect the root to the Swagger UI so the API documentation is one click away.
Route::get('/', fn () => redirect('/api/documentation'));
