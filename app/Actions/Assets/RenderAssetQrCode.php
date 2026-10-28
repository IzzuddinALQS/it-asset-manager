<?php

namespace App\Actions\Assets;

use App\Models\Asset;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Renders an inline SVG QR code that opens the asset's detail page when scanned.
 */
class RenderAssetQrCode
{
    public function handle(Asset $asset, int $size = 160): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 0), new SvgImageBackEnd));

        $svg = $writer->writeString(route('assets.show', $asset));

        // Drop the XML prolog so the markup can be embedded directly in HTML.
        return trim((string) preg_replace('/^<\?xml[^>]*\?>/', '', $svg));
    }
}
