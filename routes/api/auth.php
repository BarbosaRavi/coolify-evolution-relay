<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::group([], function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
});

Route::middleware(['auth.api'])->group(function () {
    Route::post('/me', [AuthController::class, 'me']);
});
