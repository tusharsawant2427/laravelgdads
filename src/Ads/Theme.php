<?php

namespace Packitifotech\Laragdads\Ads;

use ColorThief\ColorThief;
use GdImage;
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
        try {
            $palette = ColorThief::getPalette($image, 6, 10) ?: [];
        } catch (\Throwable) {
            $palette = [];
        }

        if ($palette === []) {
            return self::fromColors(self::FALLBACK);
        }

        // Prefer saturated, mid-lightness colours: near-white/black/grey make poor brand colours.
        $score = static function (array $c): float {
            [, $s, $l] = Color::toHsl($c);

            // Brand colours sit a little below mid lightness: light pastels and near-black score low.
            return $s * (1 - min(1.0, abs($l - 0.42) * 1.6));
        };
        // ColorThief returns the palette most-common first, but splits one big hue into several boxes.
        // Weight each colour by how much of the palette shares its hue, so the cover's main colour
        // wins over a small, brightly saturated banner.
        $ranked = [];
        foreach ($palette as $i => $c) {
            $cluster = 0.0;
            foreach ($palette as $j => $other) {
                if (Color::saturation($other) >= 0.3 && Color::hueDistance($c, $other) < 35) {
                    $cluster += 1 - 0.1 * $j;
                }
            }
            $ranked[] = ['c' => $c, 's' => $score($c) * $cluster ** 1.5 * (1 - 0.05 * $i)];
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
            if ($secondary === null && $score($c) > 0.12 && Color::hueDistance($primary, $c) >= 25) {
                $secondary = $c;
            } elseif ($accent === null && Color::saturation($c) >= 0.5 && Color::hueDistance($primary, $c) >= 70) {
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
