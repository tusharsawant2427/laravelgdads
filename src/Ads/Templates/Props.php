<?php

namespace Packitifotech\Laragdads\Ads\Templates;

use GdImage;
use Packitifotech\Laragdads\Ads\Support\Canvas;
use Packitifotech\Laragdads\Ads\Support\Color;

/**
 * More illustrated props for the template library: lab glass, molecules, globe, DNA, microscope, trophy, graduation cap,
 * geometry set, calculator, rocket, clock, clouds and confetti. Same conventions as Doodles: pixel coordinates, sizes from
 * the canvas height, everything anti-aliased by supersampling.
 */
final class Props
{
    private const GLASS = [214, 234, 250];

    /** Conical (Erlenmeyer) or round flask with liquid. ($cx, $bottom) is the bottom centre. */
    public static function flask(Canvas $c, int $cx, int $bottom, int $h, array $liquid, string $kind = 'conical'): void
    {
        $w = (int) ($h * 0.82);
        $c->draw($cx - $w, $bottom - $h - 4, 2 * $w, $h + 8, function (GdImage $l, int $k, callable $col) use ($w, $h, $liquid, $kind) {
            $s = fn (float $v): int => (int) round($v * $k);
            $mx = $w;
            $top = 4;
            if ($kind === 'round') {
                $r = $h * 0.34;
                $cy = $top + $h - $r;
                $neck = $h * 0.09;
                imagefilledrectangle($l, $s($mx - $neck), $s($top), $s($mx + $neck), $s($cy - $r * 0.7), $col(self::GLASS, 0.75));
                imagefilledellipse($l, $s($mx), $s($cy), $s(2 * $r), $s(2 * $r), $col(self::GLASS, 0.75));
                imagefilledarc($l, $s($mx), $s($cy), $s(2 * $r * 0.94), $s(2 * $r * 0.94), 0, 180, $col($liquid, 0.95), IMG_ARC_PIE);
                imagefilledellipse($l, $s($mx - $r * 0.4), $s($cy - $r * 0.35), $s($r * 0.3), $s($r * 0.55), $col([255, 255, 255], 0.55));
                imagesetthickness($l, max(2, $k));
                imageellipse($l, $s($mx), $s($cy), $s(2 * $r), $s(2 * $r), $col([150, 190, 225], 1.0));
                imagerectangle($l, $s($mx - $neck - 2), $s($top), $s($mx + $neck + 2), $s($top + 3), $col([150, 190, 225], 1.0));
                return;
            }
            $neckW = $h * 0.1;
            $bodyW = $h * 0.46;
            $neckBottom = $top + $h * 0.4;
            $body = [$s($mx - $neckW), $s($top), $s($mx + $neckW), $s($top), $s($mx + $neckW), $s($neckBottom), $s($mx + $bodyW), $s($top + $h), $s($mx - $bodyW), $s($top + $h), $s($mx - $neckW), $s($neckBottom)];
            imagefilledpolygon($l, $body, $col(self::GLASS, 0.75));
            $level = $top + $h * 0.62;
            $f = ($level - $neckBottom) / ($h - ($neckBottom - $top));
            $lw = $neckW + ($bodyW - $neckW) * $f;
            imagefilledpolygon($l, [$s($mx - $lw), $s($level), $s($mx + $lw), $s($level), $s($mx + $bodyW * 0.96), $s($top + $h), $s($mx - $bodyW * 0.96), $s($top + $h)], $col($liquid, 0.95));
            imagefilledpolygon($l, [$s($mx - $neckW * 0.6), $s($top + $h * 0.06), $s($mx - $neckW * 0.2), $s($top + $h * 0.06), $s($mx - $bodyW * 0.55), $s($top + $h * 0.9), $s($mx - $bodyW * 0.75), $s($top + $h * 0.9)], $col([255, 255, 255], 0.4));
            imagesetthickness($l, max(2, $k));
            imagepolygon($l, $body, $col([150, 190, 225], 1.0));
            imageline($l, $s($mx - $neckW - 3), $s($top), $s($mx + $neckW + 3), $s($top), $col([150, 190, 225], 1.0));
        });
    }

    /** A rack of test tubes with coloured liquids. */
    public static function testTubes(Canvas $c, int $x, int $bottom, int $h, array $colors): void
    {
        $n = count($colors);
        $gap = (int) ($h * 0.3);
        $w = $n * $gap + (int) ($h * 0.2);
        $c->draw($x, $bottom - $h - 4, $w, $h + 8, function (GdImage $l, int $k, callable $col) use ($n, $gap, $h, $colors) {
            $s = fn (float $v): int => (int) round($v * $k);
            $tw = $gap * 0.62;
            foreach ($colors as $i => $liq) {
                $tx = $h * 0.14 + $i * $gap;
                $top = 4 + ($i % 2) * $h * 0.05;
                $bot = 4 + $h * 0.9;
                $r = $tw / 2;
                imagefilledrectangle($l, $s($tx), $s($top), $s($tx + $tw), $s($bot - $r), $col(self::GLASS, 0.75));
                imagefilledellipse($l, $s($tx + $r), $s($bot - $r), $s($tw), $s($tw), $col(self::GLASS, 0.75));
                $lv = $top + ($bot - $top) * (0.35 + 0.12 * (($i * 2) % 4));
                imagefilledrectangle($l, $s($tx + 1), $s($lv), $s($tx + $tw - 1), $s($bot - $r), $col($liq, 0.95));
                imagefilledellipse($l, $s($tx + $r), $s($bot - $r), $s($tw - 2), $s($tw - 2), $col($liq, 0.95));
                imagefilledrectangle($l, $s($tx + $tw * 0.2), $s($top + 2), $s($tx + $tw * 0.3), $s($bot - $r), $col([255, 255, 255], 0.4));
                imagesetthickness($l, max(1, (int) ($k * 1.2)));
                imagerectangle($l, $s($tx), $s($top), $s($tx + $tw), $s($bot - $r), $col([150, 190, 225], 1.0));
            }
            imagefilledrectangle($l, $s($h * 0.04), $s($h * 0.5), $s($n * $gap + $h * 0.14), $s($h * 0.56), $col([196, 150, 98], 1.0));
            imagefilledrectangle($l, $s($h * 0.04), $s($h * 0.56), $s($n * $gap + $h * 0.14), $s($h * 0.59), $col([150, 108, 66], 1.0));
        });
    }

    /** Ball-and-stick molecule. */
    public static function molecule(Canvas $c, int $cx, int $cy, int $size, array $color): void
    {
        $atoms = [[0.0, 0.0, 0.2, 0], [0.78, -0.42, 0.14, 1], [-0.7, -0.5, 0.14, 1], [-0.2, 0.82, 0.14, 1], [0.7, 0.6, 0.11, 2], [1.28, 0.05, 0.09, 2]];
        $bonds = [[0, 1], [0, 2], [0, 3], [1, 4], [1, 5]];
        $pal = [$color, Color::lighten($color, 0.35), [250, 190, 60]];
        $c->draw($cx - $size, $cy - $size, 2 * $size, 2 * $size, function (GdImage $l, int $k, callable $col) use ($size, $atoms, $bonds, $pal) {
            $s = $size * $k;
            $pt = fn (int $i): array => [(int) ($s + $atoms[$i][0] * $s * 0.62), (int) ($s + $atoms[$i][1] * $s * 0.62)];
            imagesetthickness($l, max(2, (int) ($size * 0.04 * $k)));
            foreach ($bonds as [$a, $b]) {
                [$x1, $y1] = $pt($a);
                [$x2, $y2] = $pt($b);
                imageline($l, $x1, $y1, $x2, $y2, $col([150, 160, 190], 1.0));
            }
            foreach ($atoms as $i => $at) {
                [$x, $y] = $pt($i);
                $d = (int) ($at[2] * $s * 2);
                imagefilledellipse($l, $x, $y, $d, $d, $col(Color::darken($pal[$at[3]], 0.12), 1.0));
                imagefilledellipse($l, $x, $y, (int) ($d * 0.88), (int) ($d * 0.88), $col($pal[$at[3]], 1.0));
                imagefilledellipse($l, (int) ($x - $d * 0.15), (int) ($y - $d * 0.18), (int) ($d * 0.32), (int) ($d * 0.26), $col([255, 255, 255], 0.6));
            }
        });
    }

    /** Globe on a stand, with continents. */
    public static function globe(Canvas $c, int $cx, int $cy, int $d): void
    {
        $c->draw($cx - $d, $cy - $d, 2 * $d, 2 * $d, function (GdImage $l, int $k, callable $col) use ($d) {
            $s = $d * $k;
            $r = (int) ($s * 0.66);
            imagefilledellipse($l, $s, (int) ($s * 0.94), 2 * $r, 2 * $r, $col([64, 150, 220], 1.0));
            imagefilledellipse($l, (int) ($s - $r * 0.25), (int) ($s * 0.94 - $r * 0.3), (int) ($r * 0.7), (int) ($r * 0.5), $col([255, 255, 255], 0.28));
            foreach ([[[-0.55, -0.35], [-0.2, -0.6], [0.05, -0.3], [-0.1, 0.1], [-0.4, 0.05]], [[0.2, 0.1], [0.55, -0.1], [0.65, 0.35], [0.4, 0.62], [0.2, 0.4]], [[-0.3, 0.3], [-0.05, 0.4], [-0.1, 0.7], [-0.32, 0.55]]] as $land) {
                $pts = [];
                foreach ($land as [$px, $py]) {
                    $pts[] = (int) ($s + $px * $r * 0.95);
                    $pts[] = (int) ($s * 0.94 + $py * $r * 0.95);
                }
                imagefilledpolygon($l, $pts, $col([96, 180, 96], 1.0));
            }
            imagesetthickness($l, max(1, (int) ($k * 1.2)));
            foreach ([0.55, 0.0, -0.55] as $lat) {
                imagearc($l, $s, (int) ($s * 0.94 + $lat * $r * 0.9), (int) (2 * $r * sqrt(1 - $lat * $lat * 0.8)), (int) (0.3 * $r), 0, 180, $col([255, 255, 255], 0.5));
            }
            imagesetthickness($l, max(3, (int) ($k * 2.5)));
            imagearc($l, $s, (int) ($s * 0.94), (int) ($r * 2.28), (int) ($r * 2.28), 90, 320, $col([60, 66, 90], 1.0));
            imagefilledrectangle($l, (int) ($s - $r * 0.4), (int) ($s * 0.94 + $r * 1.08), (int) ($s + $r * 0.4), (int) ($s * 0.94 + $r * 1.16), $col([60, 66, 90], 1.0));
            imagefilledellipse($l, $s, (int) ($s * 0.94 + $r * 1.16), (int) ($r * 1.1), (int) ($r * 0.2), $col([60, 66, 90], 1.0));
        });
    }

    /** DNA double helix, tilted. */
    public static function dna(Canvas $c, int $cx, int $cy, int $len, array $a, array $b, float $angle = 35.0): void
    {
        $pad = (int) ($len * 0.55);
        $c->draw($cx - $pad, $cy - $pad, 2 * $pad, 2 * $pad, function (GdImage $l, int $k, callable $col) use ($pad, $len, $a, $b, $angle) {
            $rad = deg2rad($angle);
            $amp = $len * 0.13;
            $n = 90;
            $pt = function (float $t, float $phase) use ($pad, $len, $amp, $rad, $k): array {
                $u = ($t - 0.5) * $len;
                $v = sin($t * M_PI * 3.2 + $phase) * $amp;

                return [($pad + $u * cos($rad) - $v * sin($rad)) * $k, ($pad + $u * sin($rad) + $v * cos($rad)) * $k];
            };
            imagesetthickness($l, max(2, (int) ($len * 0.018 * $k)));
            for ($i = 0; $i <= 14; $i++) {   // rungs
                $t = 0.05 + $i / 14 * 0.9;
                [$x1, $y1] = $pt($t, 0);
                [$x2, $y2] = $pt($t, M_PI);
                imageline($l, (int) $x1, (int) $y1, (int) $x2, (int) $y2, $col([170, 180, 205], 1.0));
            }
            imagesetthickness($l, max(3, (int) ($len * 0.035 * $k)));
            foreach ([[0.0, $a], [M_PI, $b]] as [$phase, $color]) {
                $prev = $pt(0, $phase);
                for ($i = 1; $i <= $n; $i++) {
                    $cur = $pt($i / $n, $phase);
                    imageline($l, (int) $prev[0], (int) $prev[1], (int) $cur[0], (int) $cur[1], $col($color, 1.0));
                    $prev = $cur;
                }
            }
        });
    }

    /** Microscope. ($cx, $bottom) is the bottom centre. */
    public static function microscope(Canvas $c, int $cx, int $bottom, int $h): void
    {
        $w = (int) ($h * 0.9);
        $c->draw($cx - $w, $bottom - $h - 4, 2 * $w, $h + 8, function (GdImage $l, int $k, callable $col) use ($w, $h) {
            $s = fn (float $v): int => (int) round($v * $k);
            $mx = $w;
            $y0 = 4;
            $dark = [58, 70, 110];
            $mid = [92, 112, 168];
            imagefilledrectangle($l, $s($mx - $h * 0.42), $s($y0 + $h * 0.92), $s($mx + $h * 0.42), $s($y0 + $h), $col($dark, 1.0));                  // base
            imagefilledrectangle($l, $s($mx - $h * 0.24), $s($y0 + $h * 0.62), $s($mx + $h * 0.3), $s($y0 + $h * 0.68), $col($mid, 1.0));              // stage
            imagesetthickness($l, max(3, (int) ($h * 0.07 * $k)));
            imagearc($l, $s($mx + $h * 0.1), $s($y0 + $h * 0.55), $s($h * 0.78), $s($h * 0.86), 270, 100, $col($dark, 1.0));                          // arm
            imagesetthickness($l, 1);
            $tube = [$s($mx - $h * 0.2), $s($y0 + $h * 0.0), $s($mx - $h * 0.06), $s($y0 - 1), $s($mx + $h * 0.1), $s($y0 + $h * 0.5), $s($mx - $h * 0.04), $s($y0 + $h * 0.55)];
            imagefilledpolygon($l, [$s($mx - $h * 0.12), $s($y0 + $h * 0.04), $s($mx + $h * 0.02), $s($y0 + $h * 0.0), $s($mx + $h * 0.12), $s($y0 + $h * 0.52), $s($mx - $h * 0.02), $s($y0 + $h * 0.56)], $col($mid, 1.0));
            imagefilledpolygon($l, [$s($mx - $h * 0.1), $s($y0 + $h * 0.5), $s($mx + $h * 0.04), $s($y0 + $h * 0.54), $s($mx + $h * 0.03), $s($y0 + $h * 0.6), $s($mx - $h * 0.09), $s($y0 + $h * 0.57)], $col($dark, 1.0));
            imagefilledrectangle($l, $s($mx - $h * 0.17), $s($y0 - $h * 0.01), $s($mx + $h * 0.0), $s($y0 + $h * 0.05), $col([250, 190, 60], 1.0));
            imagefilledrectangle($l, $s($mx - $h * 0.5), $s($y0 + $h * 0.88), $s($mx + $h * 0.5), $s($y0 + $h * 0.93), $col($mid, 1.0));
        });
    }

    /** Gold trophy cup. */
    public static function trophy(Canvas $c, int $cx, int $bottom, int $h): void
    {
        $w = (int) ($h * 0.8);
        $c->draw($cx - $w, $bottom - $h - 4, 2 * $w, $h + 8, function (GdImage $l, int $k, callable $col) use ($w, $h) {
            $s = fn (float $v): int => (int) round($v * $k);
            $mx = $w;
            $y = 4;
            $gold = [250, 196, 52];
            $dark = [214, 146, 24];
            imagesetthickness($l, max(3, (int) ($h * 0.05 * $k)));
            imagearc($l, $s($mx - $h * 0.3), $s($y + $h * 0.22), $s($h * 0.3), $s($h * 0.3), 90, 270, $col($dark, 1.0));
            imagearc($l, $s($mx + $h * 0.3), $s($y + $h * 0.22), $s($h * 0.3), $s($h * 0.3), 270, 90, $col($dark, 1.0));
            imagefilledpolygon($l, [$s($mx - $h * 0.32), $s($y), $s($mx + $h * 0.32), $s($y), $s($mx + $h * 0.26), $s($y + $h * 0.3), $s($mx + $h * 0.1), $s($y + $h * 0.5), $s($mx - $h * 0.1), $s($y + $h * 0.5), $s($mx - $h * 0.26), $s($y + $h * 0.3)], $col($gold, 1.0));
            imagefilledpolygon($l, [$s($mx - $h * 0.3), $s($y), $s($mx - $h * 0.18), $s($y), $s($mx - $h * 0.12), $s($y + $h * 0.42), $s($mx - $h * 0.22), $s($y + $h * 0.3)], $col([255, 232, 140], 0.9));
            imagefilledrectangle($l, $s($mx - $h * 0.05), $s($y + $h * 0.5), $s($mx + $h * 0.05), $s($y + $h * 0.72), $col($dark, 1.0));
            imagefilledpolygon($l, [$s($mx - $h * 0.2), $s($y + $h * 0.72), $s($mx + $h * 0.2), $s($y + $h * 0.72), $s($mx + $h * 0.26), $s($y + $h), $s($mx - $h * 0.26), $s($y + $h)], $col([70, 76, 110], 1.0));
            imagefilledrectangle($l, $s($mx - $h * 0.2), $s($y + $h * 0.72), $s($mx + $h * 0.2), $s($y + $h * 0.78), $col($gold, 1.0));
        });
    }

    /** Graduation cap with tassel. */
    public static function gradCap(Canvas $c, int $cx, int $cy, int $w, array $color = [38, 52, 110]): void
    {
        $h = (int) ($w * 0.8);
        $c->draw($cx - $w, $cy - $h, 2 * $w, 2 * $h, function (GdImage $l, int $k, callable $col) use ($w, $h, $color) {
            $s = fn (float $v): int => (int) round($v * $k);
            $mx = $w;
            $my = $h;
            imagefilledpolygon($l, [$s($mx - $w * 0.52), $s($my + $h * 0.02), $s($mx - $w * 0.52), $s($my + $h * 0.34), $s($mx), $s($my + $h * 0.56), $s($mx + $w * 0.52), $s($my + $h * 0.34), $s($mx + $w * 0.52), $s($my + $h * 0.02), $s($mx), $s($my + $h * 0.22)], $col(Color::darken($color, 0.2), 1.0));
            imagefilledpolygon($l, [$s($mx), $s($my - $h * 0.42), $s($mx + $w * 0.9), $s($my - $h * 0.02), $s($mx), $s($my + $h * 0.4), $s($mx - $w * 0.9), $s($my - $h * 0.02)], $col($color, 1.0));
            imagefilledpolygon($l, [$s($mx), $s($my - $h * 0.42), $s($mx + $w * 0.9), $s($my - $h * 0.02), $s($mx + $w * 0.45), $s($my + $h * 0.19), $s($mx - $w * 0.1), $s($my - $h * 0.28)], $col(Color::lighten($color, 0.12), 1.0));
            imagesetthickness($l, max(2, (int) ($k * 2)));
            imageline($l, $s($mx), $s($my - $h * 0.02), $s($mx + $w * 0.78), $s($my + $h * 0.08), $col([250, 196, 52], 1.0));
            imageline($l, $s($mx + $w * 0.78), $s($my + $h * 0.08), $s($mx + $w * 0.78), $s($my + $h * 0.5), $col([250, 196, 52], 1.0));
            imagefilledellipse($l, $s($mx), $s($my - $h * 0.02), $s($w * 0.1), $s($w * 0.1), $col([250, 196, 52], 1.0));
            imagefilledrectangle($l, $s($mx + $w * 0.74), $s($my + $h * 0.46), $s($mx + $w * 0.82), $s($my + $h * 0.62), $col([250, 196, 52], 1.0));
        });
    }

    /** Protractor, ruler and set square lying together. ($x, $bottom) is the bottom-left. */
    public static function geometrySet(Canvas $c, int $x, int $bottom, int $h): void
    {
        $w = (int) ($h * 2.0);
        $c->draw($x, $bottom - $h - 4, $w, $h + 8, function (GdImage $l, int $k, callable $col) use ($w, $h) {
            $s = fn (float $v): int => (int) round($v * $k);
            $yb = 4 + $h;
            // set square
            imagefilledpolygon($l, [$s($w * 0.5), $s($yb), $s($w * 0.5), $s($yb - $h * 0.86), $s($w * 0.5 + $h * 1.0), $s($yb)], $col([110, 200, 160], 0.72));
            imagepolygon($l, [$s($w * 0.5), $s($yb), $s($w * 0.5), $s($yb - $h * 0.86), $s($w * 0.5 + $h * 1.0), $s($yb)], $col([60, 150, 110], 1.0));
            imagefilledpolygon($l, [$s($w * 0.5 + $h * 0.14), $s($yb - $h * 0.1), $s($w * 0.5 + $h * 0.14), $s($yb - $h * 0.52), $s($w * 0.5 + $h * 0.5), $s($yb - $h * 0.1)], $col([255, 255, 255], 0.55));
            // protractor
            $r = $h * 0.78;
            $pcx = $w * 0.3;
            imagefilledarc($l, $s($pcx), $s($yb), $s(2 * $r), $s(2 * $r), 180, 360, $col([250, 214, 120], 0.8), IMG_ARC_PIE);
            imagearc($l, $s($pcx), $s($yb), $s(2 * $r), $s(2 * $r), 180, 360, $col([210, 150, 40], 1.0));
            imagefilledarc($l, $s($pcx), $s($yb), $s(1.1 * $r), $s(1.1 * $r), 180, 360, $col([255, 255, 255], 0.7), IMG_ARC_PIE);
            imagesetthickness($l, max(1, (int) ($k * 1.1)));
            for ($d = 0; $d <= 180; $d += 10) {
                $a = deg2rad(180 + $d);
                $len = $d % 30 === 0 ? 0.16 : 0.09;
                imageline($l, $s($pcx + cos($a) * $r * (1 - $len)), $s($yb + sin($a) * $r * (1 - $len)), $s($pcx + cos($a) * $r), $s($yb + sin($a) * $r), $col([150, 100, 20], 1.0));
            }
            // ruler
            $rw = $w * 0.96;
            imagefilledrectangle($l, $s(2), $s($yb - $h * 0.16), $s($rw), $s($yb), $col([250, 190, 56], 1.0));
            for ($i = 0; $i <= 30; $i++) {
                $tx = 6 + $i * ($rw - 10) / 30;
                imageline($l, $s($tx), $s($yb - $h * 0.16), $s($tx), $s($yb - $h * ($i % 5 === 0 ? 0.08 : 0.115)), $col([120, 80, 10], 1.0));
            }
        });
    }

    public static function calculator(Canvas $c, int $x, int $y, int $w, float $angle = 0.0): void
    {
        $h = (int) ($w * 1.4);
        $pad = (int) ($w * 0.3);
        $c->draw($x - $pad, $y - $pad, $w + 2 * $pad, $h + 2 * $pad, function (GdImage $l, int $k, callable $col) use ($w, $h, $pad, $angle) {
            $rot = deg2rad($angle);
            $cx = ($pad + $w / 2) * $k;
            $cy = ($pad + $h / 2) * $k;
            $tx = function (float $px, float $py) use ($rot, $cx, $cy, $w, $h, $k): array {
                $dx = ($px - $w / 2) * $k;
                $dy = ($py - $h / 2) * $k;

                return [(int) ($cx + $dx * cos($rot) - $dy * sin($rot)), (int) ($cy + $dx * sin($rot) + $dy * cos($rot))];
            };
            $rect = function (float $x0, float $y0, float $x1, float $y1, array $color, float $a = 1.0) use ($l, $tx, $col) {
                $flat = [];
                foreach ([[$x0, $y0], [$x1, $y0], [$x1, $y1], [$x0, $y1]] as [$px, $py]) {
                    array_push($flat, ...$tx($px, $py));
                }
                imagefilledpolygon($l, $flat, $col($color, $a));
            };
            $rect(5, 8, $w + 5, $h + 8, [0, 0, 0], 0.14);
            $rect(0, 0, $w, $h, [64, 72, 110]);
            $rect($w * 0.1, $h * 0.07, $w * 0.9, $h * 0.25, [178, 232, 206]);
            $colors = [[236, 240, 250], [236, 240, 250], [236, 240, 250], [250, 170, 60]];
            for ($r = 0; $r < 4; $r++) {
                for ($q = 0; $q < 4; $q++) {
                    $bx = $w * (0.1 + 0.2 * $q);
                    $by = $h * (0.34 + 0.155 * $r);
                    $rect($bx, $by, $bx + $w * 0.17, $by + $h * 0.115, $q === 3 ? $colors[3] : ($r === 3 && $q === 2 ? [240, 90, 100] : $colors[0]));
                }
            }
        });
    }

    /** Rocket pointing up-right. ($cx, $cy) is the centre. */
    public static function rocket(Canvas $c, int $cx, int $cy, int $h, float $angle = 40.0): void
    {
        $pad = (int) ($h * 0.85);
        $c->draw($cx - $pad, $cy - $pad, 2 * $pad, 2 * $pad, function (GdImage $l, int $k, callable $col) use ($pad, $h, $angle) {
            $rot = deg2rad($angle);
            $ox = $pad * $k;
            $oy = $pad * $k;
            $tx = function (float $px, float $py) use ($rot, $ox, $oy, $k): array {
                return [(int) ($ox + ($px * cos($rot) - $py * sin($rot)) * $k), (int) ($oy + ($px * sin($rot) + $py * cos($rot)) * $k)];
            };
            $poly = function (array $pts, array $color, float $a = 1.0) use ($l, $tx, $col) {
                $flat = [];
                foreach ($pts as [$px, $py]) {
                    array_push($flat, ...$tx($px, $py));
                }
                imagefilledpolygon($l, $flat, $col($color, $a));
            };
            $u = $h / 2;
            $poly([[-0.2 * $u, 0.95 * $u], [0.2 * $u, 0.95 * $u], [0.0, 1.7 * $u]], [255, 170, 40]);                                   // flame
            $poly([[-0.1 * $u, 0.95 * $u], [0.1 * $u, 0.95 * $u], [0.0, 1.4 * $u]], [255, 232, 120]);
            $poly([[-0.42 * $u, 0.3 * $u], [-0.42 * $u, 1.0 * $u], [-0.9 * $u, 1.05 * $u]], [226, 70, 80]);                            // fins
            $poly([[0.42 * $u, 0.3 * $u], [0.42 * $u, 1.0 * $u], [0.9 * $u, 1.05 * $u]], [226, 70, 80]);
            $pts = [[0, -1.4 * $u]];
            for ($i = 0; $i <= 10; $i++) {
                $a = $i / 10 * M_PI;
                $pts[] = [-sin($a) * 0.46 * $u * -1, -1.4 * $u + $i / 10 * 2.4 * $u];
            }
            $poly([[0, -1.45 * $u], [0.46 * $u, -0.4 * $u], [0.46 * $u, 0.95 * $u], [-0.46 * $u, 0.95 * $u], [-0.46 * $u, -0.4 * $u]], [244, 246, 252]);   // body
            $poly([[0, -1.45 * $u], [0.46 * $u, -0.4 * $u], [-0.46 * $u, -0.4 * $u]], [226, 70, 80]);                                  // nose
            $poly([[0.2 * $u, -0.4 * $u], [0.46 * $u, -0.4 * $u], [0.46 * $u, 0.95 * $u], [0.28 * $u, 0.95 * $u]], [208, 214, 232]);   // shade
            [$wx, $wy] = $tx(0, 0.0);
            imagefilledellipse($l, $wx, $wy, (int) (0.5 * $u * $k), (int) (0.5 * $u * $k), $col([60, 100, 200], 1.0));
            imagefilledellipse($l, $wx, $wy, (int) (0.34 * $u * $k), (int) (0.34 * $u * $k), $col([150, 200, 250], 1.0));
        });
    }

    public static function clock(Canvas $c, int $cx, int $cy, int $d, array $rim = [60, 100, 200]): void
    {
        $c->draw($cx - $d, $cy - $d, 2 * $d, 2 * $d, function (GdImage $l, int $k, callable $col) use ($d, $rim) {
            $s = $d * $k;
            $r = (int) ($s * 0.78);
            imagefilledellipse($l, $s, $s, 2 * $r, 2 * $r, $col($rim, 1.0));
            imagefilledellipse($l, $s, $s, (int) (2 * $r * 0.88), (int) (2 * $r * 0.88), $col([252, 252, 255], 1.0));
            imagesetthickness($l, max(2, (int) ($k * 2)));
            for ($i = 0; $i < 12; $i++) {
                $a = $i / 12 * M_PI * 2;
                imageline($l, (int) ($s + cos($a) * $r * 0.7), (int) ($s + sin($a) * $r * 0.7), (int) ($s + cos($a) * $r * 0.8), (int) ($s + sin($a) * $r * 0.8), $col([60, 66, 90], 1.0));
            }
            imagesetthickness($l, max(3, (int) ($k * 3)));
            imageline($l, $s, $s, (int) ($s + cos(-1.0) * $r * 0.5), (int) ($s + sin(-1.0) * $r * 0.5), $col([60, 66, 90], 1.0));
            imageline($l, $s, $s, (int) ($s + cos(0.2) * $r * 0.66), (int) ($s + sin(0.2) * $r * 0.66), $col([226, 70, 80], 1.0));
            imagefilledellipse($l, $s, $s, (int) ($r * 0.14), (int) ($r * 0.14), $col([60, 66, 90], 1.0));
            imagefilledrectangle($l, (int) ($s - $r * 0.08), (int) ($s - $r * 1.12), (int) ($s + $r * 0.08), (int) ($s - $r * 0.98), $col($rim, 1.0));
        });
    }

    public static function cloud(Canvas $c, int $cx, int $cy, int $w, array $color = [255, 255, 255], float $alpha = 0.9): void
    {
        $h = (int) ($w * 0.5);
        $c->draw($cx - $w, $cy - $h, 2 * $w, 2 * $h, function (GdImage $l, int $k, callable $col) use ($w, $h, $color, $alpha) {
            $s = fn (float $v): int => (int) round($v * $k);
            $cc = $col($color, $alpha);
            foreach ([[-0.42, 0.1, 0.34], [-0.1, -0.12, 0.42], [0.26, 0.0, 0.36], [0.55, 0.18, 0.26], [-0.62, 0.22, 0.22]] as [$px, $py, $r]) {
                imagefilledellipse($l, $s($w + $px * $w), $s($h + $py * $w), $s($r * $w * 2), $s($r * $w * 2), $cc);
            }
            imagefilledrectangle($l, $s($w - 0.62 * $w), $s($h + 0.18 * $w), $s($w + 0.62 * $w), $s($h + 0.42 * $w), $cc);
        });
    }

    /** Scattered confetti: small rectangles, circles and triangles in the given colours. */
    public static function confetti(Canvas $c, int $x, int $y, int $w, int $h, array $colors, int $count, int $seed): void
    {
        $rand = Canvas::seeded($seed);
        $unit = max(5, (int) ($h * 0.07));
        $c->draw($x, $y, $w, $h, function (GdImage $l, int $k, callable $col) use ($w, $h, $colors, $count, $rand, $unit) {
            for ($i = 0; $i < $count; $i++) {
                $px = $rand() * $w * $k;
                $py = $rand() * $h * $k;
                $u = $unit * $k * (0.5 + $rand() * 0.7);
                $cc = $col($colors[$i % count($colors)], 0.9);
                $a = $rand() * M_PI * 2;
                switch ($i % 3) {
                    case 0:
                        $pts = [];
                        foreach ([[-1, -0.4], [1, -0.4], [1, 0.4], [-1, 0.4]] as [$dx, $dy]) {
                            $pts[] = (int) ($px + ($dx * cos($a) - $dy * sin($a)) * $u);
                            $pts[] = (int) ($py + ($dx * sin($a) + $dy * cos($a)) * $u);
                        }
                        imagefilledpolygon($l, $pts, $cc);
                        break;
                    case 1:
                        imagefilledellipse($l, (int) $px, (int) $py, (int) ($u * 1.1), (int) ($u * 1.1), $cc);
                        break;
                    default:
                        $pts = [];
                        foreach ([[0, -1], [0.9, 0.7], [-0.9, 0.7]] as [$dx, $dy]) {
                            $pts[] = (int) ($px + ($dx * cos($a) - $dy * sin($a)) * $u * 0.9);
                            $pts[] = (int) ($py + ($dx * sin($a) + $dy * cos($a)) * $u * 0.9);
                        }
                        imagefilledpolygon($l, $pts, $cc);
                }
            }
        });
    }
}
