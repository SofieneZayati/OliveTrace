<?php

namespace App\Http\Requests;

use App\Models\Mill;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMillRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $mill = $this->route('mill') ?? $user?->mill;

        return $user instanceof User && $mill instanceof Mill && $user->can('update', $mill);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:100'],
            'extraction_type' => ['required', Rule::in(Mill::EXTRACTION_TYPES)],
            'capacity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'contact' => ['nullable', 'string', 'max:50'],
        ];
    }
}
