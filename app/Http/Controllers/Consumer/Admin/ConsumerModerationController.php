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

        return redirect()->route('admin.consumer.complaints.show', $complaint)->with('success', 'Complaint '.$status->label().' registered.');
    }
}
