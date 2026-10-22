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

    /**
     * Repaint the red button inside $rect ([x, y, width, height], pixels) in $accent: strongly red pixels take the accent's hue, and
     * their saturation and brightness are scaled by the accent's, so the pill keeps its gradient and shadow. White lettering
     * and the background around the pill (not red) are left alone. $image is changed in place.
     *
     * @param int[] $rect
     * @param int[] $accent [r, g, b]
     */
    public static function button(GdImage $image, array $rect, array $accent): void
    {
        [$ax, $ay, $aw, $ah] = $rect;
        [$hue, $sat, $val] = self::hsv($accent[0], $accent[1], $accent[2]);
        $sScale = $sat / 0.85;
        $vScale = $val / 0.9;
        $w = imagesx($image);
        $h = imagesy($image);

        for ($y = max(0, $ay); $y < min($h, $ay + $ah); $y++) {
            for ($x = max(0, $ax); $x < min($w, $ax + $aw); $x++) {
                $c = imagecolorat($image, $x, $y);
                $r = ($c >> 16) & 255;
                $g = ($c >> 8) & 255;
                $b = $c & 255;
                [$ph, $ps, $pv] = self::hsv($r, $g, $b);
                if ($ps < 0.45 || $pv < 0.45 || !($ph >= 335 || $ph <= 25)) {
                    continue;
                }
                [$nr, $ng, $nb] = self::rgb($hue, min(1.0, $ps * $sScale), min(1.0, $pv * $vScale));
                imagesetpixel($image, $x, $y, $nr << 16 | $ng << 8 | $nb);
            }
        }
    }

    /** @return float[] hue 0-360, saturation 0-1, value 0-1 */
    private static function hsv(int $r, int $g, int $b): array
    {
        $max = max($r, $g, $b);
        $d = $max - min($r, $g, $b);
        if ($d === 0) {
            return [0.0, 0.0, $max / 255];
        }
        $hue = match ($max) {
            $r => 60 * ((($g - $b) / $d) + ($g < $b ? 6 : 0)),
            $g => 60 * ((($b - $r) / $d) + 2),
            default => 60 * ((($r - $g) / $d) + 4),
        };

        return [(float) $hue, $d / $max, $max / 255];
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
