<?php

namespace App\Http\Requests\Distribution;

use App\Enums\OilProductPublicStatus;
use App\Enums\Role;
use App\Models\Distribution\OilProduct;
use App\Rules\OilLotExists;
use App\Rules\OilLotOwnedByUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class UpdateOilProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active
            && in_array($this->user()->role, [Role::Producer, Role::Admin], true);
    }

    public function rules(): array
    {
        $lotOwnershipRules = $this->user()?->role === Role::Producer
            ? [app()->make(OilLotOwnedByUser::class, ['userId' => (int) $this->user()->id])]
            : [];

        return [
            'name' => ['required', 'string', 'max:120'],
            'brand' => ['required', 'string', 'max:120'],
            'bottle_volume_ml' => ['required', 'integer', 'between:100,5000'],
            'packaging_date' => ['required', 'date', 'before_or_equal:today'],
            'oil_lot_id' => array_merge(['required', 'integer', app(OilLotExists::class)], $lotOwnershipRules),
            'image' => ['nullable', File::image()->types(['jpg', 'png', 'webp'])->max('2mb')],
            'public_status' => ['sometimes', Rule::enum(OilProductPublicStatus::class)],
            'slug' => ['exclude'],
            'created_by_user_id' => ['prohibited'],
            'archived_at' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $product = $this->routeProduct();
            if ($product
                && $this->has('oil_lot_id')
                && (int) $this->input('oil_lot_id') !== (int) $product->oil_lot_id
                && $product->shipments()->exists()) {
                $validator->errors()->add('oil_lot_id', 'The oil lot cannot be changed after the product has shipments.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Enter a product name.',
            'name.max' => 'The product name may not exceed 120 characters.',
            'brand.required' => 'Enter a brand name.',
            'brand.max' => 'The brand may not exceed 120 characters.',
            'bottle_volume_ml.required' => 'Enter the bottle volume.',
            'bottle_volume_ml.integer' => 'The bottle volume must be a whole number of millilitres.',
            'bottle_volume_ml.between' => 'The bottle volume must be between 100 and 5,000 ml.',
            'packaging_date.required' => 'Choose a packaging date.',
            'packaging_date.date' => 'Enter a valid packaging date.',
            'packaging_date.before_or_equal' => 'The packaging date cannot be in the future.',
            'oil_lot_id.required' => 'Select an oil lot.',
            'oil_lot_id.integer' => 'Select a valid oil lot.',
            'image.image' => 'The product image must be a valid image.',
            'image.mimes' => 'The product image must be a JPG, PNG, or WebP file.',
            'image.max' => 'The product image may not exceed 2 MB.',
            'public_status.enum' => 'Choose a valid product visibility status.',
            'created_by_user_id.prohibited' => 'The product owner cannot be changed.',
            'archived_at.prohibited' => 'Products must be archived through the archive action.',
        ];
    }

    private function routeProduct(): ?OilProduct
    {
        $product = $this->route('oilProduct') ?? $this->route('product');

        if ($product instanceof OilProduct) {
            return $product;
        }

        return is_numeric($product) ? OilProduct::find((int) $product) : null;
    }
}
