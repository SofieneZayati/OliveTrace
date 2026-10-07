<?php

namespace App\Http\Requests\Production;

use App\Enums\FarmingType;
use App\Enums\IrrigationType;
use App\Enums\Role;
use App\Support\ProductionOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FarmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active && in_array($this->user()->role, [Role::Producer, Role::Admin], true);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'governorate' => ['required', Rule::in(ProductionOptions::GOVERNORATES)],
            'delegation' => ['nullable', 'string', 'max:100'],
            'area_ha' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
            'olive_variety' => ['required', 'string', 'max:100'],
            'farming_type' => ['required', Rule::enum(FarmingType::class)],
            'irrigation_type' => ['required', Rule::enum(IrrigationType::class)],
            'gps_lat' => ['nullable', 'required_with:gps_lng', 'numeric', 'decimal:0,7', 'between:-90,90'],
            'gps_lng' => ['nullable', 'required_with:gps_lat', 'numeric', 'decimal:0,7', 'between:-180,180'],
            'description' => ['nullable', 'string', 'max:3000'],
            'is_public' => ['required', 'boolean'],
            'producer_profile_id' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }
}
