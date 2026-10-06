<?php

namespace Tests\Fixtures;

use Doctrine\ORM\Mapping as ORM;

// Test-only infrastructure fixture. It is never scanned by the application.
#[ORM\Entity]
#[ORM\Table(name: 'doctrine_probes')]
class DoctrineProbe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    #[ORM\Column(length: 64)]
    public string $value;
}
