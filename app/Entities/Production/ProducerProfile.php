<?php

namespace App\Entities\Production;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'producer_profiles')]
class ProducerProfile
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    public ?int $id = null;

    #[ORM\Column(name: 'user_id', type: 'bigint', unique: true, options: ['unsigned' => true])]
    public int $userId;

    #[ORM\Column(name: 'display_name', length: 120)]
    public string $displayName;

    #[ORM\Column(length: 30, nullable: true)]
    public ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $address = null;

    #[ORM\Column(name: 'company_name', length: 150, nullable: true)]
    public ?string $companyName = null;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $description = null;

    #[ORM\Column(name: 'logo_path', length: 255, nullable: true)]
    public ?string $logoPath = null;

    #[ORM\Column(name: 'is_active', type: 'boolean')]
    public bool $isActive = true;

    #[ORM\Column(name: 'is_public', type: 'boolean')]
    public bool $isPublic = false;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    public DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    public DateTimeImmutable $updatedAt;

    /** @var Collection<int, Farm> */
    #[ORM\OneToMany(targetEntity: Farm::class, mappedBy: 'producerProfile')]
    #[ORM\OrderBy(['name' => 'ASC', 'id' => 'ASC'])]
    public Collection $farms;

    public function __construct(int $userId, string $displayName)
    {
        $this->userId = $userId;
        $this->displayName = $displayName;
        $this->farms = new ArrayCollection;
        $this->createdAt = $this->updatedAt = new DateTimeImmutable;
    }
}
