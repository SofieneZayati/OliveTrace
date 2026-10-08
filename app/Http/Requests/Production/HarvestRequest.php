<?php

namespace App\Http\Requests\Production;

use App\Enums\HarvestMethod;
use App\Enums\HarvestStatus;
use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HarvestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active && in_array($this->user()->role, [Role::Producer, Role::Admin], true);
    }

    public function rules(): array
    {
        return [
            'farm_id' => ['required', 'integer', Rule::exists('farms', 'id')],
            'harvest_date' => ['required', 'date', 'before_or_equal:today'],
            'expected_end_date' => ['nullable', 'date', 'after_or_equal:harvest_date'],
            'method' => ['required', Rule::enum(HarvestMethod::class)],
            'quantity_kg' => ['nullable', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
            'status' => ['required', Rule::in(HarvestStatus::PRODUCER_SETTABLE)],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
