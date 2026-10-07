<?php

namespace App\Http\Controllers\Distribution;

use App\Http\Controllers\Controller;
use App\Http\Requests\Distribution\StoreDistributorProfileRequest;
use App\Http\Requests\Distribution\UpdateDistributorProfileRequest;
use App\Models\Distribution\DistributorProfile;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DistributorProfileController extends Controller
{
    use AuthorizesRequests;

    public function show(Request $request): View|RedirectResponse
    {
        $profile = DistributorProfile::query()->where('user_id', $request->user()->id)->first();

        if ($profile === null) {
            $this->authorize('create', DistributorProfile::class);

            return view('distribution.distributor-profile.form', ['profile' => null]);
        }

        $this->authorize('view', $profile);

        return view('distribution.distributor-profile.show', compact('profile'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('create', DistributorProfile::class);
        if (DistributorProfile::query()->where('user_id', $request->user()->id)->exists()) {
            return redirect()->route('distributor.profile.show');
        }

        return view('distribution.distributor-profile.form', ['profile' => null]);
    }

    public function store(StoreDistributorProfileRequest $request): RedirectResponse
    {
        $this->authorize('create', DistributorProfile::class);
        $profile = new DistributorProfile($request->validated());
        $profile->user_id = $request->user()->id;
        $profile->save();

        return redirect()->route('distributor.profile.show')->with('success', 'Distributor profile created.');
    }

    public function edit(Request $request): View|RedirectResponse
    {
        $profile = DistributorProfile::query()->where('user_id', $request->user()->id)->first();
        if ($profile === null) {
            return redirect()->route('distributor.profile.create')->with('error', 'Create your distributor profile first.');
        }
        $this->authorize('update', $profile);

        return view('distribution.distributor-profile.form', compact('profile'));
    }

    public function update(UpdateDistributorProfileRequest $request): RedirectResponse
    {
        $profile = DistributorProfile::query()->where('user_id', $request->user()->id)->firstOrFail();
        $this->authorize('update', $profile);
        $profile->update($request->validated());

        return redirect()->route('distributor.profile.show')->with('success', 'Distributor profile updated.');
    }
}
