<?php

namespace App\Http\Requests\Consumer;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;

class FeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active && $this->user()->role === Role::Consumer;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'oil_product_id' => ['prohibited'],
            'consumer_user_id' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }
}
