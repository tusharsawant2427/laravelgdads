<?php

namespace Packitifotech\Laragdads\Ads\Layouts;

use Packitifotech\Laragdads\Ads\AdSpec;
use Packitifotech\Laragdads\Ads\Support\Canvas;
use Packitifotech\Laragdads\Ads\Support\Typography;
use Packitifotech\Laragdads\Ads\Theme;

/**
 * The plain layout for square, portrait, story and other non-wide sizes: the product(s) on top, headline, sub-headline,
 * feature list and call-to-action centred below. Packshots (book covers, boxes, bottles) have their white studio
 * background removed and stand freely on the coloured background, front product on top, a second one tilted behind.
 */
final class StackedLayout extends AbstractLayout
{
    public function render(Canvas $canvas, AdSpec $spec, Theme $theme, Typography $headingFont, Typography $bodyFont): void
    {
        $w = $spec->width;
        $h = $spec->height;
        $m = $this->margin($spec);

        $areaX = $m;
        $areaW = $w - 2 * $m;
        $areaH = (int) round($h * ($h / $w > 1.6 ? 0.44 : 0.52)) - $m;
        $areaY = $m;
        $textX = $m + (int) round($m * 0.4);
        $textW = $w - 2 * $m - (int) round($m * 0.8);
        $textY = $areaY + $areaH + (int) round($m * 0.6);
        $textH = $h - $textY - $m;

        if ($spec->hasImages()) {
            $this->products($canvas, $spec, $theme, $areaX, $areaY, $areaW, $areaH);
        }

        if ($spec->logo !== null) {
            $logoH = max(24, (int) round($h * 0.06));
            $this->logo($canvas, $spec, $m * 2, $m * 2, (int) round($textW * 0.4), $logoH, true);
        }

        $this->textColumn($canvas, $spec, $theme, $headingFont, $bodyFont, $textX, $textY, $textW, $textH, 'center', $theme->text);

        $d = max(52, min(190, (int) round(min($areaW, $areaH) * 0.3)));
        $this->badge($canvas, $spec, $theme, $headingFont, $areaX + $areaW - intdiv($d, 2), $areaY + intdiv($d, 2), $d);
    }

    private function products(Canvas $canvas, AdSpec $spec, Theme $theme, int $x, int $y, int $w, int $h): void
    {
        $cx = $x + intdiv($w, 2);
        $cy = $y + intdiv($h, 2);
        $canvas->glow($cx, $cy, (int) round(max($w, $h) * 0.62), [255, 255, 255], 0.22);

        $front = $this->prepare($spec->images[0], $spec);
        $back = isset($spec->images[1]) ? $this->prepare($spec->images[1], $spec) : null;

        if ($back === null) {
            $canvas->sprite($front, $cx, $cy, (int) round($w * 0.86), (int) round($h * 0.94), 2.5);

            return;
        }

        $boxW = (int) round($w * 0.6);
        $boxH = (int) round($h * 0.88);
        $canvas->sprite($back, $x + (int) round($w * 0.34), $cy - (int) round($h * 0.03), $boxW, $boxH, 5);
        $canvas->sprite($front, $x + (int) round($w * 0.64), $cy + (int) round($h * 0.03), $boxW, (int) round($h * 0.94), -3);
    }
}
