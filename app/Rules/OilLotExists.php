<?php

namespace App\Rules;

use App\Contracts\OilProductEligibility;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class OilLotExists implements ValidationRule
{
    public function __construct(private OilProductEligibility $eligibility) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value) || ! $this->eligibility->isEligible((int) $value)) {
            $fail('The selected oil lot does not exist or cannot be used for a product.');
        }
    }
}
