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
 * Built-in modern styles, all coloured from the cover/brand: auto (mesh), mesh, lines, bokeh, hex, gradient,
 * solid, blur.
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
            'auto', 'mesh' => self::mesh($canvas, $theme),
            'lines' => self::lines($canvas, $theme),
            'bokeh' => self::bokeh($canvas, $theme),
            'hex' => self::hex($canvas, $theme),
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
            ['auto', 'mesh', 'lines', 'bokeh', 'hex', 'gradient', 'solid', 'blur'],
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

    /** Long, thin crossing lines in a lighter tone of the brand colour (the classic "ad strip" look). */
    private static function lines(Canvas $canvas, Theme $theme): void
    {
        self::mesh($canvas, $theme);
        $w = $canvas->width();
        $h = $canvas->height();
        $rand = self::rng($w * 31 + $h);
        $tint = Color::lighten($theme->primary, 0.55);
        $gold = Color::mix($theme->accent, $tint, 0.4);

        $canvas->decor(function ($layer, int $k) use ($w, $h, $rand, $tint, $gold, $canvas) {
            imagesetthickness($layer, max(1, $k));
            $count = 9 + (int) round(max($w, $h) / 160);
            for ($i = 0; $i < $count; $i++) {
                $x1 = (int) (($rand() * 1.4 - 0.2) * $w * $k);
                $y1 = (int) (($rand() * 1.4 - 0.2) * $h * $k);
                $angle = ($rand() * 0.9 + 0.05) * M_PI * ($rand() > 0.5 ? 1 : -1);
                $len = (0.35 + $rand() * 0.8) * max($w, $h) * $k;
                $color = $canvas->colorOn($layer, $i % 3 === 0 ? $gold : $tint, 0.24 + $rand() * 0.22);
                imageline($layer, $x1, $y1, (int) ($x1 + cos($angle) * $len), (int) ($y1 + sin($angle) * $len), $color);
            }
        });
    }

    /** Out-of-focus light discs. */
    private static function bokeh(Canvas $canvas, Theme $theme): void
    {
        self::mesh($canvas, $theme);
        $w = $canvas->width();
        $h = $canvas->height();
        $rand = self::rng($w * 17 + $h);
        $palette = [Color::lighten($theme->primary, 0.5), $theme->accent, Color::lighten($theme->secondary, 0.45), [255, 255, 255]];

        $count = 12 + (int) round(max($w, $h) / 120);
        for ($i = 0; $i < $count; $i++) {
            $d = (int) round((0.04 + $rand() * 0.16) * max($w, $h));
            $canvas->circle((int) ($rand() * $w), (int) ($rand() * $h), max(8, $d), $palette[$i % 4], 0.07 + $rand() * 0.14);
        }
    }

    /** Hexagon grid: a clean "science / tech" texture. */
    private static function hex(Canvas $canvas, Theme $theme): void
    {
        self::mesh($canvas, $theme);
        $w = $canvas->width();
        $h = $canvas->height();
        $tint = Color::lighten($theme->primary, 0.6);
        $r = max(18, (int) round(max($w, $h) / 16));

        $canvas->decor(function ($layer, int $k) use ($w, $h, $r, $tint, $canvas) {
            imagesetthickness($layer, max(1, $k));
            $color = $canvas->colorOn($layer, $tint, 0.14);
            $dx = $r * sqrt(3);
            $dy = $r * 1.5;
            for ($row = -1; $row * $dy < $h + $r; $row++) {
                for ($col = -1; $col * $dx < $w + $dx; $col++) {
                    $cx = ($col + ($row % 2 ? 0.5 : 0)) * $dx;
                    $cy = $row * $dy;
                    $pts = [];
                    for ($v = 0; $v < 6; $v++) {
                        $a = M_PI / 6 + $v * M_PI / 3;
                        $pts[] = (int) (($cx + $r * cos($a)) * $k);
                        $pts[] = (int) (($cy + $r * sin($a)) * $k);
                    }
                    imagepolygon($layer, $pts, $color);
                }
            }
        });
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
