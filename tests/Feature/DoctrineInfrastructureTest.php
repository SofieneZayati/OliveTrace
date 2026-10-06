<?php

namespace Tests\Feature;

use App\Models\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Tests\Fixtures\DoctrineProbe;
use Tests\TestCase;

class DoctrineInfrastructureTest extends TestCase
{
    public function test_business_mapping_scope_excludes_eloquent_users_and_health_check_succeeds(): void
    {
        $this->assertSame([base_path('app/Entities')], config('doctrine.managers.default.paths'));
        $metadata = app(EntityManagerInterface::class)->getMetadataFactory()->getAllMetadata();
        $this->assertNotContains(User::class, array_map(fn ($class) => $class->name, $metadata));
        $this->artisan('olivetrace:check-doctrine')->assertSuccessful();
    }

    public function test_doctrine_can_persist_read_update_and_remove_a_test_only_entity(): void
    {
        $shared = app(EntityManagerInterface::class);
        $configuration = ORMSetup::createAttributeMetadataConfiguration([base_path('tests/Fixtures')], true);
        $manager = new EntityManager($shared->getConnection(), $configuration);
        $metadata = $manager->getClassMetadata(DoctrineProbe::class);
        $schema = new SchemaTool($manager);
        $schema->createSchema([$metadata]);

        try {
            $probe = new DoctrineProbe;
            $probe->value = 'Shared foundation';
            $manager->persist($probe);
            $manager->flush();
            $id = $probe->id;
            $manager->clear();
            $saved = $manager->find(DoctrineProbe::class, $id);
            $this->assertSame('Shared foundation', $saved->value);
            $saved->value = 'Updated foundation';
            $manager->flush();
            $manager->clear();
            $saved = $manager->find(DoctrineProbe::class, $id);
            $this->assertSame('Updated foundation', $saved->value);
            $manager->remove($saved);
            $manager->flush();
            $manager->clear();
            $this->assertNull($manager->find(DoctrineProbe::class, $id));
        } finally {
            $schema->dropSchema([$metadata]);
            $manager->close();
        }
    }
}
