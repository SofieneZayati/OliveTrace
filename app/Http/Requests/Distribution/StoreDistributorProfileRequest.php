<?php

namespace App\Http\Requests\Distribution;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;

class StoreDistributorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active && $this->user()->role === Role::Distributor;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:255'],
            // Accepts +216 and international dialing prefixes, with spaces, parentheses and hyphens.
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9 ()\-]{6,30}$/'],
            'region' => ['required', 'string', 'max:100'],
            'user_id' => ['prohibited'],
            'is_active' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'Enter your company name.',
            'company_name.max' => 'The company name may not exceed 150 characters.',
            'address.required' => 'Enter your company address.',
            'address.max' => 'The address may not exceed 255 characters.',
            'phone.required' => 'Enter a contact phone number.',
            'phone.regex' => 'Enter a valid phone number using digits and an optional international prefix.',
            'phone.max' => 'The phone number may not exceed 30 characters.',
            'region.required' => 'Enter your region.',
            'region.max' => 'The region may not exceed 100 characters.',
            'user_id.prohibited' => 'The profile owner is assigned from your account.',
            'is_active.prohibited' => 'Profile activation is managed by an administrator.',
        ];
    }
}
