<?php

namespace App\Http\Controllers\Consumer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Consumer\ComplaintRequest;
use App\Models\Consumer\Complaint;
use App\Models\Consumer\OilProduct;
use App\Services\Consumer\FeedbackClassifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $complaints = Complaint::where('consumer_user_id', $request->user()->id)->latest()->paginate(10);

        return view('consumer.complaints.index', ['complaints' => $complaints]);
    }

    public function create()
    {
        return view('consumer.complaints.form', ['complaint' => null]);
    }

    public function store(ComplaintRequest $request, FeedbackClassifier $classifier)
    {
        $data = $request->validated();
        if (Schema::hasTable('oil_products')) {
            OilProduct::findOrFail($data['oil_product_id']);
        }
        $analysis = $classifier->classify($data['subject'].' '.$data['description']);

        $complaint = Complaint::create([
            'oil_product_id' => $data['oil_product_id'], 'consumer_user_id' => $request->user()->id,
            'subject' => $data['subject'], 'description' => $data['description'], 'status' => 'open',
            'ai_category' => $analysis['category'], 'ai_sentiment' => $analysis['sentiment'], 'ai_priority' => $analysis['priority'],
        ]);

        return redirect()->route('complaints.show', $complaint)->with('success', 'Your complaint was submitted. You can follow its status here.');
    }

    public function show(Request $request, Complaint $complaint)
    {
        Gate::authorize('view', $complaint);

        return view('consumer.complaints.show', ['complaint' => $complaint]);
    }
}
