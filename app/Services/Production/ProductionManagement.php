<?php

namespace App\Services\Production;

use App\Entities\Production\Farm;
use App\Entities\Production\ProducerProfile;
use App\Enums\FarmingType;
use App\Enums\FarmStatus;
use App\Enums\IrrigationType;
use App\Enums\Role;
use App\Models\User;
use App\Repositories\Production\ProducerProfiles;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProductionManagement
{
    public function __construct(private EntityManagerInterface $manager, private ProducerProfiles $profiles) {}

    public function createProfile(User $actor, array $data, ?UploadedFile $logo = null): ProducerProfile
    {
        abort_unless($actor->is_active && $actor->role === Role::Producer, 403);
        if ($this->profiles->forUser($actor->id)) {
            throw ValidationException::withMessages(['profile' => 'Your producer profile already exists. Edit it instead.']);
        }
        $profile = new ProducerProfile($actor->id, $data['display_name']);

        return $this->saveProfile($profile, $data, $logo);
    }

    public function updateProfile(User $actor, ProducerProfile $profile, array $data, ?UploadedFile $logo = null): ProducerProfile
    {
        Gate::forUser($actor)->authorize('update', $profile);

        return $this->saveProfile($profile, $data, $logo);
    }

    private function saveProfile(ProducerProfile $profile, array $data, ?UploadedFile $logo): ProducerProfile
    {
        $oldLogo = $profile->logoPath;
        $newLogo = null;
        try {
            foreach (['display_name' => 'displayName', 'phone' => 'phone', 'address' => 'address', 'company_name' => 'companyName', 'description' => 'description'] as $field => $property) {
                $profile->{$property} = $data[$field] ?? null;
            }
            $profile->isPublic = (bool) $data['is_public'];
            if (array_key_exists('is_active', $data)) {
                $profile->isActive = (bool) $data['is_active'];
            }
            if ($logo) {
                $newLogo = $logo->store('producer-logos', 'local');
                $profile->logoPath = $newLogo;
            } elseif ($data['remove_logo'] ?? false) {
                $profile->logoPath = null;
            }
            $profile->updatedAt = new DateTimeImmutable;
            $this->manager->persist($profile);
            $this->manager->flush();
        } catch (Throwable $exception) {
            if ($newLogo) {
                Storage::disk('local')->delete($newLogo);
            }
            if ($exception instanceof UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['profile' => 'Your producer profile already exists.']);
            }
            throw $exception;
        }
        if ($oldLogo && $oldLogo !== $profile->logoPath) {
            Storage::disk('local')->delete($oldLogo);
        }

        return $profile;
    }

    public function deleteProfile(User $actor, ProducerProfile $profile): void
    {
        Gate::forUser($actor)->authorize('delete', $profile);
        if (! $profile->farms->isEmpty()) {
            throw ValidationException::withMessages(['profile' => 'Remove unlinked farms first. A profile with farm history must be kept.']);
        }
        try {
            $this->manager->remove($profile);
            $this->manager->flush();
        } catch (ForeignKeyConstraintViolationException) {
            throw ValidationException::withMessages(['profile' => 'This producer profile has linked records and must be kept.']);
        }
        if ($profile->logoPath) {
            Storage::disk('local')->delete($profile->logoPath);
        }
    }

    public function createFarm(User $actor, ProducerProfile $profile, array $data): Farm
    {
        Gate::forUser($actor)->authorize('update', $profile);
        if (! $profile->isActive) {
            throw ValidationException::withMessages(['profile' => 'Your producer profile is disabled. Contact an administrator.']);
        }

        return $this->saveFarm(new Farm($profile), $data);
    }

    public function updateFarm(User $actor, Farm $farm, array $data): Farm
    {
        Gate::forUser($actor)->authorize('update', $farm);

        return $this->saveFarm($farm, $data);
    }

    private function saveFarm(Farm $farm, array $data): Farm
    {
        foreach (['name' => 'name', 'governorate' => 'governorate', 'delegation' => 'delegation', 'olive_variety' => 'oliveVariety', 'description' => 'description'] as $field => $property) {
            $farm->{$property} = $data[$field] ?? null;
        }
        $farm->areaHa = number_format((float) $data['area_ha'], 2, '.', '');
        $farm->farmingType = FarmingType::from($data['farming_type']);
        $farm->irrigationType = IrrigationType::from($data['irrigation_type']);
        $farm->gpsLat = isset($data['gps_lat']) ? number_format((float) $data['gps_lat'], 7, '.', '') : null;
        $farm->gpsLng = isset($data['gps_lng']) ? number_format((float) $data['gps_lng'], 7, '.', '') : null;
        $farm->isPublic = (bool) $data['is_public'];
        $farm->updatedAt = new DateTimeImmutable;
        $this->manager->persist($farm);
        $this->manager->flush();

        return $farm;
    }

    /** Returns true when downstream harvest history requires archival. FK restrictions also guard races. */
    public function deleteFarm(User $actor, Farm $farm): bool
    {
        Gate::forUser($actor)->authorize('delete', $farm);
        $hasHistory = Schema::hasTable('harvests') && $this->manager->getConnection()
            ->fetchOne('SELECT 1 FROM harvests WHERE farm_id = ? LIMIT 1', [$farm->id]);
        if ($hasHistory) {
            $this->archiveFarm($actor, $farm);

            return true;
        }
        try {
            $this->manager->remove($farm);
            $this->manager->flush();
        } catch (ForeignKeyConstraintViolationException) {
            throw ValidationException::withMessages(['farm' => 'This farm has linked history. Use Archive to preserve it.']);
        }

        return false;
    }

    public function archiveFarm(User $actor, Farm $farm): void
    {
        Gate::forUser($actor)->authorize('delete', $farm);
        if ($farm->status === FarmStatus::Disabled) {
            throw ValidationException::withMessages(['farm' => 'An administrator disabled this farm. Its status cannot be changed by the owner.']);
        }
        $farm->status = FarmStatus::Archived;
        $farm->updatedAt = new DateTimeImmutable;
        $this->manager->flush();
    }

    public function moderateFarm(User $actor, Farm $farm, FarmStatus $status): void
    {
        abort_unless($actor->is_active && $actor->isAdmin(), 403);
        $farm->status = $status;
        $farm->updatedAt = new DateTimeImmutable;
        $this->manager->flush();
    }
}
