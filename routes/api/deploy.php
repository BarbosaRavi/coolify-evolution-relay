<?php

use App\Http\Controllers\Deploy\DeployController;
use Illuminate\Support\Facades\Route;

Route::post('/hook/{secret}', [DeployController::class, 'notify']);
