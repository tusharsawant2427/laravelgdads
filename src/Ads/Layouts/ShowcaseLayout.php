<?php

namespace Packitifotech\Laragdads\Ads\Layouts;

use Packitifotech\Laragdads\Ads\AdSpec;
use Packitifotech\Laragdads\Ads\Support\Canvas;
use Packitifotech\Laragdads\Ads\Support\Typography;
use Packitifotech\Laragdads\Ads\Theme;

/**
 * Ultra-wide strips and leaderboards: images flank the left and right edges, the message sits between.
 * On short banners the CTA moves beside the text instead of below it.
 */
final class ShowcaseLayout extends AbstractLayout
{
    public function render(Canvas $canvas, AdSpec $spec, Theme $theme, Typography $headingFont, Typography $bodyFont): void
    {
        $w = $spec->width;
        $h = $spec->height;
        $m = max(4, (int) round($h * 0.08));
        $cardH = $h - 2 * $m;
        $cardW = min((int) round($cardH * 0.82), (int) round($w * 0.2));
        $radius = max(2, (int) round($m * 0.7));

        $left = $spec->images[0] ?? null;
        $right = $spec->images[1] ?? null;

        if ($left !== null) {
            $this->card($canvas, $spec, $left, $m, $m, $cardW, $cardH, $radius);
        }
        if ($right !== null) {
            $this->card($canvas, $spec, $right, $w - $m - $cardW, $m, $cardW, $cardH, $radius);
        }

        $x0 = $left !== null ? $m * 2 + $cardW : $m * 2;
        $badgeD = ($spec->badge && $h >= 100) ? max(36, min((int) round($h * 0.42), (int) round($cardW * 0.8))) : 0;
        if ($badgeD && $left !== null) {
            // The sticker straddles the left card's edge; start the text clear of it.
            $x0 = $m + $cardW + intdiv($badgeD, 2) + $m;
        }
        $x1 = $right !== null ? $w - $m * 2 - $cardW : $w - $m * 2;
        $areaW = $x1 - $x0;
        $short = $h < 130;

        $ctaW = 0;
        if ($spec->cta && $short) {
            $ctaH = max(20, (int) round($h * 0.46));
            $probe = $headingFont->measure($spec->cta, max(10, (int) round($ctaH * 0.4)))[0] + (int) round($ctaH * 0.9);
            // Only keep the button if the message still gets most of the width.
            if ($areaW - $probe - $m * 2 > $areaW * 0.55) {
                $ctaW = $probe;
                $this->cta($canvas, $spec, $theme, $headingFont, $x1 - $ctaW, $m + intdiv($cardH - $ctaH, 2), $ctaW, $ctaH, 'right');
            }
        }

        $textW = $areaW - ($ctaW ? $ctaW + $m * 2 : 0);
        $this->textColumn(
            $canvas,
            $spec,
            $theme,
            $headingFont,
            $bodyFont,
            $x0,
            $m,
            $textW,
            $cardH,
            $short ? 'left' : 'center',
            $theme->text,
            !$short
        );

        if ($badgeD && $left !== null) {
            $this->badge($canvas, $spec, $theme, $headingFont, $m + $cardW, $m + intdiv($badgeD, 2), $badgeD);
        }
    }
}
