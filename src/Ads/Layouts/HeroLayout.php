<?php

namespace Packitifotech\Laragdads\Ads\Layouts;

use Packitifotech\Laragdads\Ads\AdSpec;
use Packitifotech\Laragdads\Ads\Support\Canvas;
use Packitifotech\Laragdads\Ads\Support\Typography;
use Packitifotech\Laragdads\Ads\Theme;

/**
 * Full-bleed photo with a brand-coloured scrim and text on top. Best for lifestyle imagery.
 */
final class HeroLayout extends AbstractLayout
{
    public function render(Canvas $canvas, AdSpec $spec, Theme $theme, Typography $headingFont, Typography $bodyFont): void
    {
        $w = $spec->width;
        $h = $spec->height;
        $m = $this->margin($spec);
        $white = [255, 255, 255];
        $landscape = $spec->aspectRatio() >= 1.3;

        if ($spec->hasImages()) {
            $canvas->picture($spec->images[0], 0, 0, $w, $h, 'cover');
        }

        $scrim = $theme->scrim();
        if ($landscape) {
            $canvas->gradient($scrim, $scrim, 'horizontal', [0, 0, (int) round($w * 0.72), $h], 0.92, 0.0);
            $textX = $m * 2;
            $textW = (int) round($w * 0.5) - $m;
            $textY = $m;
            $textH = $h - 2 * $m;
            $align = 'left';
        } else {
            $top = (int) round($h * 0.38);
            $canvas->gradient($scrim, $scrim, 'vertical', [0, $top, $w, $h - $top], 0.0, 0.94);
            $textX = $m * 2;
            $textW = $w - $m * 4;
            $textY = (int) round($h * 0.58);
            $textH = $h - $textY - $m;
            $align = 'left';
        }

        if ($spec->logo !== null) {
            $this->logo($canvas, $spec, $m * 2, $m * 2, (int) round($w * 0.25), max(22, (int) round($h * 0.08)), true);
        }

        $this->textColumn($canvas, $spec, $theme, $headingFont, $bodyFont, $textX, $textY, $textW, $textH, $align, $white);

        $d = max(48, min(170, (int) round(min($w, $h) * 0.24)));
        $this->badge($canvas, $spec, $theme, $headingFont, $w - $m - intdiv($d, 2), $m + intdiv($d, 2), $d);
    }
}
