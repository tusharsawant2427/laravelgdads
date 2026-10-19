<?php

namespace Packitifotech\Laragdads\Ads\Ai;

use GdImage;
use Packitifotech\Laragdads\Ads\Support\Canvas;
use Packitifotech\Laragdads\Ads\Support\Color;
use Packitifotech\Laragdads\Ads\Theme;

/**
 * AI-generated ad backgrounds via the AI Horde, with a disk cache and a contrast scrim so the copy that
 * the layout draws on top stays readable whatever the model produced.
 */
final class AiBackground
{
    /** @var (callable(string,int,int,array):GdImage)|null test hook / custom generator */
    private static $generator = null;

    private static ?string $cacheDir = null;

    /** Where generated backgrounds are cached (default: Laravel storage/framework/cache/laragdads-ai, else the system temp dir). */
    public static function useCacheDir(?string $dir): void
    {
        self::$cacheDir = $dir;
    }

    /** Replace the generator (tests, or another image API): fn (string $prompt, int $w, int $h, array $options): GdImage. */
    public static function useGenerator(?callable $generator): void
    {
        self::$generator = $generator;
    }

    /**
     * @param  array{style:string,prompt:?string,key:?string,seed:?int,strict:bool,hires:bool,topic?:?string} $config
     * @return array{image:GdImage,style:string,prompt:string}
     */
    public static function fetch(array $config, Theme $theme, int $targetW, int $targetH): array
    {
        $style = AiStyles::resolve($config['style'], $config['seed'], $config['topic'] ?? null);
        $aspect = $targetW / max(1, $targetH);
        $prompt = $config['prompt'] ?? AiStyles::prompt($style, [Color::name($theme->primary), Color::name($theme->accent)], $aspect >= 2.4);
        [$w, $h] = self::dimensions($aspect, $config['hires']);

        $cache = self::cachePath($prompt, $w, $h, $config['seed']);
        if (is_file($cache) && ($img = @imagecreatefrompng($cache)) !== false) {
            return ['image' => $img, 'style' => $style, 'prompt' => $prompt];
        }

        $options = array_filter(['seed' => $config['seed'] !== null ? (string) $config['seed'] : null, 'steps' => 25], fn ($v) => $v !== null);

        // One retry: the Horde is crowd-sourced, so a worker can fault or a job can be censored by mistake.
        try {
            $image = self::generate($config, $prompt, $w, $h, $options);
        } catch (\Throwable) {
            unset($options['seed']);
            $image = self::generate($config, $prompt, $w, $h, $options);
        }

        @mkdir(dirname($cache), 0775, true);
        @imagepng($image, $cache);

        return ['image' => $image, 'style' => $style, 'prompt' => $prompt];
    }

    private static function generate(array $config, string $prompt, int $w, int $h, array $options): GdImage
    {
        return self::$generator !== null
            ? (self::$generator)($prompt, $w, $h, $options)
            : (new HordeClient($config['key']))->generate($prompt, $w, $h, $options);
    }

    /**
     * Paint the AI image as the background, then add a scrim so text at the theme's text colour reads.
     * The scrim strength is solved from the image's average brightness; $lighter caps it when a text panel
     * already guarantees readability.
     */
    public static function paint(Canvas $canvas, GdImage $image, Theme $theme, bool $lighter = false): void
    {
        $w = $canvas->width();
        $h = $canvas->height();
        $strip = $w / max(1, $h) >= 3.2;
        $canvas->picture($image, 0, 0, $w, $h, 'cover', 0, [0.5, 0.55]);
        $native = abs(imagesx($image) / max(1, imagesy($image)) - $w / max(1, $h)) / ($w / max(1, $h)) < 0.25;
        if ($strip && !$native) {
            // A slice cropped from a landscape photo is busy and soft: blur it into a calm band. (Natively wide images stay sharp.)
            for ($i = 0; $i < 3; $i++) {
                imagefilter($canvas->image(), IMG_FILTER_GAUSSIAN_BLUR);
            }
        }

        $darkMode = Color::luminance($theme->text) > 0.5;
        $scrim = $darkMode ? $theme->scrim() : Color::lighten($theme->primary, 0.85);
        $avg = self::averageLuminance($image);
        $scrimL = Color::luminance($scrim);
        $target = $darkMode ? 0.16 : 0.8;

        $needed = abs($avg - $scrimL) < 0.02 ? 0.3 : ($avg - $target) / ($avg - $scrimL);
        $opacity = $strip ? ($native ? max(0.22, min(0.46, $needed)) : max(0.5, min(0.74, $needed))) : ($lighter ? max(0.15, min(0.4, $needed)) : max(0.3, min(0.78, $needed)));
        $canvas->rect(0, 0, $w, $h, $scrim, $opacity);

        if ($strip) {
            // Brand-coloured glow in the middle behind the headline, darker towards the product cards.
            $canvas->glow((int) ($w * 0.5), (int) ($h * 0.5), (int) ($w * 0.34), Color::lighten($theme->primary, 0.1), 0.35);
            $canvas->gradient($scrim, $scrim, 'horizontal', [0, 0, (int) ($w * 0.2), $h], 0.45, 0.0);
            $canvas->gradient($scrim, $scrim, 'horizontal', [(int) ($w * 0.8), 0, $w - (int) ($w * 0.8), $h], 0.0, 0.45);

            return;
        }

        // Slight vignette to keep the edges calm.
        $black = [0, 0, 0];
        if ($darkMode) {
            $canvas->gradient($black, $black, 'vertical', [0, (int) ($h * 0.7), $w, $h - (int) ($h * 0.7)], 0.0, 0.22);
        }
    }

    /**
     * Sizes SD models handle well (multiples of 64, about 0.2-0.3 megapixels), picked from the target aspect ratio.
     * Wide banners are generated natively wide (6:1, 4:1, 3:1) rather than cropped from a landscape photo.
     *
     * @return array{0:int,1:int}
     */
    public static function dimensions(float $aspect, bool $hires = false): array
    {
        return match (true) {
            $aspect >= 5.0 => $hires ? [1536, 256] : [1152, 192],
            $aspect >= 3.6 => $hires ? [1280, 320] : [1024, 256],
            $aspect >= 2.4 => $hires ? [1152, 384] : [960, 320],
            $aspect >= 1.25 => $hires ? [1024, 576] : [768, 448],
            $aspect <= 0.8 => $hires ? [576, 1024] : [448, 768],
            default => $hires ? [832, 832] : [640, 640],
        };
    }

    private static function averageLuminance(GdImage $image): float
    {
        $small = imagescale($image, 24, -1, IMG_BILINEAR_FIXED) ?: $image;
        $sum = 0.0;
        $n = 0;
        for ($y = 0; $y < imagesy($small); $y++) {
            for ($x = 0; $x < imagesx($small); $x++) {
                $c = imagecolorat($small, $x, $y);
                $sum += Color::luminance([($c >> 16) & 255, ($c >> 8) & 255, $c & 255]);
                $n++;
            }
        }

        return $n ? $sum / $n : 0.3;
    }

    private static function cachePath(string $prompt, int $w, int $h, ?int $seed): string
    {
        $dir = self::$cacheDir
            ?: (function_exists('storage_path') ? @storage_path('framework/cache/laragdads-ai') : null)
            ?: sys_get_temp_dir() . '/laragdads-ai';

        return $dir . '/' . sha1($prompt . '|' . $w . 'x' . $h . '|' . ($seed ?? 'any')) . '.png';
    }
}
