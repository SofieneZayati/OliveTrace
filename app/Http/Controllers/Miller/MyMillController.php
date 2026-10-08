<?php

namespace App\Http\Controllers\Miller;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMillRequest;
use App\Models\Mill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyMillController extends Controller
{
    public function show(Request $request): View
    {
        $mill = $this->millFor($request);

        return view('miller.show', ['mill' => $mill, 'extractionTypes' => Mill::EXTRACTION_TYPES]);
    }

    public function edit(Request $request): View
    {
        $mill = $this->millFor($request);

        return view('miller.edit', ['mill' => $mill, 'extractionTypes' => Mill::EXTRACTION_TYPES]);
    }

    public function update(UpdateMillRequest $request): RedirectResponse
    {
        $mill = $this->millFor($request);
        $mill->fill($request->validated());
        $mill->save();

        return redirect()->route('mill.show')->with('success', 'Your mill has been updated.');
    }

    private function millFor(Request $request): Mill
    {
        $user = $request->user();
        $mill = $user?->mill;

        abort_unless($mill, 404);
        abort_unless($user->can('view', $mill), 403);

        return $mill;
    }
}
