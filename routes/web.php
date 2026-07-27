<?php

use App\Http\Controllers\RateController;
use Illuminate\Support\Facades\Route;

Route::get('/', [RateController::class, 'index'])->name('home');
Route::get('/api/rates/today', [RateController::class, 'today'])->name('rates.today');
