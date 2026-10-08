<?php

use App\Http\Controllers\Distribution\DistributorProfileController;
use App\Http\Controllers\Distribution\OilProductController;
use App\Http\Controllers\Distribution\PublicCatalogController;
use App\Http\Controllers\Distribution\ShipmentController;
use Illuminate\Support\Facades\Route;

Route::get('/catalog', PublicCatalogController::class)->name('catalog.index');

Route::prefix('products')->name('producer.products.')->middleware(['auth', 'active', 'role:producer,admin'])->group(function (): void {
    Route::get('/', [OilProductController::class, 'index'])->name('index');
    Route::get('/create', [OilProductController::class, 'create'])->name('create');
    Route::post('/', [OilProductController::class, 'store'])->name('store');
    Route::get('/{product}', [OilProductController::class, 'show'])->whereNumber('product')->name('show');
    Route::get('/{product}/edit', [OilProductController::class, 'edit'])->whereNumber('product')->name('edit');
    Route::patch('/{product}', [OilProductController::class, 'update'])->whereNumber('product')->name('update');
    Route::patch('/{product}/archive', [OilProductController::class, 'archive'])->whereNumber('product')->name('archive');
    Route::patch('/{product}/visibility', [OilProductController::class, 'toggleVisibility'])->whereNumber('product')->name('visibility');
});

Route::prefix('distributor')->name('distributor.')->middleware(['auth', 'active', 'role:distributor'])->group(function (): void {
    Route::get('/profile', [DistributorProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/create', [DistributorProfileController::class, 'create'])->name('profile.create');
    Route::post('/profile', [DistributorProfileController::class, 'store'])->name('profile.store');
    Route::get('/profile/edit', [DistributorProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [DistributorProfileController::class, 'update'])->name('profile.update');

    Route::get('/shipments', [ShipmentController::class, 'index'])->name('shipments.index');
    Route::get('/shipments/create', [ShipmentController::class, 'create'])->name('shipments.create');
    Route::post('/shipments', [ShipmentController::class, 'store'])->name('shipments.store');
    Route::get('/shipments/{shipment}', [ShipmentController::class, 'show'])->whereNumber('shipment')->name('shipments.show');
    Route::get('/shipments/{shipment}/edit', [ShipmentController::class, 'edit'])->whereNumber('shipment')->name('shipments.edit');
    Route::patch('/shipments/{shipment}', [ShipmentController::class, 'update'])->whereNumber('shipment')->name('shipments.update');
    Route::patch('/shipments/{shipment}/status', [ShipmentController::class, 'updateStatus'])->whereNumber('shipment')->name('shipments.status');
    Route::post('/shipments/{shipment}/impact', [ShipmentController::class, 'analyzeImpact'])->whereNumber('shipment')->middleware('throttle:10,1')->name('shipments.impact');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'active', 'role:admin', 'can:access-admin'])->group(function (): void {
    Route::get('/products', [OilProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [OilProductController::class, 'show'])->whereNumber('product')->name('products.show');
    Route::get('/products/{product}/edit', [OilProductController::class, 'edit'])->whereNumber('product')->name('products.edit');
    Route::patch('/products/{product}', [OilProductController::class, 'update'])->whereNumber('product')->name('products.update');
    Route::patch('/products/{product}/archive', [OilProductController::class, 'archive'])->whereNumber('product')->name('products.archive');
    Route::patch('/products/{product}/visibility', [OilProductController::class, 'moderateVisibility'])->whereNumber('product')->name('products.visibility');

    Route::get('/shipments', [ShipmentController::class, 'index'])->name('shipments.index');
    Route::get('/shipments/{shipment}', [ShipmentController::class, 'show'])->whereNumber('shipment')->name('shipments.show');
    Route::post('/shipments/{shipment}/impact', [ShipmentController::class, 'analyzeImpact'])->whereNumber('shipment')->middleware('throttle:10,1')->name('shipments.impact');
});
