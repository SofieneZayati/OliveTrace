<?php

namespace App\Services\Distribution;

use App\Models\Distribution\OilProduct;
use Illuminate\Support\Str;
use RuntimeException;

class ProductSlugGenerator
{
    public function generate(string $name): string
    {
        $base = Str::limit(Str::slug($name), 40, '');
        $base = trim($base, '-') ?: 'product';

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $slug = $base.'-'.Str::lower(Str::random(8));

            if (! OilProduct::query()->where('slug', $slug)->exists()) {
                return $slug;
            }
        }

        throw new RuntimeException('Unable to generate a unique product slug after 10 attempts.');
    }
}
