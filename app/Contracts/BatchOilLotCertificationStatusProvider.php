<?php

namespace App\Contracts;

interface BatchOilLotCertificationStatusProvider extends OilLotCertificationStatusProvider
{
    /** @param list<int> $oilLotIds @return array<int, string> */
    public function statusesFor(array $oilLotIds): array;
}
