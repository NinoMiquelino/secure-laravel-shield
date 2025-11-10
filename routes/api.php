<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

Route::middleware(['api.security'])->group(function () {
    Route::get('/user', [UserController::class, 'profile']);
    Route::get('/dashboard', [UserController::class, 'dashboard']);
});