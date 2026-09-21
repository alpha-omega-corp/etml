<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\DeckController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\ProgramEntryController;
use App\Http\Controllers\SelectionController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', [LanguageController::class, 'index'])->name('languages.index');

    Route::post('/cards/{card}/status', [CardController::class, 'setStatus'])->name('cards.status');
    Route::post('/cards/reset', [CardController::class, 'reset'])->name('cards.reset');

    // A user's own deck, cut out of a list. Fixed first segment, so these
    // stay clear of the `/{language}` block below.
    Route::post('/selections', [SelectionController::class, 'store'])->name('selections.store');
    Route::delete('/selections/{unit}', [SelectionController::class, 'destroy'])->name('selections.destroy');

    Route::get('/units/create', [UnitController::class, 'create'])->name('units.create');
    Route::post('/units', [UnitController::class, 'store'])->name('units.store');
    Route::patch('/units/{unit}', [UnitController::class, 'update'])->name('units.update');
    Route::post('/units/{unit}/import', [UnitController::class, 'import'])->name('units.import');

    Route::get('/admin', [AdminController::class, 'show'])->name('admin.login');
    Route::post('/admin', [AdminController::class, 'login']);
    Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');

    Route::middleware('admin')->group(function () {
        Route::delete('/units/{unit}', [UnitController::class, 'destroy'])->name('units.destroy');

        // One line of a programme. Fixed first segment, so these stay clear of
        // the `/{language}` block below.
        Route::post('/programme/entries', [ProgramEntryController::class, 'save'])->name('entries.save');
        Route::delete('/programme/entries/{entry}', [ProgramEntryController::class, 'destroy'])->name('entries.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Language-scoped pages
    |--------------------------------------------------------------------------
    |
    | `/{language}` reads as `/allemand`, which means it would swallow any new
    | top-level path. Routes are matched in the order they are declared, so
    | everything with a fixed first segment MUST stay above this block — and a
    | new one goes above it too, never below.
    |
    */
    Route::get('/{language}', [ProgramController::class, 'show'])->name('program.show');

    Route::middleware('admin')->group(function () {
        Route::get('/{language}/programme', [ProgramController::class, 'edit'])->name('program.edit');
        Route::post('/{language}/programme', [ProgramController::class, 'store'])->name('program.store');
        Route::delete('/{language}/programme', [ProgramController::class, 'destroy'])->name('program.destroy');
    });

    Route::get('/{language}/{kind}/{unit}', [DeckController::class, 'show'])->name('deck.show');
});
