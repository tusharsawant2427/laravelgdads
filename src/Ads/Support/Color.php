<?php

namespace Packitifotech\Laragdads\Ads\Support;

/**
 * Colour maths on [r, g, b] arrays (0-255). Pure functions, no GD needed.
 */
final class Color
{
    /** @return array{0:int,1:int,2:int} */
    public static function fromHex(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            throw new \InvalidArgumentException("Invalid hex colour [#{$hex}].");
        }

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    /** Accepts [r,g,b] or '#rrggbb' / '#rgb'. */
    public static function normalize(array|string $color): array
    {
        if (is_string($color)) {
            return self::fromHex($color);
        }

        return [self::clamp($color[0]), self::clamp($color[1]), self::clamp($color[2])];
    }

    public static function toHex(array $color): string
    {
        return sprintf('#%02x%02x%02x', ...array_map('intval', $color));
    }

    /** WCAG relative luminance, 0 (black) to 1 (white). */
    public static function luminance(array $color): float
    {
        [$r, $g, $b] = array_map(function ($c) {
            $c /= 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, $color);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /** WCAG contrast ratio, 1 to 21. */
    public static function contrast(array $a, array $b): float
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /** Best readable text colour (near-white or near-black) for a background. */
    public static function textOn(array $background): array
    {
        $light = [255, 255, 255];
        $dark = [17, 24, 39];

        return self::contrast($background, $light) >= self::contrast($background, $dark) ? $light : $dark;
    }

    /** Linear blend: 0 returns $a, 1 returns $b. */
    public static function mix(array $a, array $b, float $amount): array
    {
        return array_map(
            fn ($x, $y) => self::clamp($x + ($y - $x) * $amount),
            $a,
            $b
        );
    }

    public static function lighten(array $color, float $amount): array
    {
        return self::mix($color, [255, 255, 255], $amount);
    }

    public static function darken(array $color, float $amount): array
    {
        return self::mix($color, [0, 0, 0], $amount);
    }

    /** @return array{0:float,1:float,2:float} hue 0-360, saturation 0-1, lightness 0-1 */
    public static function toHsl(array $color): array
    {
        [$r, $g, $b] = array_map(fn ($c) => $c / 255, $color);
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        $d = $max - $min;

        if ($d < 1e-9) {
            return [0.0, 0.0, $l];
        }

        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
        $h = match (true) {
            $max === $r => (($g - $b) / $d) + ($g < $b ? 6 : 0),
            $max === $g => (($b - $r) / $d) + 2,
            default => (($r - $g) / $d) + 4,
        };

        return [$h * 60, $s, $l];
    }

    public static function fromHsl(float $h, float $s, float $l): array
    {
        $h = fmod(fmod($h, 360) + 360, 360) / 360;
        if ($s <= 0) {
            $v = self::clamp($l * 255);

            return [$v, $v, $v];
        }

        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;
        $channel = function (float $t) use ($p, $q) {
            $t = $t < 0 ? $t + 1 : ($t > 1 ? $t - 1 : $t);

            return match (true) {
                $t < 1 / 6 => $p + ($q - $p) * 6 * $t,
                $t < 1 / 2 => $q,
                $t < 2 / 3 => $p + ($q - $p) * (2 / 3 - $t) * 6,
                default => $p,
            };
        };

        return [
            self::clamp($channel($h + 1 / 3) * 255),
            self::clamp($channel($h) * 255),
            self::clamp($channel($h - 1 / 3) * 255),
        ];
    }

    /** Shortest distance between two hues, 0-180. */
    public static function hueDistance(array $a, array $b): float
    {
        $d = abs(self::toHsl($a)[0] - self::toHsl($b)[0]);

        return $d > 180 ? 360 - $d : $d;
    }

    public static function saturation(array $color): float
    {
        return self::toHsl($color)[1];
    }

    /** Plain-English colour name for prompts: "deep purple", "bright orange", "light teal". */
    public static function name(array $color): string
    {
        [$h, $s, $l] = self::toHsl($color);
        if ($s < 0.12) {
            return $l > 0.8 ? 'white' : ($l < 0.2 ? 'black' : 'grey');
        }
        $hue = match (true) {
            $h < 15 || $h >= 345 => 'red',
            $h < 40 => 'orange',
            $h < 65 => 'yellow',
            $h < 165 => 'green',
            $h < 195 => 'teal',
            $h < 250 => 'blue',
            $h < 290 => 'purple',
            default => 'magenta',
        };
        $tone = $l < 0.28 ? 'deep ' : ($l > 0.68 ? 'light ' : ($s > 0.7 ? 'vivid ' : ''));

        return $tone . $hue;
    }

    private static function clamp(float|int $v): int
    {
        return (int) max(0, min(255, round($v)));
    }
}
