<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CardController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', [CardController::class, 'index'])->name('cards');
    Route::post('/cards/{card}/status', [CardController::class, 'setStatus'])->name('cards.status');
    Route::post('/cards/reset', [CardController::class, 'reset'])->name('cards.reset');
});
