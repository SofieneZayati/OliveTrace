<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use App\Models\Mill;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') ?? false;
    }

    public function rules(): array
    {
        $target = $this->route('user');
        $targetId = $target instanceof User ? $target->id : null;

        $millMissing = $this->input('role') === Role::Miller->value
            && ! Mill::withTrashed()->where('user_id', $targetId)->exists();

        $millField = $millMissing ? 'required' : 'nullable';

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->route('user'))],
            'role' => ['required', Rule::enum(Role::class)],
            'is_active' => ['required', 'boolean'],
            'mill' => [$millMissing ? 'required' : 'sometimes', 'array'],
            'mill.name' => [$millField, 'string', 'max:255'],
            'mill.region' => [$millField, 'string', 'max:100'],
            'mill.extraction_type' => [$millField, Rule::in(Mill::EXTRACTION_TYPES)],
            'mill.capacity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'mill.contact' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'mill.required' => 'A miller account needs its mill details.',
            'mill.name.required' => 'The mill name is required.',
            'mill.region.required' => 'The mill region is required.',
            'mill.extraction_type.required' => 'The extraction type is required.',
        ];
    }
}
