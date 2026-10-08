<?php

use App\Http\Controllers\Certification\CertificateController;
use App\Http\Controllers\Certification\CertificateRequestController;
use App\Http\Controllers\Certification\LabAnalysisController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->group(function () {

    // Producer Routes
    Route::middleware('role:producer')->prefix('certification')->name('certification.producer.')->group(function () {
        Route::resource('requests', CertificateRequestController::class)->only(['index', 'create', 'store', 'show']);
    });

    // Laboratory Routes
    Route::middleware('role:laboratory')->prefix('lab')->name('lab.')->group(function () {
        Route::get('requests', [LabAnalysisController::class, 'index'])->name('requests.index');
        Route::get('requests/{certificateRequest}', [LabAnalysisController::class, 'show'])->name('requests.show');
        Route::post('requests/{certificateRequest}/analyze', [LabAnalysisController::class, 'analyze'])->name('requests.analyze');

        // AI Assistant Endpoint
        Route::post('ai-explanation', [LabAnalysisController::class, 'aiExplanation'])->middleware('throttle:5,1')->name('requests.ai-explanation');
    });
});

// Admin & Public (Consumers)
Route::get('/certificates/{certificateNumber}', [CertificateController::class, 'show'])->name('certificates.show');
