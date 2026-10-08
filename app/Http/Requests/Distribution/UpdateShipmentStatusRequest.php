<?php

namespace App\Http\Requests\Distribution;

use App\Enums\Role;
use App\Enums\ShipmentStatus;
use App\Models\Distribution\Shipment;
use App\Services\Distribution\ShipmentStatusTransition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateShipmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active
            && in_array($this->user()->role, [Role::Distributor, Role::Admin], true);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ShipmentStatus::class)],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $shipment = $this->routeShipment();
                $status = ShipmentStatus::tryFrom((string) $this->input('status'));

                if ($shipment !== null && $status !== null
                    && ! app(ShipmentStatusTransition::class)->allows($shipment->status, $status)) {
                    $validator->errors()->add('status', 'The shipment status cannot transition from its current status to the selected status.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Choose the new shipment status.',
            'status.enum' => 'Choose a valid shipment status.',
        ];
    }

    private function routeShipment(): ?Shipment
    {
        $shipment = $this->route('shipment');

        if ($shipment instanceof Shipment) {
            return $shipment;
        }

        return is_numeric($shipment) ? Shipment::find((int) $shipment) : null;
    }
}
