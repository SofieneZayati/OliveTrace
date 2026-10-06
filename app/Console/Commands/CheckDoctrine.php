<?php

namespace App\Console\Commands;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaValidator;
use Illuminate\Console\Command;

class CheckDoctrine extends Command
{
    protected $signature = 'olivetrace:check-doctrine';

    protected $description = 'Check Doctrine mappings and its database connection without changing the schema';

    public function handle(EntityManagerInterface $entityManager): int
    {
        $errors = (new SchemaValidator($entityManager))->validateMapping();
        if ($errors !== []) {
            foreach ($errors as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        }

        $entityManager->getConnection()->executeQuery('SELECT 1')->fetchOne();
        $count = count($entityManager->getMetadataFactory()->getAllMetadata());
        $this->info("Doctrine connection and mappings OK ({$count} business entities).");

        return self::SUCCESS;
    }
}
