<?php

namespace App\Http\Requests\Consumer;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;

class ComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active && $this->user()->role === Role::Consumer;
    }

    public function rules(): array
    {
        return [
            'oil_product_id' => ['required', 'integer', 'min:1'],
            'subject' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:3000'],
            'consumer_user_id' => ['prohibited'],
            'status' => ['prohibited'],
            'admin_response' => ['prohibited'],
        ];
    }
}
