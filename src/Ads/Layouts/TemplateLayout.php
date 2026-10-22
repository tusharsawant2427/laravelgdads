<?php

namespace Packitifotech\Laragdads\Ads\Layouts;

use GdImage;
use Packitifotech\Laragdads\Ads\AdSpec;
use Packitifotech\Laragdads\Ads\Support\BookMockup;
use Packitifotech\Laragdads\Ads\Support\Canvas;
use Packitifotech\Laragdads\Ads\Support\Color;
use Packitifotech\Laragdads\Ads\Support\ImageLoader;
use Packitifotech\Laragdads\Ads\Support\Typography;
use Packitifotech\Laragdads\Ads\Templates\TemplateLibrary;
use Packitifotech\Laragdads\Ads\Theme;

/**
 * A subject template: paints the artwork, puts the product cover over the template's book (which it covers completely) and
 * the product title and optional tagline in the template's copy zone, in the template's own ink colours. Everything else
 * (logo, benefit icons, "Shop Now") is part of the artwork, so the call-to-action, badge, features and logo of the spec are not used.
 */
final class TemplateLayout extends AbstractLayout
{
    /** Share of the copy zone's height given to the headline when there is a tagline too. */
    private const HEADLINE_SHARE = 0.6;

    /** A cover is scaled up by at most this much to hide the placeholder book (when its proportions differ from the book's). */
    private const MAX_GROW = 1.2;

    public function __construct(private readonly string $template)
    {
    }

    public function render(Canvas $canvas, AdSpec $spec, Theme $theme, Typography $headingFont, Typography $bodyFont): void
    {
        // A cover without a usable colour makes the theme fall back to its default indigo: that is not the book's colour.
        $match = $spec->matchCoverColour && $theme->primary !== Theme::FALLBACK ? $theme->primary : null;
        $name = TemplateLibrary::resolve($this->template, $match);   // the generic template picks its colour variant here
        $template = TemplateLibrary::spec($name);
        $map = TemplateLibrary::paint($name, $canvas, $match, $match === null ? null : $this->buttonColour($theme));

        $this->brand($canvas, $spec, TemplateLibrary::rect($template['logo'], $map), $template['logoDark']);
        if ($template['book'] !== null) {
            $this->product($canvas, $spec, TemplateLibrary::rect($template['book'], $map), $template['ink']);
        }
        if ($template['zone'] === null) {
            return;   // the artwork has its own headline (NEET, MHT-CET, CUET, all entrance exams)
        }
        if (isset($template['taglineZone'])) {
            $this->category($canvas, $spec, $headingFont, $bodyFont, $map, $template);

            return;
        }
        $this->copy($canvas, $spec, $headingFont, $bodyFont, TemplateLibrary::rect($template['zone'], $map), $template);
    }

    /**
     * The category layout: the subject name in capitals on one line (the artwork says "PREPARATION BOOKS" under it), a tagline, and
     * up to four feature labels beside their icons. Unused feature slots stay empty.
     */
    private function category(Canvas $canvas, AdSpec $spec, Typography $headingFont, Typography $bodyFont, array $map, array $template): void
    {
        [$x, $y, $w, $h] = TemplateLibrary::rect($template['zone'], $map);
        $headline = mb_strtoupper($this->printable((string) $spec->headline, $headingFont));
        if ($headline !== '') {
            $fit = $headingFont->fit($headline, $w, $h, $h, 8, 1.0, 1);
            $canvas->lines($fit['lines'], $headingFont, $fit['size'], $x, $y + intdiv($h - $fit['height'], 2), $w, $template['ink'], 'left', $fit['lineHeight']);
        }

        $tagline = $this->printable((string) $spec->subheadline, $bodyFont);
        if ($tagline !== '') {
            [$x, $y, $w, $h] = TemplateLibrary::rect($template['taglineZone'], $map);
            $fit = $bodyFont->fit($tagline, $w, $h, (int) round($h * 0.8), 7, 1.1, 1);
            $canvas->lines($fit['lines'], $bodyFont, $fit['size'], $x, $y + intdiv($h - $fit['height'], 2), $w, $template['tagline'], 'left', $fit['lineHeight']);
        }

        foreach ($template['featureZones'] as $i => $zone) {
            $label = $this->printable((string) ($spec->features[$i] ?? ''), $bodyFont);
            if ($label === '') {
                continue;
            }
            [$x, $y, $w, $h] = TemplateLibrary::rect($zone, $map);
            $fit = $bodyFont->fit($label, $w, $h, (int) round($h * 0.5), 6, 1.1, 2);
            $canvas->lines($fit['lines'], $bodyFont, $fit['size'], $x, $y + intdiv($h - $fit['height'], 2), $w, $template['tagline'], 'left', $fit['lineHeight']);
        }
    }

    /** The button colour: the cover's own hue, saturated and dark enough for the button's white lettering. */
    private function buttonColour(Theme $theme): array
    {
        [$hue, $saturation] = Color::toHsl($theme->primary);

        return Color::fromHsl($hue, max(0.62, min(0.9, $saturation)), 0.42);
    }

    /** The logo, fitted into its slot and anchored to its left edge (so it lines up with the headline); on a white pill over dark artwork. */
    private function brand(Canvas $canvas, AdSpec $spec, array $rect, bool $onDark): void
    {
        [$x, $y, $w, $h] = $rect;
        $logo = ImageLoader::load($spec->logo ?? dirname(__DIR__, 3) . '/resources/logo/target-logo.png');
        $scale = min($w / imagesx($logo), $h / imagesy($logo));
        $lw = max(1, (int) round(imagesx($logo) * $scale));
        $lh = max(1, (int) round(imagesy($logo) * $scale));
        $ly = $y + intdiv($h - $lh, 2);
        if ($onDark) {
            $pad = (int) round($lh * 0.3);
            $canvas->rect($x - $pad, $ly - $pad, $lw + 2 * $pad, $lh + 2 * $pad, [255, 255, 255], 0.96, intdiv($lh + 2 * $pad, 2));
        }
        $canvas->picture($logo, $x, $ly, $lw, $lh, 'contain');
    }

    /**
     * The cover becomes a 3D book as tall (or as wide) as the placeholder book, standing on the same baseline, so the
     * placeholder underneath is hidden whatever the proportions of the cover.
     *
     * @param int[] $rect x, y, width, height of the placeholder book
     */
    private function product(Canvas $canvas, AdSpec $spec, array $rect, array $ink): void
    {
        [$x, $y, $w, $h] = $rect;
        $image = $spec->hasImages() ? $spec->images[0] : $this->plainCover($spec->headline ?? '', $ink);
        $book = BookMockup::make($this->prepare($image, $spec));
        $mw = imagesx($book);
        $mh = imagesy($book);

        $fit = min($w / $mw, $h / $mh);
        $scale = min(max($w / $mw, $h / $mh), $fit * self::MAX_GROW);
        $top = max(2, (int) round($canvas->height() * 0.02));   // a tall cover must not run off the top of the banner
        $scale = min($scale, ($y + $h - $top) / $mh);
        $bw = (int) round($mw * $scale);
        $bh = (int) round($mh * $scale);
        $canvas->sprite($book, $x + intdiv($w, 2), $y + $h - intdiv($bh, 2), $bw, $bh);
    }

    /** Headline (the product title) and tagline, left aligned, auto-sized to the zone. */
    private function copy(Canvas $canvas, AdSpec $spec, Typography $headingFont, Typography $bodyFont, array $zone, array $template): void
    {
        [$x, $y, $w, $h] = $zone;
        $headline = $this->printable((string) $spec->headline, $headingFont);
        $tagline = $this->printable((string) $spec->subheadline, $bodyFont);
        if ($headline === '') {
            return;
        }

        $headH = $tagline === '' ? $h : (int) round($h * self::HEADLINE_SHARE);
        $head = $headingFont->fit($headline, $w, $headH, $headH, 8, 1.0, $tagline === '' ? 3 : 2);
        $top = $y + intdiv($headH - $head['height'], 2);
        $canvas->lines($head['lines'], $headingFont, $head['size'], $x, $top, $w, $template['ink'], 'left', $head['lineHeight']);

        if ($tagline !== '') {
            $tagH = $h - $headH - (int) round($h * 0.04);
            $tag = $bodyFont->fit($tagline, $w, $tagH, (int) round($tagH * 0.62), 7, 1.15, 2);
            $canvas->lines($tag['lines'], $bodyFont, $tag['size'], $x, $y + $h - $tagH + intdiv($tagH - $tag['height'], 2), $w, $template['tagline'], 'left', $tag['lineHeight']);
        }
    }

    /**
     * Drop the characters the font has no glyph for (Devanagari in a Latin-only display font would print as empty boxes) and
     * tidy what is left: "Hindi Sulekh 3 (संयुक्ताक्षर) Book" becomes "Hindi Sulekh 3 Book".
     */
    private function printable(string $text, Typography $font): string
    {
        if (!preg_match('/[^\x00-\x7F]/u', $text)) {
            return trim($text);
        }
        $missing = $font->measure("\u{E000}", 40)[0];   // the width of the font's "no glyph" box
        $kept = '';
        foreach (preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $char) {
            if (ord($char[0]) < 0x80 || $font->measure($char, 40)[0] !== $missing) {
                $kept .= $char;
            }
        }
        $kept = preg_replace('/\(\s*\)|\[\s*\]/u', '', $kept);

        return trim(preg_replace('/\s{2,}/u', ' ', $kept), " \t-–|:,");
    }

    /** A plain white cover carrying the title, for banners made without a product image. */
    private function plainCover(string $title, array $ink): GdImage
    {
        $w = 600;
        $h = 800;
        $image = imagecreatetruecolor($w, $h);
        imagefill($image, 0, 0, imagecolorallocate($image, 250, 250, 248));
        $canvas = Canvas::wrap($image);
        $font = new Typography(dirname(__DIR__, 3) . '/resources/fonts/DejaVuSans-Bold.ttf');
        $fit = $font->fit($title, (int) ($w * 0.8), (int) ($h * 0.5), 90, 24, 1.15, 5);
        $canvas->lines($fit['lines'], $font, $fit['size'], (int) ($w * 0.1), (int) (($h - $fit['height']) / 2), (int) ($w * 0.8), $ink, 'center', $fit['lineHeight']);

        return $canvas->image();
    }
}
