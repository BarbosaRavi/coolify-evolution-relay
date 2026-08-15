<?php

use App\Http\Controllers\Project\ProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.api', 'can:project.view'])->group(function () {
    Route::get('/', [ProjectController::class, 'index']);
});

Route::middleware(['auth.api', 'can:project.create'])->group(function () {
    Route::post('/', [ProjectController::class, 'store']);
});

Route::middleware(['auth.api', 'can:project.restore'])->group(function () {
    Route::patch('/restore/{id}', [ProjectController::class, 'restore']);
});

Route::middleware(['auth.api', 'can:project.destroy'])->group(function () {
    Route::delete('/destroy/{id}', [ProjectController::class, 'destroy']);
});

Route::middleware(['auth.api', 'can:project.view'])->group(function () {
    Route::get('/{id}', [ProjectController::class, 'show']);
});

Route::middleware(['auth.api', 'can:project.update'])->group(function () {
    Route::put('/{id}', [ProjectController::class, 'update']);
});

Route::middleware(['auth.api', 'can:project.delete'])->group(function () {
    Route::delete('/{id}', [ProjectController::class, 'delete']);
});
