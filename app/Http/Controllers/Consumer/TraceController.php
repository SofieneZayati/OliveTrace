<?php

namespace App\Http\Controllers\Consumer;

use App\Http\Controllers\Controller;
use App\Services\Consumer\ConsumerTrace;

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
}
