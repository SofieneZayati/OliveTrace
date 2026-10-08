<?php

use App\Http\Controllers\Admin\MillController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Miller\MillRequestController;
use App\Http\Controllers\Miller\MyMillController;
use App\Http\Controllers\Miller\OilLotController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'active'])->name('dashboard');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'active', 'role:admin', 'can:access-admin'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::resource('users', UserController::class)->only(['index', 'show', 'edit', 'update', 'destroy']);
    Route::resource('mills', MillController::class)->only(['index', 'show', 'edit', 'update', 'destroy']);
});

Route::prefix('mill')->name('mill.')->middleware(['auth', 'active', 'role:miller'])->group(function () {
    Route::get('/', [MyMillController::class, 'show'])->name('show');
    Route::get('/edit', [MyMillController::class, 'edit'])->name('edit');
    Route::patch('/', [MyMillController::class, 'update'])->name('update');
    Route::prefix('requests')->name('mill-requests.')->group(function () {
        Route::get('/', [MillRequestController::class, 'index'])->name('index');
        Route::get('/{mill_request}', [MillRequestController::class, 'show'])->name('show');
        Route::patch('/{mill_request}', [MillRequestController::class, 'update'])->name('update');
    });
    Route::prefix('oil-lots')->name('oil-lots.')->group(function () {
        Route::get('/{mill_request}/create', [OilLotController::class, 'create'])->name('create');
        Route::post('/{mill_request}', [OilLotController::class, 'store'])->name('store');
        Route::get('/{oil_lot}/edit', [OilLotController::class, 'edit'])->name('edit');
        Route::patch('/{oil_lot}', [OilLotController::class, 'update'])->name('update');
    });
});

require __DIR__.'/production.php';
require __DIR__.'/certification.php';
require __DIR__.'/distribution.php';
require __DIR__.'/consumer.php';
require __DIR__.'/auth.php';
