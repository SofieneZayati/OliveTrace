<?php

namespace App\Services\Distribution;

use App\Models\Distribution\OilProduct;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use UnexpectedValueException;

class ProductQrCode
{
    public function svg(OilProduct $product): string
    {
        $renderer = new ImageRenderer(new RendererStyle(320, 4), new SvgImageBackEnd);
        $writer = new Writer($renderer);

        $svg = $writer->writeString(
            app(ProductTraceUrl::class)->forProduct($product),
            ecLevel: ErrorCorrectionLevel::M(),
        );

        if (str_starts_with($svg, '<?xml')) {
            $declarationEnd = strpos($svg, '?>');
            if ($declarationEnd === false) {
                throw new UnexpectedValueException('The QR renderer returned an invalid SVG document.');
            }

            $svg = ltrim(substr($svg, $declarationEnd + 2));
        }

        return $svg;
    }
}
