<?php

namespace App\Http\Requests\Production;

use App\Enums\FarmActivityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FarmActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active && $this->user()->role->value === 'producer';
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::enum(FarmActivityType::class)],
            'planned_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before_or_equal:'.today()->addYear()->toDateString()],
            'notes' => ['nullable', 'string', 'max:2000'],
            'farm_id' => ['prohibited'], 'status' => ['prohibited'],
            'completed_on' => ['prohibited'], 'water_m3' => ['prohibited'],
            'advice_report_id' => ['prohibited'], 'recommendation_index' => ['prohibited'],
        ];
    }
}
