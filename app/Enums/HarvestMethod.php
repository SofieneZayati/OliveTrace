<?php

namespace App\Enums;

enum HarvestMethod: string
{
    case HandPicking = 'hand_picking';
    case HandShears = 'hand_shears';
    case ManualComb = 'manual_comb';
    case PneumaticComb = 'pneumatic_comb';
    case VibratingPole = 'vibrating_pole';
    case BranchShaker = 'branch_shaker';
    case TrunkShaker = 'trunk_shaker';
    case HarvestPlatform = 'harvest_platform';
    case VacuumHarvester = 'vacuum_harvester';

    public function label(): string
    {
        return match ($this) {
            self::HandPicking => 'By hand',
            self::HandShears => 'Hand shears',
            self::ManualComb => 'Manual comb',
            self::PneumaticComb => 'Pneumatic comb',
            self::VibratingPole => 'Vibrating pole',
            self::BranchShaker => 'Branch shaker',
            self::TrunkShaker => 'Trunk shaker',
            self::HarvestPlatform => 'Harvesting platform',
            self::VacuumHarvester => 'Vacuum harvester',
        };
    }
}
