<?php

use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('/auth')->group(base_path('routes/api/auth.php'));
Route::prefix('/project')->group(base_path('routes/api/project.php'));

Route::prefix('/deploy')->group(base_path('routes/api/deploy.php'));
Route::prefix('/github')->group(base_path('routes/api/github.php'));
