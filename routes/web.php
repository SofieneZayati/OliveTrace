<?php

use App\Http\Controllers\Admin\UserController;
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
});

require __DIR__.'/production.php';
require __DIR__.'/certification.php';
require __DIR__.'/auth.php';
