<?php

namespace App\Services;

use App\Models\Distribution\OilProduct;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class ProductDescriptionGenerator
{
    public function generate(OilProduct $product): string
    {
        $fallback = $this->fallback($product);
        $key = (string) config('services.product_description.key');
        $model = (string) config('services.product_description.model');
        $endpoint = (string) config('services.product_description.endpoint');

        if ($key === '' || $model === '' || $endpoint === '') {
            return $fallback;
        }

        $prompt = $this->prompt($product);

        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->post($endpoint.'/'.$model.':generateContent?key='.urlencode($key), [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'temperature' => 0.4,
                        'maxOutputTokens' => 180,
                    ],
                ])
                ->throw();
        } catch (ConnectionException|RequestException) {
            return $fallback;
        }

        $text = trim((string) data_get($response->json(), 'candidates.0.content.parts.0.text'));
        $wordCount = count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY));

        return $wordCount >= 60 && $wordCount <= 90 ? $text : $fallback;
    }

    /** @param array{name: string, brand: string, bottle_volume_ml: int|string, packaging_date: string} $data */
    public function generateFromData(array $data): string
    {
        $product = new OilProduct([
            'name' => $data['name'],
            'brand' => $data['brand'],
            'bottle_volume_ml' => (int) $data['bottle_volume_ml'],
            'packaging_date' => Carbon::parse($data['packaging_date']),
        ]);

        return $this->generate($product);
    }

    private function prompt(OilProduct $product): string
    {
        return sprintf(
            "Rédige une description chaleureuse en français de 60 à 90 mots pour ce produit d'huile d'olive. Utilise uniquement ces données réelles : nom : %s ; marque : %s ; volume : %s ml ; date de conditionnement : %s. N'invente aucun fait et n'ajoute aucune certification, origine, récompense, propriété gustative précise ou allégation de santé qui ne figure pas dans ces données. Ne mentionne pas d'information absente. Retourne uniquement le paragraphe final.",
            $product->name,
            $product->brand,
            $product->bottle_volume_ml,
            $product->packaging_date?->format('d/m/Y'),
        );
    }

    private function fallback(OilProduct $product): string
    {
        return sprintf(
            '%s, proposé sous la marque %s, est une huile d’olive présentée dans un format de %s ml. Conditionné le %s, ce produit est décrit à partir des informations enregistrées dans OliveTrace. Cette présentation claire permet de découvrir son identité et son format en toute simplicité. Consultez les informations de traçabilité disponibles pour suivre son parcours et conserver une lecture fidèle des données déclarées.',
            $product->name,
            $product->brand,
            $product->bottle_volume_ml,
            $product->packaging_date?->format('d/m/Y'),
        );
    }
}
