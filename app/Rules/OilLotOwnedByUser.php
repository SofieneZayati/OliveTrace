<?php

namespace App\Rules;

use App\Contracts\OilLotOwnership;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class OilLotOwnedByUser implements ValidationRule
{
    public function __construct(
        private OilLotOwnership $ownership,
        private int $userId,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_numeric($value) && ! $this->ownership->belongsToUser((int) $value, $this->userId)) {
            $fail('You may only use an oil lot that belongs to your producer account.');
        }
    }
}
