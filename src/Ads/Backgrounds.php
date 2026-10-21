<?php

namespace Packitifotech\Laragdads\Ads;

use GdImage;
use Packitifotech\Laragdads\Ads\Support\Canvas;
use Packitifotech\Laragdads\Ads\Support\Color;
use Packitifotech\Laragdads\Ads\Support\ImageLoader;
use Packitifotech\Laragdads\Services\Image\ImageService;

/**
 * Background painters. A painter is `fn (Canvas $canvas, AdSpec $spec, Theme $theme): void`.
 *
 * Built-in styles, all coloured from the cover/brand: auto (= studio, a layered design), studio, paper (the light page of the
 * companion layout), mesh (simple), gradient, solid, blur.
 * Legacy styles (wave1, wave, abstract, stripes, radial, spotlight, burst, marketing, medical,
 * mobile-dots, mobile-card, mobile-diagonal, mobile-flat) are the original ImageService painters.
 * Add your own with Backgrounds::register().
 */
final class Backgrounds
{
    /** @var array<string, callable> */
    private static array $custom = [];

    public static function register(string $name, callable $painter): void
    {
        self::$custom[$name] = $painter;
    }

    public static function paint(string $style, Canvas $canvas, AdSpec $spec, Theme $theme): void
    {
        if (isset(self::$custom[$style])) {
            (self::$custom[$style])($canvas, $spec, $theme);

            return;
        }

        match ($style) {
            'auto', 'studio' => self::studio($canvas, $theme),
            'paper' => self::paper($canvas, $theme),
            'mesh' => self::mesh($canvas, $theme),
            'gradient' => $canvas->gradient($theme->backgroundFrom, $theme->backgroundTo, 'diagonal'),
            'solid' => $canvas->rect(0, 0, $canvas->width(), $canvas->height(), $theme->backgroundFrom),
            'blur' => self::blur($canvas, $spec, $theme),
            default => self::legacy($style, $canvas, $spec),
        };
    }

    /** @return string[] */
    public static function names(): array
    {
        return array_values(array_unique(array_merge(
            ['auto', 'studio', 'paper', 'mesh', 'gradient', 'solid', 'blur'],
            array_keys(self::legacyMap()),
            array_keys(self::$custom)
        )));
    }

    /** Diagonal gradient plus soft colour blobs: reads as depth without distracting from the content. */
    private static function mesh(Canvas $canvas, Theme $theme): void
    {
        $w = $canvas->width();
        $h = $canvas->height();
        $long = max($w, $h);

        $canvas->gradient($theme->backgroundFrom, $theme->backgroundTo, 'diagonal');
        $canvas->glow((int) ($w * 0.12), (int) ($h * 0.1), (int) ($long * 0.55), Color::lighten($theme->primary, 0.25), 0.55);
        $canvas->glow((int) ($w * 0.92), (int) ($h * 0.95), (int) ($long * 0.6), $theme->accent, 0.28);
        $canvas->glow((int) ($w * 0.62), (int) ($h * 0.2), (int) ($long * 0.35), Color::lighten($theme->secondary, 0.3), 0.3);
    }

    /**
     * The default: a layered, "designed" backdrop. Brand gradient, soft light beams, flowing waves, rings,
     * a dot grid, bokeh and a vignette. Every element is a translucent tint of the brand colours, so
     * text contrast stays safe, and it is deterministic for a given size.
     */
    private static function studio(Canvas $canvas, Theme $theme): void
    {
        $w = $canvas->width();
        $h = $canvas->height();
        $long = max($w, $h);
        $rand = self::rng($w * 13 + $h * 7);
        $tint = Color::lighten($theme->primary, 0.6);
        $tint2 = Color::lighten($theme->secondary, 0.5);

        self::mesh($canvas, $theme);
        // Extra warmth: an accent-coloured bloom low on the canvas and a bright one top-left.
        $canvas->glow((int) ($w * 0.7), (int) ($h * 1.0), (int) ($long * 0.5), $theme->accent, 0.22);
        $canvas->glow((int) ($w * 0.25), (int) ($h * 0.2), (int) ($long * 0.4), Color::lighten($theme->primary, 0.5), 0.3);

        // Soft diagonal light beams from the top-left.
        $canvas->decor(function ($layer, int $k) use ($w, $h, $canvas) {
            $white = [255, 255, 255];
            foreach ([[0.02, 0.22, 0.07], [0.30, 0.50, 0.05], [0.62, 0.74, 0.04]] as [$a, $b, $alpha]) {
                imagefilledpolygon($layer, [
                    (int) ($w * $a * $k), 0,
                    (int) ($w * $b * $k), 0,
                    (int) (($w * $b + $h * 0.9) * $k), $h * $k,
                    (int) (($w * $a + $h * 0.9) * $k), $h * $k,
                ], $canvas->colorOn($layer, $white, $alpha));
            }
        });

        // Large outlined rings at the corners.
        $canvas->decor(function ($layer, int $k) use ($w, $h, $long, $tint, $canvas) {
            imagesetthickness($layer, max(2, (int) round($long * 0.004 * $k)));
            $ring = $canvas->colorOn($layer, $tint, 0.16);
            foreach ([[0.96, 0.08, 0.34], [0.05, 0.98, 0.42], [0.96, 0.08, 0.5]] as [$x, $y, $d]) {
                imageellipse($layer, (int) ($w * $x * $k), (int) ($h * $y * $k), (int) ($long * $d * $k), (int) ($long * $d * $k), $ring);
            }
        });

        self::drawWaves($canvas, $theme, $rand, 3);

        // Dot grid patch.
        $gap = max(10, (int) round($long / 60));
        $canvas->decor(function ($layer, int $k) use ($w, $h, $gap, $tint, $canvas) {
            $dot = $canvas->colorOn($layer, $tint, 0.3);
            $x0 = (int) ($w * 0.66);
            $cols = 9;
            $rows = max(3, (int) min(7, $h / $gap / 3));
            for ($r = 0; $r < $rows; $r++) {
                for ($c = 0; $c < $cols; $c++) {
                    imagefilledellipse($layer, ($x0 + $c * $gap) * $k, (int) ($h * 0.06 + $r * $gap) * $k, 2 * $k, 2 * $k, $dot);
                }
            }
        });

        // A little bokeh.
        $palette = [$tint, $theme->accent, $tint2, [255, 255, 255]];
        for ($i = 0, $n = 7 + (int) round($long / 200); $i < $n; $i++) {
            $d = (int) round((0.03 + $rand() * 0.1) * $long);
            $canvas->circle((int) ($rand() * $w), (int) ($rand() * $h), max(8, $d), $palette[$i % 4], 0.06 + $rand() * 0.1);
        }

        self::vignette($canvas);
    }

    /**
     * Light "paper" page for friendly, illustrated ads: warm white with soft tints of the brand colour in the
     * corners and a scatter of fine dots. Text on it is dark.
     */
    private static function paper(Canvas $canvas, Theme $theme): void
    {
        $w = $canvas->width();
        $h = $canvas->height();
        $tint = Color::mix([255, 255, 255], $theme->primary, 0.12);
        $canvas->rect(0, 0, $w, $h, [250, 249, 252]);
        $canvas->glow((int) ($w * 0.12), (int) ($h * 0.3), (int) (max($w, $h) * 0.45), $tint, 0.9);
        $canvas->glow((int) ($w * 0.95), (int) ($h * 1.0), (int) (max($w, $h) * 0.4), [255, 236, 190], 0.55);
        $canvas->glow((int) ($w * 0.55), (int) ($h * 0.1), (int) (max($w, $h) * 0.3), [236, 230, 250], 0.5);

        // Fine paper grain.
        $rand = self::rng($w + $h * 3);
        $canvas->decor(function ($layer, int $k) use ($w, $h, $rand, $canvas) {
            $dot = $canvas->colorOn($layer, [120, 100, 150], 0.07);
            for ($i = 0, $n = (int) ($w * $h / 900); $i < $n; $i++) {
                imagefilledellipse($layer, (int) ($rand() * $w * $k), (int) ($rand() * $h * $k), $k, $k, $dot);
            }
        }, 1);
    }




    private static function drawWaves(Canvas $canvas, Theme $theme, \Closure $rand, int $bands): void
    {
        $w = $canvas->width();
        $h = $canvas->height();
        $tints = [Color::lighten($theme->primary, 0.5), $theme->accent, Color::lighten($theme->secondary, 0.45)];

        $canvas->decor(function ($layer, int $k) use ($w, $h, $rand, $bands, $tints, $canvas) {
            for ($b = 0; $b < $bands; $b++) {
                $base = $h * (0.62 + $b * 0.11);
                $amp = $h * (0.05 + $rand() * 0.05);
                $freq = (0.8 + $rand() * 1.4) * M_PI * 2 / $w;
                $phase = $rand() * M_PI * 2;
                $pts = [0, $h * $k];
                for ($x = 0; $x <= $w; $x += max(4, intdiv($w, 120))) {
                    $pts[] = $x * $k;
                    $pts[] = (int) (($base + sin($x * $freq + $phase) * $amp) * $k);
                }
                $pts[] = $w * $k;
                $pts[] = $h * $k;
                imagefilledpolygon($layer, $pts, $canvas->colorOn($layer, $tints[$b % 3], 0.13 + $b * 0.035));
            }
        });
    }

    /** Darken the edges slightly so the eye settles on the centre. */
    private static function vignette(Canvas $canvas): void
    {
        $w = $canvas->width();
        $h = $canvas->height();
        $black = [0, 0, 0];
        $canvas->gradient($black, $black, 'vertical', [0, 0, $w, (int) ($h * 0.3)], 0.22, 0.0);
        $canvas->gradient($black, $black, 'vertical', [0, (int) ($h * 0.7), $w, $h - (int) ($h * 0.7)], 0.0, 0.28);
        $canvas->gradient($black, $black, 'horizontal', [0, 0, (int) ($w * 0.12), $h], 0.16, 0.0);
        $canvas->gradient($black, $black, 'horizontal', [(int) ($w * 0.88), 0, $w - (int) ($w * 0.88), $h], 0.0, 0.16);
    }




    /** Deterministic 0..1 generator: the same size always gives the same pattern. */
    private static function rng(int $seed): \Closure
    {
        $state = $seed % 2147483647 ?: 1;

        return function () use (&$state): float {
            $state = ($state * 48271) % 2147483647;

            return $state / 2147483647;
        };
    }

    /** The first image, blown up, blurred and dimmed. */
    private static function blur(Canvas $canvas, AdSpec $spec, Theme $theme): void
    {
        if (!$spec->hasImages()) {
            self::mesh($canvas, $theme);

            return;
        }

        $src = ImageLoader::load($spec->images[0]);
        $small = imagescale($src, 48, -1, IMG_BILINEAR_FIXED);
        for ($i = 0; $i < 4; $i++) {
            imagefilter($small, IMG_FILTER_GAUSSIAN_BLUR);
        }
        $canvas->picture($small, 0, 0, $canvas->width(), $canvas->height(), 'cover');
        $canvas->rect(0, 0, $canvas->width(), $canvas->height(), $theme->scrim(), 0.55);
    }

    private static function legacy(string $style, Canvas $canvas, AdSpec $spec): void
    {
        $map = self::legacyMap();
        $path = $spec->themeSource ?? ($spec->images[0] ?? null);
        if (!is_string($path)) {
            throw new \InvalidArgumentException("Background [{$style}] needs the colour-source image as a file path.");
        }

        $w = $canvas->width();
        $h = $canvas->height();
        $font = $spec->fontPath;

        $image = isset($map[$style])
            ? $map[$style]($path, $w, $h, $font)
            : ImageService::createSeamlessJoinedWaveBackground($path, $w, $h);

        self::copyOnto($canvas, $image);
    }

    /** @return array<string, \Closure> */
    private static function legacyMap(): array
    {
        return [
            'wave1' => fn ($p, $w, $h) => ImageService::createSeamlessJoinedWaveBackground1($p, $w, $h),
            'wave' => fn ($p, $w, $h) => ImageService::createSeamlessJoinedWaveBackground($p, $w, $h),
            'abstract' => fn ($p, $w, $h) => ImageService::createAbstractPatternedBackground($p, $w, $h),
            'stripes' => fn ($p, $w, $h) => ImageService::createDiagonalStripesBackground($p, $w, $h),
            'radial' => fn ($p, $w, $h) => ImageService::createRadialGradientBackground($p, $w, $h),
            'marketing' => fn ($p, $w, $h, $f) => ImageService::createMarketingAgencyBanner($p, null, $f, $w, $h),
            'spotlight' => fn ($p, $w, $h) => ImageService::createSpotlightBackground($p, $w, $h),
            'burst' => fn ($p, $w, $h) => ImageService::createBurstPatternBackground($p, $w, $h),
            'mobile-dots' => fn ($p, $w, $h) => ImageService::createModernDotsPattern($p, $w, $h),
            'mobile-card' => fn ($p, $w, $h) => ImageService::createMaterialCardBackground($p, $w, $h),
            'mobile-diagonal' => fn ($p, $w, $h) => ImageService::createDiagonalMobilePattern($p, $w, $h),
            'mobile-flat' => fn ($p, $w, $h) => ImageService::createFlatMobileUIBackground($p, $w, $h),
            'medical' => fn ($p, $w, $h) => ImageService::createMedicalBannerBackground($p, $w, $h),
        ];
    }

    private static function copyOnto(Canvas $canvas, GdImage $image): void
    {
        imagealphablending($canvas->image(), false);
        imagecopy($canvas->image(), $image, 0, 0, 0, 0, min(imagesx($image), $canvas->width()), min(imagesy($image), $canvas->height()));
        imagealphablending($canvas->image(), true);
    }
}
