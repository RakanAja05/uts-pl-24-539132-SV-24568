<?php

use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\MetricsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', [HealthCheckController::class, 'check']);

Route::get('/metrics', [MetricsController::class, 'index']);
