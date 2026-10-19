<?php

namespace Packitifotech\Laragdads\Ads\Layouts;

use Packitifotech\Laragdads\Ads\AdSpec;
use Packitifotech\Laragdads\Ads\Support\Canvas;
use Packitifotech\Laragdads\Ads\Support\Typography;
use Packitifotech\Laragdads\Ads\Theme;

/**
 * Square, portrait and story ads: image card on top, centred text and CTA below.
 */
final class StackedLayout extends AbstractLayout
{
    public function render(Canvas $canvas, AdSpec $spec, Theme $theme, Typography $headingFont, Typography $bodyFont): void
    {
        $w = $spec->width;
        $h = $spec->height;
        $m = $this->margin($spec);
        $radius = (int) round($m * 0.7);

        $cardBottom = $m;
        if ($spec->hasImages()) {
            $cardH = (int) round($h * ($h / $w > 1.6 ? 0.42 : 0.5)) - $m;
            $cardH = max(40, $cardH);
            $this->card($canvas, $spec, $spec->images[0], $m, $m, $w - 2 * $m, $cardH, $radius);
            $cardBottom = $m + $cardH;

            if ($spec->logo !== null) {
                $logoH = max(22, (int) round($cardH * 0.12));
                $this->logo($canvas, $spec, $m * 2, $m * 2, (int) round($w * 0.3), $logoH, true);
            }

            $d = max(46, min(190, (int) round(min($w - 2 * $m, $cardH) * 0.26)));
            $this->badge($canvas, $spec, $theme, $headingFont, $w - $m - intdiv($d, 3), $m + intdiv($d, 3), $d);
        } elseif ($spec->logo !== null) {
            $logoH = max(22, (int) round($h * 0.08));
            $this->logo($canvas, $spec, $m, $m, (int) round($w * 0.4), $logoH);
            $cardBottom = $m + $logoH;
        }

        $textY = $cardBottom + (int) round($m * 0.8);
        $this->textColumn(
            $canvas,
            $spec,
            $theme,
            $headingFont,
            $bodyFont,
            $m + (int) round($m * 0.4),
            $textY,
            $w - 2 * $m - (int) round($m * 0.8),
            $h - $textY - $m,
            'center',
            $theme->text
        );
    }
}
