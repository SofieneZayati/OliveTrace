<?php

namespace App\Http\Requests;

use App\Models\Mill;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMillRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::unique(Mill::class, 'user_id')],
            'name' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:100'],
            'extraction_type' => ['required', Rule::in(Mill::EXTRACTION_TYPES)],
            'capacity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'contact' => ['nullable', 'string', 'max:50'],
        ];
    }
}
