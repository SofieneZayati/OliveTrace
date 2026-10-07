<?php

namespace Database\Factories\Production;

use App\Entities\Production\ProducerProfile;
use Faker\Factory;

/** Doctrine entity factory: make first, then persist/flush using the entity manager. */
class ProducerProfileFactory
{
    public function make(int $userId, array $overrides = []): ProducerProfile
    {
        $faker = Factory::create();
        $profile = new ProducerProfile($userId, $faker->company());
        $profile->companyName = $profile->displayName;
        $profile->description = 'An olive-growing family sharing the origin of its harvests.';
        foreach ($overrides as $property => $value) {
            $profile->{$property} = $value;
        }

        return $profile;
    }
}
