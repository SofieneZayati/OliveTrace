<?php

use App\Http\Controllers\Production\FarmActivityController;
use App\Http\Controllers\Production\FarmAdviceReportController;
use App\Http\Controllers\Production\FarmAssistantController;
use App\Http\Controllers\Production\FarmController;
use App\Http\Controllers\Production\FarmMapController;
use App\Http\Controllers\Production\HarvestController;
use App\Http\Controllers\Production\Miller\MillingOperationController as MillerMillingOperationController;
use App\Http\Controllers\Production\MillingOperationController as ProducerMillingOperationController;
use App\Http\Controllers\Production\MillRequestController;
use App\Http\Controllers\Production\OilLotController;
use App\Http\Controllers\Production\OriginController;
use App\Http\Controllers\Production\ProducerProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/origins/farms/{farm}', [OriginController::class, 'show'])->whereNumber('farm')->name('origin.farms.show');

Route::prefix('producer')->name('producer.')->middleware(['auth', 'active', 'role:producer'])->group(function () {
    Route::get('profile/create', [ProducerProfileController::class, 'create'])->name('profile.create');
    Route::post('profile', [ProducerProfileController::class, 'store'])->name('profile.store');
    Route::get('profile', [ProducerProfileController::class, 'show'])->name('profile.show');
    Route::get('profile/edit', [ProducerProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProducerProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile', [ProducerProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('farms/map', FarmMapController::class)->name('farms.map');
    Route::resource('farms', FarmController::class)->whereNumber('farm');
    Route::resource('farms.activities', FarmActivityController::class)->except(['show', 'destroy'])->whereNumber(['farm', 'activity']);
    Route::patch('farms/{farm}/activities/{activity}/status', [FarmActivityController::class, 'status'])->whereNumber(['farm', 'activity'])->name('farms.activities.status');
    Route::get('farms/{farm}/reports', [FarmAdviceReportController::class, 'index'])->whereNumber('farm')->name('farms.reports.index');
    Route::post('farms/{farm}/reports', [FarmAdviceReportController::class, 'store'])->whereNumber('farm')->middleware('throttle:3,1')->name('farms.reports.store');
    Route::get('farms/{farm}/reports/{report}', [FarmAdviceReportController::class, 'show'])->whereNumber(['farm', 'report'])->name('farms.reports.show');
    Route::post('farms/{farm}/reports/{report}/recommendations/{recommendation}', [FarmAdviceReportController::class, 'adopt'])->whereNumber(['farm', 'report', 'recommendation'])->name('farms.reports.adopt');
    Route::patch('farms/{farm}/archive', [FarmController::class, 'archive'])->whereNumber('farm')->name('farms.archive');
    Route::post('farms/{farm}/advice', FarmAssistantController::class)->whereNumber('farm')->middleware('throttle:5,1')->name('farms.advice');
    Route::resource('harvests', HarvestController::class)->whereNumber('harvest');
    Route::post('harvests/{harvest}/estimate', [HarvestController::class, 'estimate'])->whereNumber('harvest')->middleware('throttle:5,1')->name('harvests.estimate');
    Route::post('harvests/{harvest}/requests', [MillRequestController::class, 'store'])->whereNumber('harvest')->name('mill-requests.store');
    Route::patch('requests/{mill_request}/cancel', [MillRequestController::class, 'cancel'])->whereNumber('mill_request')->name('requests.cancel');
    Route::get('oil-lots', [OilLotController::class, 'index'])->name('oil-lots.index');
    Route::get('oil-lots/{oil_lot}', [OilLotController::class, 'show'])->whereNumber('oil_lot')->name('oil-lots.show');
    Route::get('milling-operations', [ProducerMillingOperationController::class, 'index'])->name('milling-operations.index');
    Route::get('milling-operations/{millingOperation}', [ProducerMillingOperationController::class, 'show'])->whereNumber('millingOperation')->name('milling-operations.show');
    Route::post('milling-operations/{millingOperation}/confirm-delivery', [ProducerMillingOperationController::class, 'confirmDelivery'])->whereNumber('millingOperation')->name('milling-operations.confirm-delivery');
    Route::post('milling-operations/{millingOperation}/acknowledge', [ProducerMillingOperationController::class, 'acknowledge'])->whereNumber('millingOperation')->name('milling-operations.acknowledge');
});

Route::get('/producer-logos/{profile}', [ProducerProfileController::class, 'logo'])
    ->middleware(['auth', 'active', 'role:producer,admin'])->whereNumber('profile')->name('producer.logo');

Route::prefix('miller')->name('miller.')->middleware(['auth', 'active', 'role:miller'])->group(function () {
    Route::get('milling-operations', [MillerMillingOperationController::class, 'index'])->name('milling-operations.index');
    Route::get('milling-operations/{millingOperation}', [MillerMillingOperationController::class, 'show'])->whereNumber('millingOperation')->name('milling-operations.show');
    Route::post('milling-operations/{millingOperation}/confirm-reception', [MillerMillingOperationController::class, 'confirmReception'])->whereNumber('millingOperation')->name('milling-operations.confirm-reception');
    Route::post('milling-operations/{millingOperation}/start', [MillerMillingOperationController::class, 'start'])->whereNumber('millingOperation')->name('milling-operations.start');
    Route::post('milling-operations/{millingOperation}/complete', [MillerMillingOperationController::class, 'complete'])->whereNumber('millingOperation')->name('milling-operations.complete');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'active', 'role:admin', 'can:access-admin'])->group(function () {
    Route::get('producers', [ProducerProfileController::class, 'index'])->name('producers.index');
    Route::get('producers/{profile}', [ProducerProfileController::class, 'show'])->whereNumber('profile')->name('producers.show');
    Route::get('producers/{profile}/edit', [ProducerProfileController::class, 'edit'])->whereNumber('profile')->name('producers.edit');
    Route::patch('producers/{profile}', [ProducerProfileController::class, 'update'])->whereNumber('profile')->name('producers.update');
    Route::get('farms/map', FarmMapController::class)->name('farms.map');
    Route::resource('farms', FarmController::class)->only(['index', 'show', 'edit', 'update'])->whereNumber('farm');
    Route::get('farms/{farm}/activities', [FarmActivityController::class, 'index'])->whereNumber('farm')->name('farms.activities.index');
    Route::get('farms/{farm}/reports', [FarmAdviceReportController::class, 'index'])->whereNumber('farm')->name('farms.reports.index');
    Route::post('farms/{farm}/reports', [FarmAdviceReportController::class, 'store'])->whereNumber('farm')->middleware('throttle:3,1')->name('farms.reports.store');
    Route::get('farms/{farm}/reports/{report}', [FarmAdviceReportController::class, 'show'])->whereNumber(['farm', 'report'])->name('farms.reports.show');
    Route::resource('harvests', HarvestController::class)->only(['index', 'show', 'destroy'])->whereNumber('harvest');
    Route::post('harvests/{harvest}/estimate', [HarvestController::class, 'estimate'])->whereNumber('harvest')->middleware('throttle:5,1')->name('harvests.estimate');
    Route::patch('farms/{farm}/status', [FarmController::class, 'status'])->whereNumber('farm')->name('farms.status');
    Route::post('farms/{farm}/advice', FarmAssistantController::class)->whereNumber('farm')->middleware('throttle:5,1')->name('farms.advice');
});
