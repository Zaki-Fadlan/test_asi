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
Route::get('clients', [ClientController::class, 'index']);
Route::get('clients/{slug}', [ClientController::class, 'show']);
Route::post('clients', [ClientController::class, 'store']);
Route::put('clients/{slug}', [ClientController::class, 'update']);
Route::delete('clients/{slug}', [ClientController::class, 'destroy']);


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
