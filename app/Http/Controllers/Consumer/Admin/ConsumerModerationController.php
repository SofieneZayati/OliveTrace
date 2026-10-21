<?php

namespace App\Http\Controllers\Consumer\Admin;

use App\Enums\Consumer\ComplaintStatus;
use App\Enums\Consumer\FeedbackStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Consumer\ComplaintModerationRequest;
use App\Models\Consumer\Complaint;
use App\Models\Consumer\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use App\Mail\ComplaintStatusUpdated;
use App\Services\Consumer\ComplaintReplyAssistant;
use App\Services\Consumer\ReviewSummaryGenerator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ConsumerModerationController extends Controller
{
    public function feedbackIndex(Request $request)
    {
        $filters = $request->validate(['status' => ['nullable', Rule::enum(FeedbackStatus::class)]]);
        $feedback = Feedback::with(['consumer', 'product'])->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()->paginate(15);

        return view('consumer.admin.feedback-index', ['feedback' => $feedback]);
    }

    public function feedbackUpdate(Request $request, Feedback $feedback)
    {
        Gate::authorize('moderate', $feedback);
        $data = $request->validate(['status' => ['required', Rule::enum(FeedbackStatus::class)]]);
        $feedback->update(['status' => FeedbackStatus::from($data['status'])]);

        return redirect()->route('admin.consumer.feedback.index')->with('success', 'Review visibility updated.');
    }

    public function complaintsIndex(Request $request)
    {
        $filters = $request->validate(['status' => ['nullable', Rule::enum(ComplaintStatus::class)]]);
        $complaints = Complaint::with(['consumer', 'product'])->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()->paginate(15);

        return view('consumer.admin.complaints-index', ['complaints' => $complaints]);
    }

    public function complaintsShow(Complaint $complaint)
    {
        Gate::authorize('view', $complaint);
        $complaint->load(['consumer', 'product']);

        return view('consumer.admin.complaints-show', ['complaint' => $complaint]);
    }

    public function complaintsUpdate(ComplaintModerationRequest $request, Complaint $complaint)
    {
        $data = $request->validated();
        $status = ComplaintStatus::from($data['status']);
        $complaint->update([
            'status' => $status,
            'admin_response' => $data['admin_response'] ?? null,
            'resolved_at' => $status->isClosed() ? ($complaint->resolved_at ?? now()) : null,
        ]);

        // Feature 6: Send email notification to the consumer.
        try {
            if ($complaint->consumer?->email) {
                Mail::to($complaint->consumer->email)->send(new ComplaintStatusUpdated($complaint, $status->value));
            }
        } catch (\Throwable $e) {
            Log::warning('ComplaintStatusUpdated mail failed', ['complaint' => $complaint->id, 'error' => $e->getMessage()]);
        }

        return redirect()->route('admin.consumer.complaints.show', $complaint)->with('success', 'Complaint '.$status->label().' registered.');
    }

    // Feature 1: Generate an AI draft reply for a complaint.
    public function generateAiReply(Complaint $complaint, ComplaintReplyAssistant $assistant)
    {
        $complaint->load('product');
        $draft = $assistant->generateDraft($complaint);

        if ($draft === null) {
            return response()->json(['error' => 'Le service IA est indisponible. Rédigez la réponse manuellement.'], 503);
        }

        // Cache the draft on the complaint for reference.
        $complaint->update(['ai_draft_reply' => $draft]);

        return response()->json(['draft' => $draft]);
    }

    // Feature 2: Generate an AI summary of all reviews.
    public function generateSummary(ReviewSummaryGenerator $generator)
    {
        $summary = $generator->generate();

        if ($summary === null) {
            return response()->json(['error' => 'Le service IA est indisponible.'], 503);
        }

        return response()->json(['summary' => $summary]);
    }
}
