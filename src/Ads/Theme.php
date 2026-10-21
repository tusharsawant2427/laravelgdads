<?php

namespace Packitifotech\Laragdads\Ads;

use ColorThief\ColorThief;
use GdImage;
use Packitifotech\Laragdads\Ads\Support\ImageLoader;
use Packitifotech\Laragdads\Ads\Support\Color;

/**
 * The colour system of one ad. Everything is derived from a single brand/image colour so the
 * result stays harmonious, and text/button colours are chosen for WCAG-readable contrast.
 */
final class Theme
{
    public const FALLBACK = [79, 70, 229];

    /**
     * @param array $primary   brand colour (as extracted / supplied)
     * @param array $secondary second background colour
     * @param array $accent    call-to-action / badge colour
     * @param array $backgroundFrom background gradient start (contrast-checked against $text)
     * @param array $backgroundTo   background gradient end
     * @param array $text      headline colour that reads on the background
     */
    public function __construct(
        public readonly array $primary,
        public readonly array $secondary,
        public readonly array $accent,
        public readonly array $backgroundFrom,
        public readonly array $backgroundTo,
        public readonly array $text,
    ) {
    }

    /** Pick colours from the dominant, most colourful tones of an image. */
    public static function fromImage(string|GdImage $image): self
    {
        return self::fromImages([$image]);
    }

    /**
     * Pick colours from several images (e.g. front and back cover). Earlier images weigh more, so the
     * front cover decides the brand colour and the back cover only fills in supporting tones.
     *
     * @param array<string|GdImage> $images
     */
    public static function fromImages(array $images): self
    {
        // Every palette colour with its real share of the cover's pixels (white margins excluded).
        $entries = [];
        foreach (array_values($images) as $n => $image) {
            try {
                $colors = ColorThief::getPalette($image, 6, 10) ?: [];
                $shares = $colors === [] ? [] : self::coverage(ImageLoader::load($image), $colors);
            } catch (\Throwable) {
                continue;
            }
            $weight = $n === 0 ? 1.0 : 0.5;   // the front cover decides the brand colour
            foreach ($colors as $i => $c) {
                $entries[] = ['c' => $c, 'w' => $shares[$i] * $weight];
            }
        }

        if ($entries === []) {
            return self::fromColors(self::FALLBACK);
        }

        // Brand colours sit a little below mid lightness: light pastels and near-black score low.
        $score = static function (array $c): float {
            [, $s, $l] = Color::toHsl($c);

            return $s * (1 - min(1.0, abs($l - 0.42) * 1.6));
        };

        // A hue is as important as everything of similar hue on the cover put together, so the cover's
        // main colour wins over a small, brightly saturated banner.
        $ranked = [];
        foreach ($entries as $e) {
            $cluster = 0.0;
            foreach ($entries as $o) {
                if (Color::saturation($o['c']) >= 0.3 && Color::hueDistance($e['c'], $o['c']) < 35) {
                    $cluster += $o['w'];
                }
            }
            $ranked[] = ['c' => $e['c'], 's' => $score($e['c']) * $cluster];
        }
        usort($ranked, fn ($a, $b) => $b['s'] <=> $a['s']);
        $palette = array_column($ranked, 'c');

        $primary = $palette[0];
        if ($score($primary) < 0.08) {
            $primary = self::FALLBACK;
        }

        $secondary = null;
        $accent = null;
        foreach (array_slice($palette, 1) as $c) {
            // A neighbouring hue gives a calm gradient; a far-away one (purple -> orange) goes muddy, so it becomes the accent instead.
            if ($secondary === null && $score($c) > 0.12 && Color::hueDistance($primary, $c) >= 20 && Color::hueDistance($primary, $c) <= 45) {
                $secondary = $c;
            } elseif ($accent === null && Color::saturation($c) >= 0.5 && Color::hueDistance($primary, $c) >= 45) {
                $accent = $c;
            }
        }

        return self::fromColors($primary, $secondary, $accent);
    }

    /**
     * @param array|string      $primary   [r,g,b] or '#hex'
     * @param array|string|null $secondary derived from $primary when null
     * @param array|string|null $accent    derived (complementary, punchy) when null
     */
    public static function fromColors(array|string $primary, array|string|null $secondary = null, array|string|null $accent = null): self
    {
        $primary = Color::normalize($primary);
        [$h, $s, $l] = Color::toHsl($primary);

        $secondary = $secondary !== null
            ? Color::normalize($secondary)
            : Color::fromHsl($h + (($h < 75 || $h > 330) ? -22 : 25), min(1.0, max($s, 0.5)), max(0.2, $l * 0.85));
        $accent = $accent !== null
            ? Color::normalize($accent)
            : Color::fromHsl($h + 180, max(0.75, min(1.0, $s + 0.2)), 0.55);

        // Light brand colours get dark text, everything else white; the background is then
        // pushed far enough from the text colour to guarantee readability (WCAG AA, 4.5:1).
        $isLight = Color::luminance($primary) > 0.55;
        $text = $isLight ? [17, 24, 39] : [255, 255, 255];
        $from = self::ensureContrast($primary, $text, $isLight);
        $to = self::ensureContrast($secondary, $text, $isLight);

        // The CTA/badge must stand out from the background: shift its lightness away from it.
        $accent = self::ensureAccentVisible($accent, $from, $to, $isLight);

        return new self($primary, $secondary, $accent, $from, $to, $text);
    }

    /** A saturated, light version of the accent: reads as "gold" on dark backgrounds. */
    public function gold(): array
    {
        [$h, $s, $l] = Color::toHsl($this->accent);

        return Color::fromHsl($h, max($s, 0.85), max(0.58, min($l, 0.68)));
    }

    /**
     * Share of the image covered by each palette colour (nearest-colour assignment on a small copy).
     * Transparent and near-white pixels (studio backgrounds, margins) are ignored.
     *
     * @param  array<int, array{0:int,1:int,2:int}> $palette
     * @return float[] same keys as $palette, summing to 1
     */
    private static function coverage(GdImage $image, array $palette): array
    {
        $small = imagescale($image, 64, -1, IMG_BILINEAR_FIXED) ?: $image;
        $w = imagesx($small);
        $h = imagesy($small);
        $counts = array_fill_keys(array_keys($palette), 0);
        $total = 0;

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $px = imagecolorat($small, $x, $y);
                $r = ($px >> 16) & 255;
                $g = ($px >> 8) & 255;
                $b = $px & 255;
                if ((($px >> 24) & 0x7F) > 40 || min($r, $g, $b) >= 235) {
                    continue;
                }
                $best = null;
                $bestD = PHP_INT_MAX;
                foreach ($palette as $i => [$pr, $pg, $pb]) {
                    $d = ($r - $pr) ** 2 + ($g - $pg) ** 2 + ($b - $pb) ** 2;
                    if ($d < $bestD) {
                        [$best, $bestD] = [$i, $d];
                    }
                }
                $counts[$best]++;
                $total++;
            }
        }

        return array_map(fn ($n) => $total > 0 ? $n / $total : 1 / count($palette), $counts);
    }

    public function ctaText(): array
    {
        return Color::textOn($this->accent);
    }

    /** Colour for overlays on photos (scrims): a deep shade of the brand colour. */
    public function scrim(): array
    {
        return Color::darken($this->primary, 0.62);
    }

    /** Darken/lighten in HSL (keeps the hue vivid) until the background clears the text at 4.5:1. */
    private static function ensureContrast(array $bg, array $text, bool $lightBackground): array
    {
        [$h, $s, $l] = Color::toHsl($bg);
        for ($i = 0; $i < 30 && Color::contrast($bg, $text) < 4.5; $i++) {
            $l = $lightBackground ? min(0.97, $l + 0.025) : max(0.05, $l - 0.025);
            $bg = Color::fromHsl($h, $s, $l);
        }

        return $bg;
    }

    private static function ensureAccentVisible(array $accent, array $from, array $to, bool $lightBackground): array
    {
        $worst = fn (array $c) => min(Color::contrast($c, $from), Color::contrast($c, $to));
        [$h, $s, $l] = Color::toHsl($accent);
        for ($i = 0; $i < 20 && $worst($accent) < 3.0; $i++) {
            $l = $lightBackground ? max(0.05, $l - 0.04) : min(0.9, $l + 0.04);
            $accent = Color::fromHsl($h, $s, $l);
        }

        return $worst($accent) >= 3.0 ? $accent : ($lightBackground ? [17, 24, 39] : [255, 255, 255]);
    }
}
