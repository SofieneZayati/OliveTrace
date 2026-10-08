<?php

namespace App\Http\Requests\Consumer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ComplaintModerationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('access-admin');
    }

    public function rules(): array
    {
        return [
            // Per the team spec, the admin sets in_review / resolved / rejected and adds a response.
            'status' => ['required', Rule::in(['in_review', 'resolved', 'rejected'])],
            'admin_response' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
