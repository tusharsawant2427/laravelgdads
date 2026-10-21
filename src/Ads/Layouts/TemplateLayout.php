<?php

namespace Packitifotech\Laragdads\Ads\Layouts;

use Packitifotech\Laragdads\Ads\AdSpec;
use Packitifotech\Laragdads\Ads\Support\Canvas;
use Packitifotech\Laragdads\Ads\Support\Typography;
use Packitifotech\Laragdads\Ads\Templates\TemplateLibrary;
use Packitifotech\Laragdads\Ads\Theme;

/**
 * Content for the illustrated template banners: the free middle of the template holds the product (a 3D book for
 * packshots, otherwise the whole image in a card), the headline and sub-headline in the template's ink colour, and the
 * call-to-action button on the right. The decorations on the edges are drawn by TemplateLibrary before this runs.
 */
final class TemplateLayout extends AbstractLayout
{
    public function __construct(private readonly string $template)
    {
    }

    public function render(Canvas $canvas, AdSpec $spec, Theme $theme, Typography $headingFont, Typography $bodyFont): void
    {
        $w = $spec->width;
        $h = $spec->height;
        [$x0, $x1] = TemplateLibrary::zone($this->template, $w, $h);
        $m = (int) round($h * 0.08);
        $ink = $theme->text;

        // Product on the left of the free zone.
        $productW = 0;
        if ($spec->hasImages()) {
            $ph = (int) round($h * 0.88);
            $pw = (int) round($ph * 0.74);
            $this->book($canvas, $spec, $x0, (int) round(($h - $ph) / 2), $pw, $ph, 0.0);
            $productW = $pw + (int) round($h * 0.14);
            if ($spec->badge) {
                $d = (int) round($h * 0.42);
                $this->badge($canvas, $spec, $theme, $headingFont, $x0 + $pw - (int) ($d * 0.2), (int) round($h * 0.2), $d);
                $productW += (int) ($d * 0.25);
            }
        }

        // Button at the right end of the zone, text between.
        $ctaW = 0;
        if ($spec->cta) {
            $ch = (int) round($h * 0.36);
            $probe = $headingFont->measure($spec->cta, max(10, (int) round($ch * 0.4)))[0] + (int) round($ch * 0.9);
            $ctaW = min($probe, (int) (($x1 - $x0) * 0.32));
            $this->cta($canvas, $spec, $theme, $headingFont, $x1 - $ctaW, (int) round(($h - $ch) / 2), $ctaW, $ch, 'right');
            $ctaW += (int) round($h * 0.18);
        }

        $tx = $x0 + $productW;
        $tw = $x1 - $ctaW - $tx;
        $copy = clone $spec;
        $copy->textPanel = false;
        $copy->textShadow = false;
        $copy->textAlign = $spec->textAlign ?? 'left';
        $this->textColumn($canvas, $copy, $theme, $headingFont, $bodyFont, $tx, $m, $tw, $h - 2 * $m, 'left', $ink, false);
    }
}
