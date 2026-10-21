<?php

namespace App\Http\Controllers\Consumer;

use App\Http\Controllers\Controller;
use App\Services\Consumer\ConsumerTrace;
use App\Services\Consumer\MyTraceService;

class TraceController extends Controller
{
    public function show(string $slug, ConsumerTrace $trace)
    {
        $timeline = $trace->forSlug($slug);

        if (! $timeline) {
            return response()->view('consumer.trace.missing', ['slug' => $slug], 404)
                ->header('Cache-Control', 'no-store');
        }

        return response()->view('consumer.trace.show', ['timeline' => $timeline])
            ->header('Cache-Control', 'no-store');
    }

    public function shareProduct(string $slug, MyTraceService $traceService)
    {
        $timeline = app(ConsumerTrace::class)->forSlug($slug);

        if (! $timeline) {
            abort(404);
        }

        $shareData = app(MyTraceService::class)->generateShareCard($timeline['product']->id, $slug);

        return view('consumer.share-card', ['share' => $shareData]);
    }

    public function shareCardApi(string $slug, MyTraceService $traceService)
    {
        $timeline = app(ConsumerTrace::class)->forSlug($slug);

        if (! $timeline) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $shareData = app(MyTraceService::class)->generateShareCard($timeline['product']->id, $slug);

        return response()->json($shareData);
    }
}
