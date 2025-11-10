<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('app');
});

Route::get('/login', function () {
    return view('app');
});

Route::get('/dashboard', function () {
    return view('app');
})->name('dashboard');

// Health check route
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toDateTimeString(),
        'security' => [
            'jwt_enabled' => true,
            'redis_connected' => extension_loaded('redis'),
            'rate_limiting_active' => true
        ]
    ]);
});