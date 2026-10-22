<?php

namespace Packitifotech\Laragdads\Ads\Templates;

use GdImage;
use Packitifotech\Laragdads\Ads\Support\Canvas;
use Packitifotech\Laragdads\Ads\Support\Color;

/**
 * Cheerful props for the children's templates (colouring, activity, rhymes, stories, alphabets): rainbow, sun, star, moon,
 * balloon, flower and butterfly. Same conventions as Props: pixel coordinates, anti-aliased by supersampling.
 */
final class KidsProps
{
    /** Rainbow arch standing on ($cx, $bottom), $w wide. */
    public static function rainbow(Canvas $c, int $cx, int $bottom, int $w): void
    {
        $bands = [[238, 84, 90], [250, 160, 50], [250, 208, 60], [110, 190, 90], [70, 150, 230], [130, 100, 210]];
        $c->draw($cx - intdiv($w, 2) - 2, $bottom - intdiv($w, 2) - 2, $w + 4, intdiv($w, 2) + 4, function (GdImage $l, int $k, callable $col) use ($w, $bands) {
            $s = fn (float $v): int => (int) round($v * $k);
            $mid = $w / 2 + 2;
            $t = $w * 0.075;
            foreach ($bands as $i => $b) {
                $d = 2 * ($w / 2 - $i * $t);
                imagefilledellipse($l, $s($mid), $s($mid), $s($d), $s($d), $col($b, 1.0));
            }
            $inner = 2 * ($w / 2 - count($bands) * $t);
            imagefilledellipse($l, $s($mid), $s($mid), $s($inner), $s($inner), $col([0, 0, 0], 0.0));
            imagefilledrectangle($l, 0, $s($mid), $s($w + 4), $s($w + 4), $col([0, 0, 0], 0.0));
        });
    }

    /** Smiling sun with rays, centred on ($cx, $cy), $d across the disc. */
    public static function sun(Canvas $c, int $cx, int $cy, int $d): void
    {
        $r = (int) ($d * 0.9);
        $c->draw($cx - $r, $cy - $r, 2 * $r, 2 * $r, function (GdImage $l, int $k, callable $col) use ($d, $r) {
            $s = fn (float $v): int => (int) round($v * $k);
            imagesetthickness($l, max(2, $s($d * 0.09)));
            for ($i = 0; $i < 12; $i++) {
                $a = $i * M_PI / 6;
                imageline($l, $s($r + cos($a) * $d * 0.62), $s($r + sin($a) * $d * 0.62), $s($r + cos($a) * $d * 0.88), $s($r + sin($a) * $d * 0.88), $col([250, 176, 40], 1.0));
            }
            imagefilledellipse($l, $s($r), $s($r), $s($d), $s($d), $col([252, 200, 50], 1.0));
            imagefilledellipse($l, $s($r), $s($r), $s($d * 0.82), $s($d * 0.82), $col([255, 222, 90], 1.0));
            foreach ([-1, 1] as $e) {
                imagefilledellipse($l, $s($r + $e * $d * 0.17), $s($r - $d * 0.08), $s($d * 0.08), $s($d * 0.1), $col([120, 70, 30], 1.0));
            }
            imagesetthickness($l, max(2, $s($d * 0.04)));
            imagearc($l, $s($r), $s($r + $d * 0.04), $s($d * 0.42), $s($d * 0.3), 20, 160, $col([200, 90, 40], 1.0));
        });
    }

    /** Five-point star. */
    public static function star(Canvas $c, int $cx, int $cy, int $d, array $color = [250, 200, 50]): void
    {
        $c->draw($cx - $d, $cy - $d, 2 * $d, 2 * $d, function (GdImage $l, int $k, callable $col) use ($d, $color) {
            $s = fn (float $v): int => (int) round($v * $k);
            $pts = [];
            for ($i = 0; $i < 10; $i++) {
                $a = -M_PI / 2 + $i * M_PI / 5;
                $r = ($i % 2 === 0 ? 0.5 : 0.22) * $d * 2 * 0.98;
                $pts[] = $s($d + cos($a) * $r);
                $pts[] = $s($d + sin($a) * $r);
            }
            imagefilledpolygon($l, $pts, $col($color, 1.0));
            imagefilledellipse($l, $s($d - $d * 0.14), $s($d - $d * 0.2), $s($d * 0.3), $s($d * 0.3), $col(Color::lighten($color, 0.45), 0.7));
        });
    }

    /** Crescent moon. */
    public static function moon(Canvas $c, int $cx, int $cy, int $d, array $color = [252, 228, 130]): void
    {
        $c->draw($cx - $d, $cy - $d, 2 * $d, 2 * $d, function (GdImage $l, int $k, callable $col) use ($d, $color) {
            $s = fn (float $v): int => (int) round($v * $k);
            imagefilledellipse($l, $s($d), $s($d), $s($d * 1.7), $s($d * 1.7), $col($color, 1.0));
            imagefilledellipse($l, $s($d + $d * 0.5), $s($d - $d * 0.2), $s($d * 1.5), $s($d * 1.5), $col([0, 0, 0], 0.0));
        });
    }

    /** Balloon with a string, centred on its body ($cx, $cy), $d wide. */
    public static function balloon(Canvas $c, int $cx, int $cy, int $d, array $color): void
    {
        $w = (int) ($d * 1.1);
        $c->draw($cx - $w, $cy - (int) ($d * 0.7), 2 * $w, (int) ($d * 2.5), function (GdImage $l, int $k, callable $col) use ($d, $w, $color) {
            $s = fn (float $v): int => (int) round($v * $k);
            $x = $w;
            $y = $d * 0.7;
            imagesetthickness($l, max(2, $s($d * 0.03)));
            imagearc($l, $s($x + $d * 0.1), $s($y + $d * 1.3), $s($d * 0.35), $s($d * 0.9), 270, 90, $col([110, 110, 130], 1.0));
            imagefilledpolygon($l, [$s($x), $s($y + $d * 0.62), $s($x - $d * 0.09), $s($y + $d * 0.78), $s($x + $d * 0.09), $s($y + $d * 0.78)], $col(Color::darken($color, 0.15), 1.0));
            imagefilledellipse($l, $s($x), $s($y), $s($d), $s($d * 1.2), $col($color, 1.0));
            imagefilledellipse($l, $s($x - $d * 0.2), $s($y - $d * 0.25), $s($d * 0.22), $s($d * 0.36), $col([255, 255, 255], 0.45));
        });
    }

    /** Flower on a stem, standing on ($cx, $bottom), $h tall. */
    public static function flower(Canvas $c, int $cx, int $bottom, int $h, array $color = [238, 84, 130]): void
    {
        $w = (int) ($h * 0.5);
        $c->draw($cx - $w, $bottom - $h, 2 * $w, $h, function (GdImage $l, int $k, callable $col) use ($w, $h, $color) {
            $s = fn (float $v): int => (int) round($v * $k);
            $fy = $h * 0.28;
            imagesetthickness($l, max(2, $s($h * 0.04)));
            imageline($l, $s($w), $s($fy), $s($w), $s($h), $col([70, 160, 80], 1.0));
            imagefilledellipse($l, $s($w - $h * 0.14), $s($h * 0.68), $s($h * 0.26), $s($h * 0.12), $col([90, 180, 90], 1.0));
            imagefilledellipse($l, $s($w + $h * 0.14), $s($h * 0.52), $s($h * 0.26), $s($h * 0.12), $col([90, 180, 90], 1.0));
            for ($i = 0; $i < 6; $i++) {
                $a = $i * M_PI / 3;
                imagefilledellipse($l, $s($w + cos($a) * $h * 0.17), $s($fy + sin($a) * $h * 0.17), $s($h * 0.2), $s($h * 0.2), $col($color, 1.0));
            }
            imagefilledellipse($l, $s($w), $s($fy), $s($h * 0.16), $s($h * 0.16), $col([252, 205, 60], 1.0));
        });
    }

    /** Butterfly, centred on ($cx, $cy), $w wide. */
    public static function butterfly(Canvas $c, int $cx, int $cy, int $w, array $color = [140, 100, 220]): void
    {
        $c->draw($cx - $w, $cy - $w, 2 * $w, 2 * $w, function (GdImage $l, int $k, callable $col) use ($w, $color) {
            $s = fn (float $v): int => (int) round($v * $k);
            $light = Color::lighten($color, 0.4);
            foreach ([-1, 1] as $e) {
                imagefilledellipse($l, $s($w + $e * $w * 0.34), $s($w - $w * 0.2), $s($w * 0.62), $s($w * 0.78), $col($color, 1.0));
                imagefilledellipse($l, $s($w + $e * $w * 0.26), $s($w + $w * 0.28), $s($w * 0.44), $s($w * 0.5), $col($light, 1.0));
                imagefilledellipse($l, $s($w + $e * $w * 0.36), $s($w - $w * 0.22), $s($w * 0.22), $s($w * 0.26), $col([255, 255, 255], 0.6));
            }
            imagefilledellipse($l, $s($w), $s($w), $s($w * 0.12), $s($w * 0.8), $col([60, 50, 80], 1.0));
            imagesetthickness($l, max(2, $s($w * 0.03)));
            imageline($l, $s($w), $s($w - $w * 0.38), $s($w - $w * 0.14), $s($w - $w * 0.62), $col([60, 50, 80], 1.0));
            imageline($l, $s($w), $s($w - $w * 0.38), $s($w + $w * 0.14), $s($w - $w * 0.62), $col([60, 50, 80], 1.0));
        });
    }
}
