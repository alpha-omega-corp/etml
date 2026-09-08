<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\ChapterController;
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

    Route::get('/branches/{branch}', [BranchController::class, 'show'])->name('branches.show');

    Route::get('/chapters/create', [ChapterController::class, 'create'])->name('chapters.create');
    Route::post('/chapters', [ChapterController::class, 'store'])->name('chapters.store');
    Route::get('/chapters/{chapter}', [ChapterController::class, 'show'])->name('chapters.show');
    Route::patch('/chapters/{chapter}', [ChapterController::class, 'update'])->name('chapters.update');
    Route::post('/chapters/{chapter}/import', [ChapterController::class, 'import'])->name('chapters.import');

    Route::get('/admin', [AdminController::class, 'show'])->name('admin.login');
    Route::post('/admin', [AdminController::class, 'login']);
    Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');

    Route::middleware('admin')->group(function () {
        Route::delete('/chapters/{chapter}', [ChapterController::class, 'destroy'])->name('chapters.destroy');
    });
});
