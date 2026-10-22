<?php

namespace Packitifotech\Laragdads\Ads\Support;

use GdImage;

/**
 * Moves the background colour family of a piece of artwork to another hue, leaving everything else alone: whites, greys and
 * dark lettering (low saturation), the red logo and button, and colours far from the background family (a blue set square
 * on a yellow page keeps its blue). Pixels close to the family move fully, pixels at the edge of it only partly, so gradients stay smooth.
 */
final class Recolor
{
    /** Pixels within this many degrees of the family hue are recoloured completely, and none are beyond FADE. */
    private const CORE = 20;

    private const FADE = 42;

    /** How much of a pixel's own hue offset from the family is kept, so a gradient from yellow to orange stays a gradient. */
    private const SPREAD = 0.6;

    /**
     * @param float $fromHue the artwork's background hue, degrees
     * @param float $fromSat its typical saturation (0..1)
     * @param float $toHue   the hue to move it to, degrees
     * @param float $toSat   how saturated the new background should be (0..1)
     */
    public static function hue(GdImage $source, float $fromHue, float $fromSat, float $toHue, float $toSat): GdImage
    {
        $w = imagesx($source);
        $h = imagesy($source);
        $out = imagecreatetruecolor($w, $h);
        imagecopy($out, $source, 0, 0, 0, 0, $w, $h);
        $ratio = max(0.5, min(1.5, $toSat / max(0.05, $fromSat)));

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $c = imagecolorat($source, $x, $y);
                $r = ($c >> 16) & 255;
                $g = ($c >> 8) & 255;
                $b = $c & 255;
                $max = max($r, $g, $b);
                $d = $max - min($r, $g, $b);
                if ($max < 40 || $d * 100 < $max * 9) {   // near-black, white or grey: saturation below 0.09
                    continue;
                }
                $s = $d / $max;

                if ($max === $r) {
                    $hue = 60 * ((($g - $b) / $d) + ($g < $b ? 6 : 0));
                } elseif ($max === $g) {
                    $hue = 60 * ((($b - $r) / $d) + 2);
                } else {
                    $hue = 60 * ((($r - $g) / $d) + 4);
                }
                if ($s > 0.7 && $max > 178 && ($hue >= 340 || $hue <= 12)) {
                    continue;   // the red of the logo and the Shop Now button
                }

                $offset = fmod($hue - $fromHue + 540, 360) - 180;   // -180..180
                $away = abs($offset);
                if ($away >= self::FADE) {
                    continue;
                }
                $weight = $away <= self::CORE ? 1.0 : (self::FADE - $away) / (self::FADE - self::CORE);
                $t = ($s - 0.09) / 0.13;   // fade in with saturation: faint tints move less than clear colour
                $weight *= $t >= 1 ? 1.0 : ($t * $t * (3 - 2 * $t));
                if ($weight <= 0) {
                    continue;
                }

                $newHue = fmod($toHue + $offset * self::SPREAD + 360, 360);
                $newSat = min(1.0, $s * $ratio);
                [$nr, $ng, $nb] = self::rgb($newHue, $newSat, $max / 255);
                imagesetpixel($out, $x, $y, (int) round($r + ($nr - $r) * $weight) << 16 | (int) round($g + ($ng - $g) * $weight) << 8 | (int) round($b + ($nb - $b) * $weight));
            }
        }

        return $out;
    }

    /** @return int[] */
    private static function rgb(float $h, float $s, float $v): array
    {
        $c = $v * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $v - $c;
        [$r, $g, $b] = match ((int) floor($h / 60) % 6) {
            0 => [$c, $x, 0],
            1 => [$x, $c, 0],
            2 => [0, $c, $x],
            3 => [0, $x, $c],
            4 => [$x, 0, $c],
            default => [$c, 0, $x],
        };

        return [(int) round(($r + $m) * 255), (int) round(($g + $m) * 255), (int) round(($b + $m) * 255)];
    }
}
