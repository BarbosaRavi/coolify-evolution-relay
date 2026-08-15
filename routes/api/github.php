<?php

use App\Http\Controllers\Github\GithubController;
use Illuminate\Support\Facades\Route;

Route::post('/push', [GithubController::class, 'push']);
