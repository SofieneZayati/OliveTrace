<?php

namespace App\Http\Requests\Distribution;

use App\Enums\Role;
use App\Enums\ShipmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListShipmentFiltersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active
            && in_array($this->user()->role, [Role::Distributor, Role::Admin], true);
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(ShipmentStatus::class)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.enum' => 'Choose a valid shipment status filter.',
            'date_from.date' => 'Enter a valid start date.',
            'date_to.date' => 'Enter a valid end date.',
            'date_to.after_or_equal' => 'The end date must be on or after the start date.',
        ];
    }
}
