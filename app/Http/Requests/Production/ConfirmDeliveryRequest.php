<?php

namespace App\Http\Requests\Production;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delivered_at' => ['nullable', 'date', 'before_or_equal:now'],
        ];
    }
}
