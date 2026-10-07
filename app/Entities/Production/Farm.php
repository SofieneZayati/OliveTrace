<?php

namespace App\Entities\Production;

use App\Enums\FarmingType;
use App\Enums\FarmStatus;
use App\Enums\IrrigationType;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'farms')]
#[ORM\Index(name: 'farms_origin_index', columns: ['governorate', 'status'])]
class Farm
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ProducerProfile::class, inversedBy: 'farms')]
    #[ORM\JoinColumn(name: 'producer_profile_id', nullable: false, onDelete: 'RESTRICT')]
    public ProducerProfile $producerProfile;

    #[ORM\Column(length: 150)]
    public string $name;

    #[ORM\Column(length: 50)]
    public string $governorate;

    #[ORM\Column(length: 100, nullable: true)]
    public ?string $delegation = null;

    #[ORM\Column(name: 'area_ha', type: 'decimal', precision: 10, scale: 2)]
    public string $areaHa;

    #[ORM\Column(name: 'olive_variety', length: 100)]
    public string $oliveVariety;

    #[ORM\Column(name: 'farming_type', length: 20, enumType: FarmingType::class)]
    public FarmingType $farmingType;

    #[ORM\Column(name: 'irrigation_type', length: 20, enumType: IrrigationType::class)]
    public IrrigationType $irrigationType;

    #[ORM\Column(name: 'gps_lat', type: 'decimal', precision: 10, scale: 7, nullable: true)]
    public ?string $gpsLat = null;

    #[ORM\Column(name: 'gps_lng', type: 'decimal', precision: 10, scale: 7, nullable: true)]
    public ?string $gpsLng = null;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $description = null;

    #[ORM\Column(length: 20, enumType: FarmStatus::class)]
    public FarmStatus $status = FarmStatus::Active;

    #[ORM\Column(name: 'is_public', type: 'boolean')]
    public bool $isPublic = false;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    public DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    public DateTimeImmutable $updatedAt;

    public function __construct(ProducerProfile $profile)
    {
        $this->producerProfile = $profile;
        $profile->farms->add($this);
        $this->createdAt = $this->updatedAt = new DateTimeImmutable;
    }

    /** Safe origin fields only; controlled farming values are declarations, not certification. */
    public function publicOrigin(): array
    {
        return [
            'farm_id' => $this->id,
            'name' => $this->name,
            'producer' => $this->producerProfile->displayName,
            'governorate' => $this->governorate,
            'delegation' => $this->delegation,
            'area_ha' => $this->areaHa,
            'olive_variety' => $this->oliveVariety,
            'farming_type' => $this->farmingType->label(),
            'irrigation_type' => $this->irrigationType->label(),
        ];
    }
}
