<?php

namespace App\Http\Requests\Production;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class ProducerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active && in_array($this->user()->role, [Role::Producer, Role::Admin], true);
    }

    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9 ()\-]{6,30}$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:3000'],
            'logo' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max('2mb')->dimensions(
                Rule::dimensions()->maxWidth(3000)->maxHeight(3000)
            )],
            'remove_logo' => ['sometimes', 'boolean'],
            'is_public' => ['required', 'boolean'],
            'is_active' => $this->user()->isAdmin() ? ['required', 'boolean'] : ['prohibited'],
            'user_id' => ['prohibited'],
        ];
    }
}
