<?php

namespace App\Services\Production;

use App\Enums\FarmStatus;
use App\Enums\Role;
use App\Models\Production\Farm;
use App\Models\Production\ProducerProfile;
use App\Models\User;
use App\Repositories\Production\ProducerProfiles;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProductionManagement
{
    public function __construct(private ProducerProfiles $profiles) {}

    public function createProfile(User $actor, array $data, ?UploadedFile $logo = null): ProducerProfile
    {
        abort_unless($actor->is_active && $actor->role === Role::Producer, 403);
        if ($this->profiles->forUser($actor->id)) {
            throw ValidationException::withMessages(['profile' => 'Your producer profile already exists. Edit it instead.']);
        }
        $profile = new ProducerProfile;
        $profile->user()->associate($actor);

        return $this->saveProfile($profile, $data, $logo);
    }

    public function updateProfile(User $actor, ProducerProfile $profile, array $data, ?UploadedFile $logo = null): ProducerProfile
    {
        Gate::forUser($actor)->authorize('update', $profile);

        return $this->saveProfile($profile, $data, $logo);
    }

    private function saveProfile(ProducerProfile $profile, array $data, ?UploadedFile $logo): ProducerProfile
    {
        $oldLogo = $profile->logo_path;
        $newLogo = null;
        try {
            foreach (['display_name', 'phone', 'address', 'company_name', 'description'] as $field) {
                $profile->{$field} = $data[$field] ?? null;
            }
            $profile->is_public = (bool) $data['is_public'];
            if (array_key_exists('is_active', $data)) {
                $profile->is_active = (bool) $data['is_active'];
            }
            if ($logo) {
                $newLogo = $logo->store('producer-logos', 'local');
                $profile->logo_path = $newLogo;
            } elseif ($data['remove_logo'] ?? false) {
                $profile->logo_path = null;
            }
            $profile->save();
        } catch (Throwable $exception) {
            if ($newLogo) {
                Storage::disk('local')->delete($newLogo);
            }
            if ($exception instanceof UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['profile' => 'Your producer profile already exists.']);
            }
            throw $exception;
        }
        if ($oldLogo && $oldLogo !== $profile->logo_path) {
            Storage::disk('local')->delete($oldLogo);
        }

        return $profile;
    }

    public function deleteProfile(User $actor, ProducerProfile $profile): void
    {
        Gate::forUser($actor)->authorize('delete', $profile);
        // Query current records rather than a previously loaded relationship collection.
        if ($profile->farms()->exists()) {
            throw ValidationException::withMessages(['profile' => 'Remove unlinked farms first. A profile with farm history must be kept.']);
        }
        try {
            $profile->delete();
        } catch (QueryException $exception) {
            if (! $this->isForeignKeyViolation($exception)) {
                throw $exception;
            }
            throw ValidationException::withMessages(['profile' => 'This producer profile has linked records and must be kept.']);
        }
        if ($profile->logo_path) {
            Storage::disk('local')->delete($profile->logo_path);
        }
    }

    public function createFarm(User $actor, ProducerProfile $profile, array $data): Farm
    {
        Gate::forUser($actor)->authorize('update', $profile);
        if (! $profile->is_active) {
            throw ValidationException::withMessages(['profile' => 'Your producer profile is disabled. Contact an administrator.']);
        }
        $farm = new Farm;
        $farm->producerProfile()->associate($profile);

        return $this->saveFarm($farm, $data);
    }

    public function updateFarm(User $actor, Farm $farm, array $data): Farm
    {
        Gate::forUser($actor)->authorize('update', $farm);

        return $this->saveFarm($farm, $data);
    }

    private function saveFarm(Farm $farm, array $data): Farm
    {
        foreach (['name', 'governorate', 'delegation', 'olive_variety', 'description', 'farming_type', 'irrigation_type', 'gps_lat', 'gps_lng'] as $field) {
            $farm->{$field} = $data[$field] ?? null;
        }
        $farm->area_ha = number_format((float) $data['area_ha'], 2, '.', '');
        $farm->is_public = (bool) $data['is_public'];
        $farm->save();

        return $farm;
    }

    /** Returns true when downstream harvest history requires archival. FK restrictions also guard races. */
    public function deleteFarm(User $actor, Farm $farm): bool
    {
        Gate::forUser($actor)->authorize('delete', $farm);

        try {
            return DB::transaction(function () use ($actor, $farm) {
                $record = Farm::whereKey($farm->id)->lockForUpdate()->firstOrFail();
                if (Schema::hasTable('harvests') && DB::table('harvests')->where('farm_id', $record->id)->exists()) {
                    $this->archiveFarm($actor, $record);

                    return true;
                }
                $record->delete();

                return false;
            });
        } catch (QueryException $exception) {
            if (! $this->isForeignKeyViolation($exception)) {
                throw $exception;
            }
            throw ValidationException::withMessages(['farm' => 'This farm has linked history. Use Archive to preserve it.']);
        }
    }

    public function archiveFarm(User $actor, Farm $farm): void
    {
        Gate::forUser($actor)->authorize('delete', $farm);
        if ($farm->status === FarmStatus::Disabled) {
            throw ValidationException::withMessages(['farm' => 'An administrator disabled this farm. Its status cannot be changed by the owner.']);
        }
        $farm->status = FarmStatus::Archived;
        $farm->save();
    }

    public function moderateFarm(User $actor, Farm $farm, FarmStatus $status): void
    {
        abort_unless($actor->is_active && $actor->isAdmin(), 403);
        $farm->status = $status;
        $farm->save();
    }

    private function isForeignKeyViolation(QueryException $exception): bool
    {
        return ($exception->errorInfo[0] ?? null) === '23503'
            || in_array($exception->errorInfo[1] ?? null, [1451, 1452], true)
            || str_contains($exception->getMessage(), 'FOREIGN KEY constraint failed');
    }
}
