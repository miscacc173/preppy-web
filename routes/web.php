<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
});

// Vue Router owns every browser route. Laravel serves the same Vite-powered
// application shell for direct visits and browser refreshes.
Route::view('/{path?}', 'app')->where('path', '^(?!api(?:/|$)|sanctum(?:/|$)).*')->name('spa');
