<?php

namespace App\Http\Requests\Distribution;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListPublicCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $search = $this->query('search');
        $volume = $this->query('bottle_volume_ml');
        $delivered = $this->query('delivered');
        $sort = $this->query('sort');

        $this->merge([
            'search' => is_string($search) && mb_strlen($search) <= 120 ? trim($search) : null,
            'bottle_volume_ml' => in_array((string) $volume, ['250', '500', '750', '1000'], true) ? (string) $volume : null,
            'delivered' => $delivered === '1' ? '1' : null,
            'sort' => in_array($sort, ['newest', 'name'], true) ? $sort : 'newest',
        ]);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'bottle_volume_ml' => ['nullable', Rule::in(['250', '500', '750', '1000'])],
            'delivered' => ['nullable', Rule::in(['1'])],
            'sort' => ['required', Rule::in(['newest', 'name'])],
        ];
    }
}
