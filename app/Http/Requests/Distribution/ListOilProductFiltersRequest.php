<?php

namespace App\Http\Requests\Distribution;

use App\Enums\OilProductPublicStatus;
use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListOilProductFiltersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active
            && in_array($this->user()->role, [Role::Producer, Role::Admin], true);
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(OilProductPublicStatus::class)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'owner_id' => [
                'nullable', 'integer', 'min:1',
                Rule::exists('users', 'id')->where('role', Role::Producer->value),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.enum' => 'Choose a valid product status filter.',
            'date_from.date' => 'Enter a valid start date.',
            'date_to.date' => 'Enter a valid end date.',
            'date_to.after_or_equal' => 'The end date must be on or after the start date.',
            'owner_id.integer' => 'The owner filter must be a valid user ID.',
            'owner_id.min' => 'The owner filter must be a positive user ID.',
            'owner_id.exists' => 'Choose an existing producer account for the owner filter.',
        ];
    }
}
