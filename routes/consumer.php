<?php

use App\Http\Controllers\Consumer\Admin\ConsumerModerationController;
use App\Http\Controllers\Consumer\Admin\SlaDashboardController;
use App\Http\Controllers\Consumer\ComplaintController;
use App\Http\Controllers\Consumer\FeedbackController;
use App\Http\Controllers\Consumer\MyTraceController;
use App\Http\Controllers\Consumer\TraceController;
use App\Http\Controllers\Consumer\TraceSearchController;
use Illuminate\Support\Facades\Route;

// Module 5 (Aymen) — Consumer Traceability, Feedback & Complaints.
Route::get('/trace/{slug}', [TraceController::class, 'show'])->name('trace.show');
Route::get('/trace/{slug}/share', [TraceController::class, 'shareProduct'])->name('trace.share');
Route::get('/api/trace/{slug}/share', [TraceController::class, 'shareCardApi'])->name('trace.share.api');
Route::redirect('/my-complaints', '/complaints')->name('my-complaints');

// Feature 4: Public QR scanner and trace search.
Route::get('/trace', [TraceSearchController::class, 'index'])->name('trace.search');
Route::get('/trace-search', [TraceSearchController::class, 'search'])->name('trace.search.query');

Route::get('/complaints/{complaint}/evidence', [ComplaintController::class, 'evidence'])
    ->middleware(['auth', 'active'])->whereNumber('complaint')->name('complaints.evidence');
Route::get('/purchase-proofs/{purchase}', [MyTraceController::class, 'proof'])
    ->middleware(['auth', 'active'])->whereNumber('purchase')->name('products.proof.show');

Route::middleware(['auth', 'active', 'role:consumer'])->group(function () {
    Route::get('/my-trace', [MyTraceController::class, 'index'])->name('my-trace.index');
    Route::get('/my-trace/notifications', [MyTraceController::class, 'notifications'])->name('my-trace.notifications');
    Route::patch('/my-trace/notifications/read-all', [MyTraceController::class, 'markAllNotificationsRead'])->name('my-trace.notifications.read-all');
    Route::patch('/my-trace/notifications/{notification}', [MyTraceController::class, 'markNotificationRead'])->whereNumber('notification')->name('my-trace.notifications.read');
    Route::post('/products/{product}/proof', [MyTraceController::class, 'uploadProof'])->whereNumber('product')->name('products.proof.upload');
    Route::get('/products/{product}/share', [MyTraceController::class, 'shareProduct'])->whereNumber('product')->name('products.share');
    Route::get('/api/products/{product}/share', [MyTraceController::class, 'shareCardApi'])->whereNumber('product')->name('products.share.api');
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
    Route::get('consumer/sla', [SlaDashboardController::class, 'index'])->name('consumer.sla.index');
    Route::get('consumer/purchase-proofs', [MyTraceController::class, 'proofsIndex'])->name('consumer.proofs.index');
    Route::patch('consumer/purchase-proofs/{purchase}', [MyTraceController::class, 'approveProof'])->whereNumber('purchase')->name('consumer.proofs.approve');
    Route::post('consumer/feedback/{feedback}/ai-response', [SlaDashboardController::class, 'getSuggestedResponse'])->middleware('throttle:6,1')->whereNumber('feedback')->name('consumer.feedback.ai-response');
    Route::post('consumer/feedback/{feedback}/ai-response/multi', [SlaDashboardController::class, 'getMultipleTones'])->middleware('throttle:2,1')->whereNumber('feedback')->name('consumer.feedback.ai-response.multi');
    Route::get('consumer/feedback', [ConsumerModerationController::class, 'feedbackIndex'])->name('consumer.feedback.index');
    Route::patch('consumer/feedback/{feedback}', [ConsumerModerationController::class, 'feedbackUpdate'])->whereNumber('feedback')->name('consumer.feedback.update');
    Route::get('consumer/complaints', [ConsumerModerationController::class, 'complaintsIndex'])->name('consumer.complaints.index');
    Route::get('consumer/complaints/{complaint}', [ConsumerModerationController::class, 'complaintsShow'])->whereNumber('complaint')->name('consumer.complaints.show');
    Route::patch('consumer/complaints/{complaint}', [ConsumerModerationController::class, 'complaintsUpdate'])->whereNumber('complaint')->name('consumer.complaints.update');

    // Feature 1: AI draft reply for complaints.
    Route::post('consumer/complaints/{complaint}/ai-reply', [ConsumerModerationController::class, 'generateAiReply'])->middleware('throttle:6,1')->whereNumber('complaint')->name('consumer.complaints.ai-reply');
    // Feature 2: AI review summary.
    Route::post('consumer/feedback/summary', [ConsumerModerationController::class, 'generateSummary'])->middleware('throttle:6,1')->name('consumer.feedback.summary');
});
