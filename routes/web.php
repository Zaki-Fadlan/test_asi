<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ClientController; 

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
Route::prefix('clients')->group(function () {
    Route::get('/', [ClientController::class, 'index']);
    Route::post('/', [ClientController::class, 'store']);
    Route::get('{slug}', [ClientController::class, 'show']);
    Route::put('{slug}', [ClientController::class, 'update']);
    Route::delete('{slug}', [ClientController::class, 'destroy']);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
