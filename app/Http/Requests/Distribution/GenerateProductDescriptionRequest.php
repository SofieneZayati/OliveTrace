<?php

namespace App\Http\Requests\Distribution;

use App\Enums\Role;
use App\Models\Distribution\OilProduct;
use Illuminate\Foundation\Http\FormRequest;

class GenerateProductDescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $this->user()?->is_active
            && in_array($this->user()->role, [Role::Producer, Role::Admin], true)
            && $product instanceof OilProduct
            && $this->user()->can('update', $product);
    }

    public function rules(): array
    {
        return [];
    }
}
