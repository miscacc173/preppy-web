<?php

use App\Http\Controllers\Auth\AppleAuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
    Route::get('/auth/apple', [AppleAuthController::class, 'redirect'])->name('apple.redirect');
    // Apple requires response_mode=form_post when name/email scopes are requested.
    // The callback uses an encrypted, expiring state value, so it remains CSRF-safe
    // without relying on a cross-site POST session cookie.
    Route::post('/auth/apple/callback', [AppleAuthController::class, 'callback'])
        ->withoutMiddleware([ValidateCsrfToken::class])
        ->name('apple.callback');
});

Route::view('/{path?}', 'app')->where('path', '^(?!api(?:/|$)|sanctum(?:/|$)).*')->name('spa');
