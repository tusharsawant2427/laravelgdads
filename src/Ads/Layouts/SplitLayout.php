<?php

namespace Packitifotech\Laragdads\Ads\Layouts;

use Packitifotech\Laragdads\Ads\AdSpec;
use Packitifotech\Laragdads\Ads\Support\Canvas;
use Packitifotech\Laragdads\Ads\Support\Typography;
use Packitifotech\Laragdads\Ads\Theme;

/**
 * Landscape ads: an image card on one side, headline / sub / CTA on the other.
 * A second image becomes a small inset card overlapping the first.
 */
final class SplitLayout extends AbstractLayout
{
    public function render(Canvas $canvas, AdSpec $spec, Theme $theme, Typography $headingFont, Typography $bodyFont): void
    {
        $w = $spec->width;
        $h = $spec->height;
        $m = $this->margin($spec);

        if (!$spec->hasImages()) {
            $d = $spec->badge ? (int) round($h * 0.3) : 0;
            $textW = $w - $m * 4 - ($d ? $d + $m : 0);
            $this->textColumn($canvas, $spec, $theme, $headingFont, $bodyFont, $m * 2, $m, $textW, $h - $m * 2, 'left', $theme->text);
            $this->badge($canvas, $spec, $theme, $headingFont, $w - $m * 2 - intdiv($d, 2), intdiv($h, 2), $d);

            return;
        }

        $cardH = $h - 2 * $m;
        $cardW = (int) round($w * 0.4);
        $left = $spec->imageSide !== 'right';
        $cardX = $left ? $m : $w - $m - $cardW;
        $radius = (int) round($m * 0.6);

        $this->card($canvas, $spec, $spec->images[0], $cardX, $m, $cardW, $cardH, $radius);

        if (isset($spec->images[1])) {
            $inW = (int) round($cardW * 0.42);
            $inH = (int) round($inW * 1.05);
            $border = max(3, (int) round($m * 0.2));
            $ix = $left ? $cardX + $cardW - (int) round($inW * 0.55) : $cardX - (int) round($inW * 0.45);
            $iy = $m + $cardH - $inH - (int) round($m * 0.8);
            $canvas->shadow($ix, $iy, $inW + 2 * $border, $inH + 2 * $border, $radius, (int) round($inW * 0.1), 0.4, 6);
            $canvas->rect($ix - $border, $iy - $border, $inW + 2 * $border, $inH + 2 * $border, [255, 255, 255], 1.0, $radius);
            $canvas->picture($spec->images[1], $ix, $iy, $inW, $inH, 'cover', max(2, $radius - $border));
        }

        $textX = $left ? $cardX + $cardW + (int) round($m * 1.8) : $m * 2;
        $textW = $w - $cardW - (int) round($m * 4.2);
        $textY = $m;
        $textH = $cardH;

        if ($spec->logo !== null) {
            $logoH = max(24, (int) round($h * 0.1));
            $this->logo($canvas, $spec, $textX, $textY, (int) round($textW * 0.4), $logoH);
            $textY += $logoH + (int) round($m * 0.6);
            $textH -= $logoH + (int) round($m * 0.6);
        }

        $this->textColumn($canvas, $spec, $theme, $headingFont, $bodyFont, $textX, $textY, $textW, $textH, 'left', $theme->text);

        $d = max(48, min(150, (int) round(min($cardW, $cardH) * 0.3)));
        $this->badge($canvas, $spec, $theme, $headingFont, $left ? $cardX + $cardW - intdiv($d, 3) : $cardX + intdiv($d, 3), $m + intdiv($d, 3), $d);
    }
}
