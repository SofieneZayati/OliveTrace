<?php

namespace App\Http\Requests\Production;

use Illuminate\Foundation\Http\FormRequest;

class AcknowledgeDiscrepancyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'acknowledge' => ['required', 'accepted'],
        ];
    }
}
