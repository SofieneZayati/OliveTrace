<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMillRequest;
use App\Models\Mill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MillController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'extraction_type' => ['nullable', Rule::in(Mill::EXTRACTION_TYPES)],
        ]);

        $mills = Mill::query()
            ->with('user:id,name,email')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('region', 'like', '%'.$search.'%')
                ->orWhere('contact', 'like', '%'.$search.'%')
                ->orWhereHas('user', fn ($query) => $query->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'))))
            ->when($filters['region'] ?? null, fn ($query, $region) => $query->where('region', $region))
            ->when($filters['extraction_type'] ?? null, fn ($query, $type) => $query->where('extraction_type', $type))
            ->orderBy('name')->orderBy('id')->paginate(15)->withQueryString();

        return view('admin.mills.index', [
            'mills' => $mills,
            'regions' => Mill::query()->select('region')->distinct()->orderBy('region')->pluck('region'),
            'extractionTypes' => Mill::EXTRACTION_TYPES,
        ]);
    }

    public function show(Mill $mill): View
    {
        return view('admin.mills.show', [
            'mill' => $mill->load('user:id,name,email,role'),
            'extractionTypes' => Mill::EXTRACTION_TYPES,
        ]);
    }

    public function edit(Mill $mill): View
    {
        return view('admin.mills.edit', [
            'mill' => $mill->load('user:id,name,email'),
            'extractionTypes' => Mill::EXTRACTION_TYPES,
        ]);
    }

    public function update(UpdateMillRequest $request, Mill $mill): RedirectResponse
    {
        $mill->fill($request->validated());
        $mill->save();

        return redirect()->route('admin.mills.show', $mill)->with('success', 'Mill updated.');
    }

    public function destroy(Request $request, Mill $mill): RedirectResponse
    {
        DB::transaction(function () use ($mill) {
            $mill = Mill::whereKey($mill->id)->lockForUpdate()->firstOrFail();
            $owner = $mill->user()->lockForUpdate()->first();

            $mill->delete();

            if ($owner && $owner->hasRole(Role::Miller)) {
                $owner->role = Role::Consumer;
                $owner->save();
            }
        });

        return redirect()->route('admin.mills.index')->with('success', 'Mill deleted. Its owner account went back to consumer.');
    }
}
