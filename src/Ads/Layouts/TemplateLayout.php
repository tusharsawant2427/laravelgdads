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
        if (TemplateLibrary::isPortrait($spec->width, $spec->height)) {
            $this->renderPortrait($canvas, $spec, $theme, $headingFont, $bodyFont);

            return;
        }
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

    /**
     * Portrait: the template's decorated bands frame the top and bottom; the headline sits in the open middle of the top
     * band, the product below it, then the sub-headline and the button, which is always at the same place above the bottom band.
     */
    private function renderPortrait(Canvas $canvas, AdSpec $spec, Theme $theme, Typography $headingFont, Typography $bodyFont): void
    {
        $w = $spec->width;
        $h = $spec->height;
        $band = TemplateLibrary::band($w);
        $ink = $theme->text;
        $m = (int) round($w * 0.05);

        // Headline in the free middle of the top band.
        [$x0, $x1] = TemplateLibrary::zone($this->template, $w, $band);
        if ($x1 - $x0 < $w * 0.45) {   // never squeeze the headline into less than this
            $mid = intdiv($x0 + $x1, 2);
            [$x0, $x1] = [$mid - (int) ($w * 0.225), $mid + (int) ($w * 0.225)];
        }
        [$x0, $x1] = [$x0 + (int) ($w * 0.02), $x1 - (int) ($w * 0.02)];
        $copy = clone $spec;
        $copy->subheadline = null;
        $copy->textPanel = false;
        $copy->textShadow = false;
        $this->textColumn($canvas, $copy, $theme, $headingFont, $bodyFont, $x0, (int) round($band * 0.04), $x1 - $x0, (int) round($band * 0.92), 'center', $ink, false);

        // Button: fixed above the bottom band.
        $ch = (int) round($w * 0.105);
        $by = $h - $band - $ch - (int) round($w * 0.025);
        if ($spec->cta) {
            $this->cta($canvas, $spec, $theme, $headingFont, $m, $by, $w - 2 * $m, $ch, 'center');
        }

        // Sub-headline above the button.
        $subH = $spec->subheadline ? (int) round($w * 0.12) : 0;
        $sy = $by - (int) round($w * 0.02) - $subH;
        if ($subH > 0) {
            $sub = $bodyFont->fit($spec->subheadline, $w - 2 * $m, $subH, (int) round($w * 0.05), 10, 1.25, 2);
            $canvas->lines($sub['lines'], $bodyFont, $sub['size'], $m, $sy + max(0, intdiv($subH - $sub['height'], 2)), $w - 2 * $m, $ink, 'center', $sub['lineHeight'], null, 0.92);
        }

        // Product between the headline band and the sub-headline: a fixed box, the same on every template.
        if ($spec->hasImages()) {
            $top = $band + (int) round($w * 0.01);
            $ph = min((int) round($w * 0.92), $sy - (int) round($w * 0.01) - $top);   // as big as the space allows
            $pw = (int) round($ph * self::BOOK_WIDTH / self::BOOK_HEIGHT);
            $px = intdiv($w - $pw, 2);
            $canvas->glow($px + intdiv($pw, 2), $top + intdiv($ph, 2), (int) round($ph * 0.75), [255, 255, 255], 0.38);
            $this->book($canvas, $spec, $px, $top, $pw, $ph, 0.0);
            if ($spec->badge) {
                $d = (int) round($ph * 0.4);
                $this->badge($canvas, $spec, $theme, $headingFont, $px + $pw - (int) ($d * 0.35), $top, $d);
            }
        }
    }
}
