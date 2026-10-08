<?php

namespace App\Http\Requests\Production;

use App\Models\Production\Harvest;
use App\Models\Production\MillRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MillRequestRequest extends FormRequest
{
    public function prepareForValidation(): void
    {
        $millId = $this->input('mill_id');
        $this->merge(['mill_id' => in_array($millId, [null, '', 'external'], true) ? null : (int) $millId]);
    }

    public function authorize(): bool
    {
        $harvest = Harvest::find($this->route('harvest'));
        abort_unless($harvest, 404);

        return $this->user()?->can('create', [MillRequest::class, $harvest]) ?? false;
    }

    public function rules(): array
    {
        return [
            'mill_id' => ['nullable', 'integer', Rule::exists('mills', 'id')->whereNull('deleted_at')],
            'external_mill_name' => ['nullable', 'string', 'max:150'],
            'requested_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_date' => ['prohibited'],
            'quantity_kg' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
            'message' => ['nullable', 'string', 'max:2000'],
            'harvest_id' => ['prohibited'],
            'status' => ['prohibited'],
            'response_message' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $millId = $this->input('mill_id');
            $external = trim((string) $this->input('external_mill_name', ''));
            if ($millId === null && $external === '') {
                $validator->errors()->add('mill_id', 'Select a registered mill or type the name of an external mill.');
            }
            if ($millId !== null && $external !== '') {
                $validator->errors()->add('external_mill_name', 'Leave the external mill name empty when a registered mill is selected.');
            }
        });
    }
}
