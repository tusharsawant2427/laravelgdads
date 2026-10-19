<?php

namespace Packitifotech\Laragdads\Ads\Support;

use GdImage;
use InvalidArgumentException;

final class ImageLoader
{
    /** @var array<string, GdImage> small memo so one file is decoded once per ad, not once per use */
    private static array $memo = [];

    /**
     * Load a JPEG, PNG, WebP or GIF (path, URL-less file or existing GdImage) as an alpha-aware truecolor image.
     */
    public static function load(string|GdImage $source): GdImage
    {
        if ($source instanceof GdImage) {
            return $source;
        }

        if (!is_file($source) || !is_readable($source)) {
            throw new InvalidArgumentException("Image not found or unreadable: [{$source}].");
        }

        $key = $source . '|' . filemtime($source);
        if (isset(self::$memo[$key])) {
            return self::$memo[$key];
        }

        $image = @imagecreatefromstring((string) file_get_contents($source));
        if ($image === false) {
            throw new InvalidArgumentException("Unsupported or corrupt image: [{$source}].");
        }

        if (!imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }
        imagealphablending($image, false);
        imagesavealpha($image, true);

        if (count(self::$memo) >= 6) {
            array_shift(self::$memo);
        }

        return self::$memo[$key] = self::applyExifOrientation($image, $source);
    }

    private static function applyExifOrientation(GdImage $image, string $path): GdImage
    {
        if (!function_exists('exif_read_data') || !preg_match('/\.jpe?g$/i', $path)) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $rotated = match ($exif['Orientation'] ?? 1) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return $rotated ?: $image;
    }
}
