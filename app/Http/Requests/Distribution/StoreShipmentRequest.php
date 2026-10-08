<?php

namespace App\Http\Requests\Distribution;

use App\Enums\Role;
use App\Enums\ShipmentStatus;
use App\Enums\TransportType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active && $this->user()->role === Role::Distributor;
    }

    public function rules(): array
    {
        return [
            'oil_product_id' => [
                'required', 'integer',
                Rule::exists('oil_products', 'id')->whereNull('archived_at'),
            ],
            'departure_location' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255', 'different:departure_location'],
            'departure_date' => ['required', 'date'],
            'arrival_date' => ['nullable', 'date', 'after_or_equal:departure_date'],
            'distance_km' => ['required', 'numeric', 'gt:0', 'max:20000'],
            'transport_type' => ['required', Rule::enum(TransportType::class)],
            'status' => ['sometimes', Rule::enum(ShipmentStatus::class)],
            'co2_estimate' => ['exclude'],
            'distributor_profile_id' => ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('status') === ShipmentStatus::Delivered->value && ! $this->filled('arrival_date')) {
                    $validator->errors()->add('arrival_date', 'Enter an arrival date when marking a shipment as delivered.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'oil_product_id.required' => 'Select a product for this shipment.',
            'oil_product_id.exists' => 'Choose an existing product that has not been archived.',
            'departure_location.required' => 'Enter the shipment departure location.',
            'destination.required' => 'Enter the shipment destination.',
            'destination.different' => 'The destination must differ from the departure location.',
            'departure_date.required' => 'Choose a departure date.',
            'departure_date.date' => 'Enter a valid departure date.',
            'arrival_date.date' => 'Enter a valid arrival date.',
            'arrival_date.after_or_equal' => 'The arrival date must be on or after the departure date.',
            'distance_km.required' => 'Enter the shipment distance.',
            'distance_km.numeric' => 'The distance must be a number.',
            'distance_km.gt' => 'The distance must be greater than zero.',
            'distance_km.max' => 'The distance may not exceed 20,000 km.',
            'transport_type.required' => 'Choose a transport type.',
            'transport_type.enum' => 'Choose a valid transport type.',
            'status.enum' => 'Choose a valid shipment status.',
            'distributor_profile_id.prohibited' => 'The distributor profile is assigned from your account.',
        ];
    }
}
