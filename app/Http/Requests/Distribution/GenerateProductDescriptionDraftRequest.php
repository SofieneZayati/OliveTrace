<?php

namespace App\Http\Requests\Distribution;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;

class GenerateProductDescriptionDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active && $this->user()->role === Role::Producer;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'brand' => ['required', 'string', 'max:120'],
            'bottle_volume_ml' => ['required', 'integer', 'between:100,5000'],
            'packaging_date' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
