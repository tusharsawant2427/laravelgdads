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

    /** The product has the same fixed box on every template (any cover is fitted inside it), as a share of the canvas height. */
    public const BOOK_HEIGHT = 0.94;

    public const BOOK_WIDTH = 0.7;

    /** Gap between the product and the text, as a share of the height. */
    public const BOOK_GAP = 0.14;

    /** Button height as a share of the canvas height. */
    private const CTA_HEIGHT = 0.36;

    /** Gap between the button and the right edge, and between the artwork and the button, as a share of the height. */
    private const CTA_MARGIN = 0.1;

    /**
     * Width of the strip at the right edge that belongs to the button, 0 without one. The button is always flush right and
     * vertically centred, so it sits in the same place on every template; the artwork is painted left of this strip.
     */
    public function reserveRight(AdSpec $spec, Typography $headingFont): int
    {
        return $spec->cta ? $this->ctaWidth($spec, $headingFont) + (int) round($spec->height * self::CTA_MARGIN * 2) : 0;
    }

    private function ctaWidth(AdSpec $spec, Typography $headingFont): int
    {
        $ch = (int) round($spec->height * self::CTA_HEIGHT);
        $label = $headingFont->measure((string) $spec->cta, max(10, (int) round($ch * 0.4)))[0] + (int) round($ch * 0.9);

        return min(max($label, (int) round($spec->height * 0.8)), (int) round($spec->width * 0.3));
    }

    public function render(Canvas $canvas, AdSpec $spec, Theme $theme, Typography $headingFont, Typography $bodyFont): void
    {
        $w = $spec->width;
        $h = $spec->height;
        $reserve = $this->reserveRight($spec, $headingFont);
        [$x0, $x1] = TemplateLibrary::zone($this->template, $w, $h, $reserve);
        $m = (int) round($h * 0.08);
        $ink = $theme->text;

        // Product on the left of the free zone.
        $productW = 0;
        if ($spec->hasImages()) {
            $ph = (int) round($h * self::BOOK_HEIGHT);
            $pw = (int) round($h * self::BOOK_WIDTH);
            $canvas->glow($x0 + intdiv($pw, 2), intdiv($h, 2), (int) round($h * 0.75), [255, 255, 255], 0.38);
            $this->book($canvas, $spec, $x0, (int) round(($h - $ph) / 2), $pw, $ph, 0.0);
            $productW = $pw + (int) round($h * self::BOOK_GAP);
            if ($spec->badge) {
                $d = (int) round($h * 0.42);
                $this->badge($canvas, $spec, $theme, $headingFont, $x0 + $pw - (int) ($d * 0.2), (int) round($h * 0.2), $d);
                $productW += (int) ($d * 0.25);
            }
        }

        // The button: fixed position, flush right, centred vertically.
        if ($spec->cta) {
            $ch = (int) round($h * self::CTA_HEIGHT);
            $cw = $this->ctaWidth($spec, $headingFont);
            $this->cta($canvas, $spec, $theme, $headingFont, $w - (int) round($h * self::CTA_MARGIN) - $cw, (int) round(($h - $ch) / 2), $cw, $ch, 'right');
        }

        $tx = $x0 + $productW;
        $tw = $x1 - $tx;
        $copy = clone $spec;
        $copy->textPanel = false;
        $copy->textShadow = false;
        $copy->textAlign = $spec->textAlign ?? 'left';
        $this->textColumn($canvas, $copy, $theme, $headingFont, $bodyFont, $tx, $m, max(20, $tw), $h - 2 * $m, 'left', $ink, false);
    }
}
