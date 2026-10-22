<?php

namespace App\Http\Requests\Production;

use App\Enums\Production\OliveCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ConfirmReceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'received_quantity_kg' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'olive_condition' => ['required', new Enum(OliveCondition::class)],
            'delivery_notes' => ['nullable', 'string', 'max:2000'],
            'discrepancy_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
