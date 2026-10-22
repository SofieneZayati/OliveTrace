<?php

namespace App\Http\Controllers\Consumer;

use App\Http\Controllers\Controller;
use App\Services\Consumer\ConsumerTrace;
use App\Services\Consumer\MyTraceService;

class TraceController extends Controller
{
    public function shareProduct(string $slug, ConsumerTrace $trace, MyTraceService $service)
    {
        $timeline = $trace->forSlug($slug);
        abort_unless($timeline, 404);

        return view('consumer.share-card', ['share' => $service->generateShareCard($timeline['product']->id, $slug)]);
    }

    public function shareCardApi(string $slug, ConsumerTrace $trace, MyTraceService $service)
    {
        $timeline = $trace->forSlug($slug);
        abort_unless($timeline, 404);

        return response()->json($service->generateShareCard($timeline['product']->id, $slug));
    }

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
}
