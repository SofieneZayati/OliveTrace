<?php

use App\Http\Controllers\Consumer\Admin\ConsumerModerationController;
use App\Http\Controllers\Consumer\ComplaintController;
use App\Http\Controllers\Consumer\FeedbackController;
use App\Http\Controllers\Consumer\TraceController;
use Illuminate\Support\Facades\Route;

// Module 5 (Aymen) — Consumer Traceability, Feedback & Complaints.
Route::get('/trace/{slug}', [TraceController::class, 'show'])->name('trace.show');
Route::redirect('/my-complaints', '/complaints')->name('my-complaints');

Route::middleware(['auth', 'active', 'role:consumer'])->group(function () {
    Route::post('/products/{product}/feedback', [FeedbackController::class, 'store'])->whereNumber('product')->name('feedback.store');
    Route::get('/feedback/{feedback}/edit', [FeedbackController::class, 'edit'])->whereNumber('feedback')->name('feedback.edit');
    Route::patch('/feedback/{feedback}', [FeedbackController::class, 'update'])->whereNumber('feedback')->name('feedback.update');
    Route::delete('/feedback/{feedback}', [FeedbackController::class, 'destroy'])->whereNumber('feedback')->name('feedback.destroy');

    Route::get('/complaints', [ComplaintController::class, 'index'])->name('complaints.index');
    Route::get('/complaints/create', [ComplaintController::class, 'create'])->name('complaints.create');
    Route::post('/complaints', [ComplaintController::class, 'store'])->name('complaints.store');
    Route::get('/complaints/{complaint}', [ComplaintController::class, 'show'])->whereNumber('complaint')->name('complaints.show');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'active', 'role:admin', 'can:access-admin'])->group(function () {
    Route::get('consumer/feedback', [ConsumerModerationController::class, 'feedbackIndex'])->name('consumer.feedback.index');
    Route::patch('consumer/feedback/{feedback}', [ConsumerModerationController::class, 'feedbackUpdate'])->whereNumber('feedback')->name('consumer.feedback.update');
    Route::get('consumer/complaints', [ConsumerModerationController::class, 'complaintsIndex'])->name('consumer.complaints.index');
    Route::get('consumer/complaints/{complaint}', [ConsumerModerationController::class, 'complaintsShow'])->whereNumber('complaint')->name('consumer.complaints.show');
    Route::patch('consumer/complaints/{complaint}', [ConsumerModerationController::class, 'complaintsUpdate'])->whereNumber('complaint')->name('consumer.complaints.update');
});
