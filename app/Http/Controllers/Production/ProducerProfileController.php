<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Http\Requests\Production\ProducerProfileRequest;
use App\Repositories\Production\ProducerProfiles;
use App\Services\Production\ProductionManagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProducerProfileController extends Controller
{
    public function __construct(private ProducerProfiles $profiles) {}

    public function index(Request $request)
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'active' => ['nullable', Rule::in(['0', '1'])], 'page' => ['sometimes', 'integer', 'min:1']]);

        return view('production.profiles.index', ['profiles' => $this->profiles->paginate($filters, (int) ($filters['page'] ?? 1))]);
    }

    public function create(Request $request)
    {
        if ($this->profiles->forUser($request->user()->id)) {
            return redirect()->route('producer.profile.show');
        }

        return view('production.profiles.form', ['profile' => null, 'admin' => false]);
    }

    public function store(ProducerProfileRequest $request, ProductionManagement $management)
    {
        $management->createProfile($request->user(), $request->validated(), $request->file('logo'));

        return redirect()->route('producer.profile.show')->with('success', 'Producer profile created. You can now add your farms.');
    }

    private function resolve(Request $request, ?int $profile)
    {
        return $profile !== null ? $this->profiles->find($profile) : $this->profiles->forUser($request->user()->id);
    }

    public function show(Request $request, ?int $profile = null)
    {
        $record = $this->resolve($request, $profile);
        if (! $record) {
            return redirect()->route('producer.profile.create');
        }
        Gate::authorize('view', $record);

        return view('production.profiles.show', ['profile' => $record, 'admin' => $request->routeIs('admin.*')]);
    }

    public function edit(Request $request, ?int $profile = null)
    {
        $record = $this->resolve($request, $profile);
        if (! $record) {
            return redirect()->route('producer.profile.create');
        }
        Gate::authorize('update', $record);

        return view('production.profiles.form', ['profile' => $record, 'admin' => $request->routeIs('admin.*')]);
    }

    public function update(ProducerProfileRequest $request, ProductionManagement $management, ?int $profile = null)
    {
        $record = $this->resolve($request, $profile) ?? abort(404);
        $management->updateProfile($request->user(), $record, $request->validated(), $request->file('logo'));

        return redirect()->route($request->routeIs('admin.*') ? 'admin.producers.show' : 'producer.profile.show', $request->routeIs('admin.*') ? $record->id : [])
            ->with('success', 'Producer profile updated.');
    }

    public function destroy(Request $request, ProductionManagement $management)
    {
        $record = $this->profiles->forUser($request->user()->id) ?? abort(404);
        $management->deleteProfile($request->user(), $record);

        return redirect()->route('producer.profile.create')->with('success', 'Producer profile deleted. Your user account is unchanged.');
    }

    public function logo(Request $request, int $profile)
    {
        $record = $this->profiles->find($profile);
        Gate::authorize('view', $record);
        abort_unless($record->logoPath && Storage::disk('local')->exists($record->logoPath), 404);

        return response()->file(Storage::disk('local')->path($record->logoPath), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
