<?php

namespace Packitifotech\Laragdads\Ads\Templates;

use GdImage;
use Packitifotech\Laragdads\Ads\Support\Canvas;
use Packitifotech\Laragdads\Ads\Support\Color;
use Packitifotech\Laragdads\Ads\Support\Typography;

/**
 * Illustrated props for the subject-themed templates: finance, technology, history, civics, art, language, reasoning,
 * sports and the environment. Same conventions as Doodles / Props.
 */
final class SubjectProps
{
    private static function ink(): array
    {
        return [52, 58, 96];
    }

    /** Stack of gold coins. ($cx, $bottom) is the bottom centre. */
    public static function coins(Canvas $c, int $cx, int $bottom, int $w, int $count = 5): void
    {
        $th = (int) ($w * 0.17);
        $c->draw($cx - $w, $bottom - $count * $th - $w, 2 * $w, $count * $th + $w, function (GdImage $l, int $k, callable $col) use ($w, $count, $th) {
            $s = fn (float $v): int => (int) round($v * $k);
            $gold = [250, 196, 52];
            $dark = [208, 140, 20];
            $bottomY = $count * $th + $w * 0.4;
            for ($i = 0; $i < $count; $i++) {
                $y = $bottomY - $i * $th;
                $off = ($i % 2 ? 1 : -1) * $w * 0.04;
                imagefilledellipse($l, $s($w + $off), $s($y + $th * 0.5), $s($w * 1.2), $s($w * 0.4), $col($dark, 1.0));
                imagefilledrectangle($l, $s($w + $off - $w * 0.6), $s($y - $th * 0.5), $s($w + $off + $w * 0.6), $s($y + $th * 0.5), $col($gold, 1.0));
                imagefilledellipse($l, $s($w + $off), $s($y - $th * 0.5), $s($w * 1.2), $s($w * 0.4), $col([255, 224, 120], 1.0));
                imagefilledellipse($l, $s($w + $off), $s($y - $th * 0.5), $s($w * 0.82), $s($w * 0.26), $col($gold, 1.0));
            }
        });
    }

    /** Rising bar chart with an arrow. */
    public static function barChart(Canvas $c, int $x, int $bottom, int $w, int $h, array $color, array $arrow = [236, 80, 70]): void
    {
        $c->draw($x - 4, $bottom - $h - 8, $w + 8, $h + 12, function (GdImage $l, int $k, callable $cc) use ($w, $h, $color, $arrow) {
            $s = fn (float $v): int => (int) round($v * $k);
            $bars = [0.34, 0.5, 0.42, 0.7, 0.92];
            $bw = $w / (count($bars) * 1.5);
            foreach ($bars as $i => $f) {
                $bx = 4 + $i * $bw * 1.5;
                imagefilledrectangle($l, $s($bx), $s(8 + $h - $h * $f), $s($bx + $bw), $s(8 + $h), $cc(Color::lighten($color, $i * 0.08), 1.0));
            }
            imagesetthickness($l, max(3, (int) ($k * 3)));
            $pts = [[4, 8 + $h * 0.62], [$w * 0.3, 8 + $h * 0.42], [$w * 0.52, 8 + $h * 0.5], [$w * 0.98, 8 + $h * 0.04]];
            for ($i = 0; $i < 3; $i++) {
                imageline($l, $s($pts[$i][0]), $s($pts[$i][1]), $s($pts[$i + 1][0]), $s($pts[$i + 1][1]), $cc($arrow, 1.0));
            }
            imagefilledpolygon($l, [$s($w * 0.98), $s(8 + $h * 0.0), $s($w * 0.86), $s(8 + $h * 0.06), $s($w * 0.93), $s(8 + $h * 0.16)], $cc($arrow, 1.0));
        });
    }

    /** Laptop with a screen full of code bars. ($cx, $bottom) is the bottom centre. */
    public static function laptop(Canvas $c, int $cx, int $bottom, int $w): void
    {
        $h = (int) ($w * 0.72);
        $c->draw($cx - $w, $bottom - $h - 4, 2 * $w, $h + 8, function (GdImage $l, int $k, callable $col) use ($w, $h) {
            $s = fn (float $v): int => (int) round($v * $k);
            $mx = $w;
            $sw = $w * 0.82;
            $sh = $h * 0.78;
            imagefilledrectangle($l, $s($mx - $sw / 2), $s(4), $s($mx + $sw / 2), $s(4 + $sh), $col([60, 66, 96], 1.0));
            imagefilledrectangle($l, $s($mx - $sw / 2 + 5), $s(9), $s($mx + $sw / 2 - 5), $s(4 + $sh - 5), $col([28, 36, 66], 1.0));
            $rows = [[0.1, 0.4, [90, 200, 160]], [0.2, 0.6, [255, 190, 80]], [0.2, 0.3, [120, 170, 255]], [0.3, 0.55, [255, 120, 150]], [0.1, 0.45, [90, 200, 160]], [0.25, 0.35, [180, 150, 255]]];
            foreach ($rows as $i => [$ind, $len, $color]) {
                $y = 16 + $i * $sh * 0.12;
                imagefilledrectangle($l, $s($mx - $sw / 2 + 12 + $ind * $sw * 0.4), $s($y), $s($mx - $sw / 2 + 12 + $ind * $sw * 0.4 + $len * $sw * 0.7), $s($y + $sh * 0.05), $col($color, 1.0));
            }
            imagefilledpolygon($l, [$s($mx - $w * 0.55), $s(4 + $sh), $s($mx + $w * 0.55), $s(4 + $sh), $s($mx + $w * 0.98), $s(4 + $h), $s($mx - $w * 0.98), $s(4 + $h)], $col([210, 216, 232], 1.0));
            imagefilledrectangle($l, $s($mx - $w * 0.14), $s(4 + $sh), $s($mx + $w * 0.14), $s(4 + $sh + $h * 0.04), $col([160, 168, 196], 1.0));
        });
    }

    /** Circuit traces ending in pads, plus a chip. Region ($x, $y, $w, $h). */
    public static function circuit(Canvas $c, int $x, int $y, int $w, int $h, array $color, float $alpha = 0.7, int $seed = 3): void
    {
        $rand = Canvas::seeded($seed);
        $c->draw($x, $y, $w, $h, function (GdImage $l, int $k, callable $col) use ($w, $h, $color, $alpha, $rand) {
            imagesetthickness($l, max(2, (int) ($k * 1.6)));
            $line = $col($color, $alpha);
            $step = max(8, (int) ($h * 0.16));
            for ($i = 0; $i < 7; $i++) {
                $px = (int) ($rand() * $w / $step) * $step;
                $py = (int) ($rand() * $h / $step) * $step;
                for ($j = 0; $j < 4; $j++) {
                    $dir = (int) ($rand() * 3);
                    $nx = $px + ($dir === 0 ? $step * 2 : ($dir === 1 ? $step : 0));
                    $ny = $py + ($dir === 2 ? $step * 2 : ($dir === 1 ? $step : 0));
                    imageline($l, $px * $k, $py * $k, $nx * $k, $py * $k, $line);
                    imageline($l, $nx * $k, $py * $k, $nx * $k, $ny * $k, $line);
                    $px = $nx;
                    $py = $ny;
                }
                imagefilledellipse($l, $px * $k, $py * $k, (int) ($step * 0.45 * $k), (int) ($step * 0.45 * $k), $col($color, min(1.0, $alpha + 0.2)));
            }
        });
    }

    public static function chip(Canvas $c, int $cx, int $cy, int $size, array $color): void
    {
        $c->draw($cx - $size, $cy - $size, 2 * $size, 2 * $size, function (GdImage $l, int $k, callable $col) use ($size, $color) {
            $s = $size * $k;
            $r = (int) ($s * 0.42);
            imagesetthickness($l, max(2, (int) ($k * 2)));
            for ($i = -2; $i <= 2; $i++) {
                $o = (int) ($i * $r * 0.36);
                foreach ([[$s + $o, $s - $r - $s * 0.2, $s + $o, $s - $r], [$s + $o, $s + $r, $s + $o, $s + $r + $s * 0.2], [$s - $r - $s * 0.2, $s + $o, $s - $r, $s + $o], [$s + $r, $s + $o, $s + $r + $s * 0.2, $s + $o]] as [$a, $b, $cx2, $d]) {
                    imageline($l, (int) $a, (int) $b, (int) $cx2, (int) $d, $col($color, 1.0));
                }
            }
            imagefilledrectangle($l, $s - $r, $s - $r, $s + $r, $s + $r, $col($color, 1.0));
            imagefilledrectangle($l, (int) ($s - $r * 0.62), (int) ($s - $r * 0.62), (int) ($s + $r * 0.62), (int) ($s + $r * 0.62), $col(Color::lighten($color, 0.3), 1.0));
            imagefilledellipse($l, (int) ($s - $r * 0.3), (int) ($s - $r * 0.3), (int) ($r * 0.3), (int) ($r * 0.3), $col([255, 255, 255], 0.8));
        });
    }

    /** Scales of justice. */
    public static function scales(Canvas $c, int $cx, int $bottom, int $h, array $color): void
    {
        $w = (int) ($h * 0.95);
        $c->draw($cx - $w, $bottom - $h - 4, 2 * $w, $h + 8, function (GdImage $l, int $k, callable $col) use ($w, $h, $color) {
            $s = fn (float $v): int => (int) round($v * $k);
            $mx = $w;
            $y = 4;
            imagesetthickness($l, max(3, (int) ($h * 0.035 * $k)));
            $cc = $col($color, 1.0);
            imageline($l, $s($mx), $s($y + $h * 0.08), $s($mx), $s($y + $h * 0.9), $cc);
            imageline($l, $s($mx - $w * 0.78), $s($y + $h * 0.18), $s($mx + $w * 0.78), $s($y + $h * 0.18), $cc);
            imagefilledellipse($l, $s($mx), $s($y + $h * 0.07), $s($h * 0.1), $s($h * 0.1), $cc);
            imagefilledpolygon($l, [$s($mx - $w * 0.4), $s($y + $h * 0.96), $s($mx + $w * 0.4), $s($y + $h * 0.96), $s($mx + $w * 0.24), $s($y + $h * 0.88), $s($mx - $w * 0.24), $s($y + $h * 0.88)], $cc);
            foreach ([-1, 1] as $side) {
                $px = $mx + $side * $w * 0.78;
                imagesetthickness($l, max(1, (int) ($k * 1.4)));
                imageline($l, $s($px), $s($y + $h * 0.18), $s($px - $w * 0.28), $s($y + $h * 0.55), $cc);
                imageline($l, $s($px), $s($y + $h * 0.18), $s($px + $w * 0.28), $s($y + $h * 0.55), $cc);
                imagefilledarc($l, $s($px), $s($y + $h * 0.52), $s($w * 0.62), $s($h * 0.26), 0, 180, $col($color, 1.0), IMG_ARC_PIE);
            }
        });
    }

    /** A row of classical columns with a pediment. ($x, $bottom) is the bottom-left. */
    public static function columns(Canvas $c, int $x, int $bottom, int $w, int $h, array $color): void
    {
        $c->draw($x, $bottom - $h - 4, $w, $h + 8, function (GdImage $l, int $k, callable $col) use ($w, $h, $color) {
            $s = fn (float $v): int => (int) round($v * $k);
            $y = 4;
            imagefilledpolygon($l, [$s(0), $s($y + $h * 0.22), $s($w / 2), $s($y), $s($w), $s($y + $h * 0.22)], $col(Color::lighten($color, 0.1), 1.0));
            imagefilledrectangle($l, $s(0), $s($y + $h * 0.22), $s($w), $s($y + $h * 0.3), $col($color, 1.0));
            $n = 4;
            $cw = $w / ($n * 1.6);
            for ($i = 0; $i < $n; $i++) {
                $cx = $w * (0.12 + $i * 0.253);
                imagefilledrectangle($l, $s($cx - $cw / 2), $s($y + $h * 0.3), $s($cx + $cw / 2), $s($y + $h * 0.86), $col(Color::lighten($color, 0.18), 1.0));
                imagefilledrectangle($l, $s($cx - $cw * 0.75), $s($y + $h * 0.3), $s($cx + $cw * 0.75), $s($y + $h * 0.34), $col($color, 1.0));
                imagefilledrectangle($l, $s($cx - $cw * 0.75), $s($y + $h * 0.82), $s($cx + $cw * 0.75), $s($y + $h * 0.86), $col($color, 1.0));
            }
            imagefilledrectangle($l, $s(0), $s($y + $h * 0.86), $s($w), $s($y + $h), $col($color, 1.0));
        });
    }

    /** Fort / castle with two towers and flags. */
    public static function fort(Canvas $c, int $x, int $bottom, int $w, array $color): void
    {
        $h = (int) ($w * 0.62);
        $c->draw($x, $bottom - $h - 4, $w, $h + 8, function (GdImage $l, int $k, callable $col) use ($w, $h, $color) {
            $s = fn (float $v): int => (int) round($v * $k);
            $y = 4;
            $dark = Color::darken($color, 0.18);
            $battle = function (float $x0, float $x1, float $top) use ($l, $s, $col, $dark) {
                $n = 4;
                $bw = ($x1 - $x0) / ($n * 2 - 1);
                for ($i = 0; $i < $n; $i++) {
                    imagefilledrectangle($l, $s($x0 + $i * 2 * $bw), $s($top - $bw), $s($x0 + $i * 2 * $bw + $bw), $s($top), $col($dark, 1.0));
                }
            };
            imagefilledrectangle($l, $s($w * 0.28), $s($y + $h * 0.42), $s($w * 0.72), $s($y + $h), $col($color, 1.0));
            $battle($w * 0.28, $w * 0.72, $y + $h * 0.42);
            foreach ([[0.04, 0.26], [0.74, 0.96]] as [$a, $b]) {
                imagefilledrectangle($l, $s($w * $a), $s($y + $h * 0.2), $s($w * $b), $s($y + $h), $col(Color::lighten($color, 0.08), 1.0));
                $battle($w * $a, $w * $b, $y + $h * 0.2);
                imagefilledrectangle($l, $s($w * ($a + $b) / 2 - 3), $s($y + $h * 0.42), $s($w * ($a + $b) / 2 + 3), $s($y + $h * 0.56), $col([40, 40, 60], 1.0));
                imageline($l, $s($w * ($a + $b) / 2), $s($y + $h * 0.2 - $h * 0.1), $s($w * ($a + $b) / 2), $s($y - 2 + $h * 0.0), $col($dark, 1.0));
                imagefilledpolygon($l, [$s($w * ($a + $b) / 2), $s($y), $s($w * ($a + $b) / 2 + $w * 0.07), $s($y + $h * 0.05), $s($w * ($a + $b) / 2), $s($y + $h * 0.1)], $col([226, 70, 80], 1.0));
            }
            imagefilledpolygon($l, [$s($w * 0.42), $s($y + $h), $s($w * 0.42), $s($y + $h * 0.72), $s($w * 0.5), $s($y + $h * 0.62), $s($w * 0.58), $s($y + $h * 0.72), $s($w * 0.58), $s($y + $h)], $col([54, 44, 52], 1.0));
        });
    }

    /** Rolled scroll with a seal. */
    public static function scroll(Canvas $c, int $cx, int $cy, int $w, float $angle = -8.0): void
    {
        $h = (int) ($w * 0.5);
        $pad = (int) ($w * 0.2);
        $c->draw($cx - $w / 2 - $pad, $cy - $h / 2 - $pad, $w + 2 * $pad, $h + 2 * $pad, function (GdImage $l, int $k, callable $col) use ($w, $h, $pad, $angle) {
            $rot = deg2rad($angle);
            $ox = ($pad + $w / 2) * $k;
            $oy = ($pad + $h / 2) * $k;
            $tx = function (float $px, float $py) use ($rot, $ox, $oy, $k, $w, $h): array {
                $dx = ($px - $w / 2) * $k;
                $dy = ($py - $h / 2) * $k;

                return [(int) ($ox + $dx * cos($rot) - $dy * sin($rot)), (int) ($oy + $dx * sin($rot) + $dy * cos($rot))];
            };
            $poly = function (array $pts, array $color) use ($l, $tx, $col) {
                $flat = [];
                foreach ($pts as [$px, $py]) {
                    array_push($flat, ...$tx($px, $py));
                }
                imagefilledpolygon($l, $flat, $col($color, 1.0));
            };
            $poly([[$w * 0.08, 0], [$w * 0.92, 0], [$w * 0.92, $h], [$w * 0.08, $h]], [246, 232, 196]);
            foreach ([0.28, 0.42, 0.56, 0.7] as $f) {
                $poly([[$w * 0.2, $h * $f], [$w * ($f === 0.7 ? 0.5 : 0.78), $h * $f], [$w * ($f === 0.7 ? 0.5 : 0.78), $h * ($f + 0.04)], [$w * 0.2, $h * ($f + 0.04)]], [186, 150, 96]);
            }
            foreach ([0.0, 0.92] as $f) {
                $poly([[$w * $f, -$h * 0.06], [$w * ($f + 0.08), -$h * 0.06], [$w * ($f + 0.08), $h * 1.06], [$w * $f, $h * 1.06]], [196, 120, 70]);
            }
            [$sx, $sy] = $tx($w * 0.78, $h * 0.82);
            imagefilledellipse($l, $sx, $sy, (int) ($h * 0.3 * $k), (int) ($h * 0.3 * $k), $col([196, 50, 60], 1.0));
        });
    }

    /** Mountains with a snow cap, in a row. */
    public static function mountains(Canvas $c, int $x, int $bottom, int $w, int $h, array $color): void
    {
        $c->draw($x, $bottom - $h - 2, $w, $h + 4, function (GdImage $l, int $k, callable $col) use ($w, $h, $color) {
            $s = fn (float $v): int => (int) round($v * $k);
            foreach ([[0.18, 0.62, 0.34], [0.5, 1.0, 0.42], [0.82, 0.7, 0.32]] as $i => [$cx, $ph, $hw]) {
                $top = 2 + $h * (1 - $ph);
                imagefilledpolygon($l, [$s($w * ($cx - $hw)), $s(2 + $h), $s($w * $cx), $s($top), $s($w * ($cx + $hw)), $s(2 + $h)], $col(Color::darken($color, $i === 1 ? 0.0 : 0.12), 1.0));
                imagefilledpolygon($l, [$s($w * $cx), $s($top), $s($w * ($cx + $hw)), $s(2 + $h), $s($w * $cx), $s(2 + $h)], $col(Color::darken($color, 0.18), 0.6));
                $sn = $hw * 0.36;
                imagefilledpolygon($l, [$s($w * $cx), $s($top), $s($w * ($cx + $sn)), $s($top + $h * $ph * 0.26), $s($w * ($cx + $sn * 0.4)), $s($top + $h * $ph * 0.2), $s($w * $cx), $s($top + $h * $ph * 0.3), $s($w * ($cx - $sn * 0.5)), $s($top + $h * $ph * 0.2), $s($w * ($cx - $sn)), $s($top + $h * $ph * 0.26)], $col([255, 255, 255], 1.0));
            }
        });
    }

    public static function mapPin(Canvas $c, int $cx, int $bottom, int $h, array $color): void
    {
        $w = (int) ($h * 0.7);
        $c->draw($cx - $w, $bottom - $h - 2, 2 * $w, $h + 4, function (GdImage $l, int $k, callable $col) use ($w, $h, $color) {
            $s = fn (float $v): int => (int) round($v * $k);
            $r = $h * 0.32;
            $cy = 2 + $r;
            imagefilledellipse($l, $s($w), $s($cy), $s(2 * $r), $s(2 * $r), $col($color, 1.0));
            imagefilledpolygon($l, [$s($w - $r * 0.92), $s($cy + $r * 0.4), $s($w + $r * 0.92), $s($cy + $r * 0.4), $s($w), $s(2 + $h)], $col($color, 1.0));
            imagefilledellipse($l, $s($w), $s($cy), $s($r), $s($r), $col([255, 255, 255], 1.0));
        });
    }

    /** Artist's palette with paint dabs. */
    public static function palette(Canvas $c, int $cx, int $cy, int $w): void
    {
        $h = (int) ($w * 0.78);
        $c->draw($cx - $w, $cy - $h, 2 * $w, 2 * $h, function (GdImage $l, int $k, callable $col) use ($w, $h) {
            $s = fn (float $v): int => (int) round($v * $k);
            $mx = $w;
            $my = $h;
            imagefilledellipse($l, $s($mx), $s($my), $s($w * 1.8), $s($h * 1.5), $col([220, 168, 110], 1.0));
            imagefilledellipse($l, $s($mx + $w * 0.5), $s($my + $h * 0.36), $s($w * 0.4), $s($h * 0.36), $col([245, 245, 250], 1.0));  // thumb hole bg
            imagefilledellipse($l, $s($mx + $w * 0.5), $s($my + $h * 0.36), $s($w * 0.34), $s($h * 0.3), $col([255, 244, 226], 1.0));
            foreach ([[-0.6, -0.34, [226, 60, 70]], [-0.2, -0.52, [250, 190, 50]], [0.26, -0.5, [60, 160, 90]], [0.6, -0.2, [60, 110, 220]], [-0.72, 0.12, [150, 80, 200]]] as [$dx, $dy, $color]) {
                imagefilledellipse($l, $s($mx + $dx * $w), $s($my + $dy * $h), $s($w * 0.3), $s($h * 0.28), $col($color, 1.0));
                imagefilledellipse($l, $s($mx + $dx * $w - $w * 0.05), $s($my + $dy * $h - $h * 0.05), $s($w * 0.1), $s($h * 0.08), $col([255, 255, 255], 0.5));
            }
        });
    }

    /** Paint brush, tilted. */
    public static function brush(Canvas $c, int $cx, int $cy, int $len, array $paint, float $angle = 45.0): void
    {
        $pad = (int) ($len * 0.6);
        $c->draw($cx - $pad, $cy - $pad, 2 * $pad, 2 * $pad, function (GdImage $l, int $k, callable $col) use ($pad, $len, $paint, $angle) {
            $rot = deg2rad($angle);
            $ox = $pad * $k;
            $oy = $pad * $k;
            $tx = fn (float $u, float $v): array => [(int) ($ox + ($u * cos($rot) - $v * sin($rot)) * $k), (int) ($oy + ($u * sin($rot) + $v * cos($rot)) * $k)];
            $poly = function (array $pts, array $color) use ($l, $tx, $col) {
                $flat = [];
                foreach ($pts as [$u, $v]) {
                    array_push($flat, ...$tx($u, $v));
                }
                imagefilledpolygon($l, $flat, $col($color, 1.0));
            };
            $t = $len * 0.045;
            $poly([[-$len * 0.5, -$t], [$len * 0.12, -$t * 0.8], [$len * 0.12, $t * 0.8], [-$len * 0.5, $t]], [212, 150, 84]);           // handle
            $poly([[$len * 0.12, -$t * 1.3], [$len * 0.26, -$t * 1.3], [$len * 0.26, $t * 1.3], [$len * 0.12, $t * 1.3]], [190, 196, 214]); // ferrule
            $poly([[$len * 0.26, -$t * 1.3], [$len * 0.46, -$t * 0.6], [$len * 0.5, 0], [$len * 0.46, $t * 0.6], [$len * 0.26, $t * 1.3]], $paint);
        });
    }

    public static function puzzle(Canvas $c, int $cx, int $cy, int $size, array $color, float $angle = 0.0): void
    {
        $c->draw($cx - $size, $cy - $size, 2 * $size, 2 * $size, function (GdImage $l, int $k, callable $col) use ($size, $color, $angle) {
            $s = $size * $k;
            $rot = deg2rad($angle);
            $cc = $col($color, 1.0);
            $u = $s * 0.5;
            $tx = fn (float $x, float $y): array => [(int) ($s + ($x * cos($rot) - $y * sin($rot))), (int) ($s + ($x * sin($rot) + $y * cos($rot)))];
            $quad = function (float $x0, float $y0, float $x1, float $y1) use ($l, $tx, $cc) {
                $flat = [];
                foreach ([[$x0, $y0], [$x1, $y0], [$x1, $y1], [$x0, $y1]] as [$x, $y]) {
                    array_push($flat, ...$tx($x, $y));
                }
                imagefilledpolygon($l, $flat, $cc);
            };
            $quad(-$u, -$u, $u, $u);
            foreach ([[0, -$u, -1], [$u, 0, 1]] as [$kx, $ky, $dir]) {
                [$ex, $ey] = $tx($kx, $ky);
                imagefilledellipse($l, $ex, $ey, (int) ($u * 0.7), (int) ($u * 0.7), $cc);
            }
            foreach ([[-$u, 0], [0, $u]] as [$kx, $ky]) {
                [$ex, $ey] = $tx($kx, $ky);
                imagefilledellipse($l, $ex, $ey, (int) ($u * 0.7), (int) ($u * 0.7), $col([255, 255, 255], 1.0));
            }
        });
    }

    public static function medal(Canvas $c, int $cx, int $cy, int $d, array $ribbon = [226, 60, 70]): void
    {
        $c->draw($cx - $d, $cy - $d, 2 * $d, 2 * $d, function (GdImage $l, int $k, callable $col) use ($d, $ribbon) {
            $s = $d * $k;
            imagefilledpolygon($l, [(int) ($s * 0.52), 0, (int) ($s * 0.82), 0, (int) ($s * 1.1), (int) ($s * 0.8), (int) ($s * 0.82), (int) ($s * 0.8)], $col($ribbon, 1.0));
            imagefilledpolygon($l, [(int) ($s * 1.48), 0, (int) ($s * 1.18), 0, (int) ($s * 0.9), (int) ($s * 0.8), (int) ($s * 1.18), (int) ($s * 0.8)], $col(Color::darken($ribbon, 0.2), 1.0));
            $r = (int) ($s * 0.5);
            imagefilledellipse($l, $s, (int) ($s * 1.3), 2 * $r, 2 * $r, $col([214, 146, 24], 1.0));
            imagefilledellipse($l, $s, (int) ($s * 1.3), (int) (1.76 * $r), (int) (1.76 * $r), $col([250, 196, 52], 1.0));
            imagefilledellipse($l, $s, (int) ($s * 1.3), (int) (1.2 * $r), (int) (1.2 * $r), $col([255, 226, 120], 1.0));
            $pts = [];
            for ($i = 0; $i < 10; $i++) {
                $rr = $i % 2 === 0 ? 0.42 : 0.17;
                $a = -M_PI / 2 + $i * M_PI / 5;
                $pts[] = (int) ($s + cos($a) * $r * $rr * 2);
                $pts[] = (int) ($s * 1.3 + sin($a) * $r * $rr * 2);
            }
            imagefilledpolygon($l, $pts, $col([214, 146, 24], 1.0));
        });
    }

    public static function shield(Canvas $c, int $cx, int $cy, int $h, array $color): void
    {
        $w = (int) ($h * 0.8);
        $c->draw($cx - $w, $cy - $h, 2 * $w, 2 * $h, function (GdImage $l, int $k, callable $col) use ($w, $h, $color) {
            $s = fn (float $v): int => (int) round($v * $k);
            $mx = $w;
            $my = $h;
            $pts = fn (float $f): array => [$s($mx - $w * 0.62 * $f), $s($my - $h * 0.78 * $f), $s($mx + $w * 0.62 * $f), $s($my - $h * 0.78 * $f), $s($mx + $w * 0.62 * $f), $s($my + $h * 0.1 * $f), $s($mx), $s($my + $h * 0.82 * $f), $s($mx - $w * 0.62 * $f), $s($my + $h * 0.1 * $f)];
            imagefilledpolygon($l, $pts(1.0), $col([250, 196, 52], 1.0));
            imagefilledpolygon($l, $pts(0.88), $col($color, 1.0));
            $star = [];
            for ($i = 0; $i < 10; $i++) {
                $rr = $i % 2 === 0 ? 0.34 : 0.14;
                $a = -M_PI / 2 + $i * M_PI / 5;
                $star[] = $s($mx + cos($a) * $h * $rr);
                $star[] = $s($my - $h * 0.06 + sin($a) * $h * $rr);
            }
            imagefilledpolygon($l, $star, $col([250, 196, 52], 1.0));
        });
    }

    public static function waterDrop(Canvas $c, int $cx, int $bottom, int $h, array $color): void
    {
        $w = (int) ($h * 0.7);
        $c->draw($cx - $w, $bottom - $h - 2, 2 * $w, $h + 4, function (GdImage $l, int $k, callable $col) use ($w, $h, $color) {
            $s = fn (float $v): int => (int) round($v * $k);
            $r = $h * 0.34;
            $cy = 2 + $h - $r;
            imagefilledellipse($l, $s($w), $s($cy), $s(2 * $r), $s(2 * $r), $col($color, 1.0));
            imagefilledpolygon($l, [$s($w - $r * 0.94), $s($cy - $r * 0.3), $s($w + $r * 0.94), $s($cy - $r * 0.3), $s($w), $s(2)], $col($color, 1.0));
            imagefilledellipse($l, $s($w - $r * 0.35), $s($cy + $r * 0.05), $s($r * 0.4), $s($r * 0.7), $col([255, 255, 255], 0.55));
        });
    }

    public static function heart(Canvas $c, int $cx, int $cy, int $size, array $color): void
    {
        $c->draw($cx - $size, $cy - $size, 2 * $size, 2 * $size, function (GdImage $l, int $k, callable $col) use ($size, $color) {
            $s = $size * $k;
            $pts = [];
            for ($i = 0; $i < 90; $i++) {
                $t = $i / 90 * M_PI * 2;
                $x = 16 * sin($t) ** 3;
                $y = -(13 * cos($t) - 5 * cos(2 * $t) - 2 * cos(3 * $t) - cos(4 * $t));
                $pts[] = (int) ($s + $x * $s / 20);
                $pts[] = (int) ($s * 0.95 + $y * $s / 20);
            }
            imagefilledpolygon($l, $pts, $col($color, 1.0));
            imagefilledellipse($l, (int) ($s * 0.68), (int) ($s * 0.62), (int) ($s * 0.22), (int) ($s * 0.16), $col([255, 255, 255], 0.5));
        });
    }

    /** Brain, drawn as two lobes with folds. */
    public static function brain(Canvas $c, int $cx, int $cy, int $size, array $color): void
    {
        $c->draw($cx - $size, $cy - $size, 2 * $size, 2 * $size, function (GdImage $l, int $k, callable $col) use ($size, $color) {
            $s = $size * $k;
            foreach ([[-0.34, 0.0], [0.34, 0.0]] as [$dx, $dy]) {
                $pts = Canvas::blobPoints($s + $dx * $s, $s + $dy * $s, $s * 0.5, $s * 0.58, 4 + (int) ($dx * 10), 0.12, 1);
                imagefilledpolygon($l, $pts, $col($color, 1.0));
            }
            imagefilledrectangle($l, (int) ($s * 0.9), (int) ($s * 0.5), (int) ($s * 1.1), (int) ($s * 1.5), $col($color, 1.0));
            imagesetthickness($l, max(2, (int) ($k * 2)));
            $fold = $col(Color::darken($color, 0.28), 1.0);
            foreach ([[0.62, 0.62, 0.2], [0.55, 1.0, 0.2], [1.38, 0.62, 0.2], [1.45, 1.0, 0.2], [0.8, 1.2, 0.14], [1.2, 1.2, 0.14]] as [$fx, $fy, $r]) {
                imagearc($l, (int) ($s * $fx), (int) ($s * $fy), (int) ($s * $r * 2), (int) ($s * $r * 2), 200, 340, $fold);
            }
            imageline($l, $s, (int) ($s * 0.45), $s, (int) ($s * 1.45), $fold);
            imagefilledellipse($l, (int) ($s * 0.55), (int) ($s * 0.52), (int) ($s * 0.3), (int) ($s * 0.18), $col([255, 255, 255], 0.35));
        });
    }

    /** Speech bubble with three dots. */
    public static function bubble(Canvas $c, int $cx, int $cy, int $w, array $color, array $dots = [255, 255, 255], bool $flip = false): void
    {
        $h = (int) ($w * 0.72);
        $c->draw($cx - $w, $cy - $h, 2 * $w, 2 * $h, function (GdImage $l, int $k, callable $col) use ($w, $h, $color, $dots, $flip) {
            $s = fn (float $v): int => (int) round($v * $k);
            $mx = $w;
            $my = $h;
            $bw = $w * 0.9;
            $bh = $h * 0.55;
            $r = $bh * 0.5;
            imagefilledrectangle($l, $s($mx - $bw + $r), $s($my - $bh), $s($mx + $bw - $r), $s($my + $bh), $col($color, 1.0));
            imagefilledellipse($l, $s($mx - $bw + $r), $s($my), $s(2 * $r * 1.0), $s(2 * $bh), $col($color, 1.0));
            imagefilledellipse($l, $s($mx + $bw - $r), $s($my), $s(2 * $r * 1.0), $s(2 * $bh), $col($color, 1.0));
            $f = $flip ? -1 : 1;
            imagefilledpolygon($l, [$s($mx - $f * $bw * 0.5), $s($my + $bh * 0.9), $s($mx - $f * $bw * 0.2), $s($my + $bh * 0.9), $s($mx - $f * $bw * 0.6), $s($my + $bh * 1.55)], $col($color, 1.0));
            foreach ([-0.3, 0, 0.3] as $d) {
                imagefilledellipse($l, $s($mx + $d * $bw), $s($my), $s($bh * 0.3), $s($bh * 0.3), $col($dots, 1.0));
            }
        });
    }

    /** Alphabet blocks ("A", "B", "C") stacked. */
    public static function blocks(Canvas $c, int $x, int $bottom, int $size, Typography $font, array $letters = ['A', 'B', 'C']): void
    {
        $colors = [[236, 84, 90], [250, 190, 50], [70, 130, 230], [80, 180, 120]];
        $positions = [[0, 0], [1.08, 0], [0.54, -1.08]];
        foreach (array_slice($letters, 0, 3) as $i => $letter) {
            [$px, $py] = $positions[$i];
            $bx = $x + (int) ($px * $size);
            $by = $bottom + (int) ($py * $size) - $size;
            $col = $colors[$i % 4];
            $c->shadow($bx, $by + $size - 4, $size, 8, 3, 10, 0.2, 2);
            $c->rect($bx, $by, $size, $size, $col, 1.0, (int) ($size * 0.12));
            $c->rect($bx + 3, $by + 3, $size - 6, (int) ($size * 0.18), Color::lighten($col, 0.3), 0.5, (int) ($size * 0.1));
            $fs = (int) ($size * 0.62);
            $tw = $font->measure($letter, $fs)[0];
            $c->lines([$letter], $font, $fs, $bx + intdiv($size - $tw, 2), $by + (int) ($size * 0.12), $size, [255, 255, 255], 'left', (int) ($fs * 1.2));
        }
    }

    /** Feather quill and inkwell. */
    public static function quill(Canvas $c, int $cx, int $cy, int $h, array $color): void
    {
        $pad = (int) ($h * 0.7);
        $c->draw($cx - $pad, $cy - $pad, 2 * $pad, 2 * $pad, function (GdImage $l, int $k, callable $col) use ($pad, $h, $color) {
            $s = fn (float $v): int => (int) round($v * $k);
            $ox = $pad;
            $oy = $pad;
            $rot = deg2rad(38);
            $tx = fn (float $u, float $v): array => [$s($ox + $u * cos($rot) - $v * sin($rot)), $s($oy + $u * sin($rot) + $v * cos($rot))];
            $pts = [];
            for ($i = 0; $i <= 20; $i++) {
                $t = $i / 20;
                array_push($pts, ...$tx(-$h * 0.5 + $t * $h * 0.86, -sin($t * M_PI) * $h * 0.13 * (1 - 0.2 * $t)));
            }
            for ($i = 20; $i >= 0; $i--) {
                $t = $i / 20;
                array_push($pts, ...$tx(-$h * 0.5 + $t * $h * 0.86, sin($t * M_PI) * $h * 0.13 * (1 - 0.2 * $t)));
            }
            imagefilledpolygon($l, $pts, $col($color, 1.0));
            imagesetthickness($l, max(2, (int) ($k * 1.5)));
            $a = $tx(-$h * 0.5, 0);
            $b = $tx($h * 0.36, 0);
            imageline($l, $a[0], $a[1], $b[0], $b[1], $col(Color::darken($color, 0.3), 1.0));
            $n = $tx($h * 0.36, 0);
            $tip = $tx($h * 0.5, 0);
            imageline($l, $n[0], $n[1], $tip[0], $tip[1], $col([60, 60, 80], 1.0));
        });
    }

    /** Sports: a football (hexagon-patch ball). */
    public static function ball(Canvas $c, int $cx, int $cy, int $d): void
    {
        $c->draw($cx - $d, $cy - $d, 2 * $d, 2 * $d, function (GdImage $l, int $k, callable $col) use ($d) {
            $s = $d * $k;
            $r = (int) ($s * 0.82);
            imagefilledellipse($l, $s, $s, 2 * $r, 2 * $r, $col([250, 250, 252], 1.0));
            $pent = function (float $x, float $y, float $rad) use ($l, $col) {
                $pts = [];
                for ($i = 0; $i < 5; $i++) {
                    $a = -M_PI / 2 + $i * M_PI * 2 / 5;
                    $pts[] = (int) ($x + cos($a) * $rad);
                    $pts[] = (int) ($y + sin($a) * $rad);
                }
                imagefilledpolygon($l, $pts, $col([44, 52, 80], 1.0));
            };
            $pent($s, $s, $r * 0.3);
            foreach ([0, 72, 144, 216, 288] as $deg) {
                $a = deg2rad($deg - 90);
                $pent($s + cos($a) * $r * 0.78, $s + sin($a) * $r * 0.78, $r * 0.2);
            }
            imagesetthickness($l, max(2, (int) ($k * 1.5)));
            imageellipse($l, $s, $s, 2 * $r, 2 * $r, $col([150, 156, 180], 1.0));
        });
    }

    /** Three tilted leaves growing from a point: the "environment" sprout. */
    public static function sprout(Canvas $c, int $cx, int $bottom, int $h, array $green = [70, 170, 80]): void
    {
        $w = (int) ($h * 0.8);
        $c->draw($cx - $w, $bottom - $h - 2, 2 * $w, $h + 4, function (GdImage $l, int $k, callable $col) use ($w, $h, $green) {
            $s = fn (float $v): int => (int) round($v * $k);
            imagesetthickness($l, max(3, (int) ($h * 0.05 * $k)));
            imageline($l, $s($w), $s(2 + $h), $s($w), $s(2 + $h * 0.45), $col([50, 130, 60], 1.0));
            foreach ([-1, 1] as $side) {
                $pts = [];
                for ($i = 0; $i <= 24; $i++) {
                    $t = $i / 24;
                    $pts[] = $s($w + $side * ($w * 0.9) * $t * (1 - 0.15 * $t) - $side * sin($t * M_PI) * $h * 0.05);
                    $pts[] = $s(2 + $h * 0.5 - $h * 0.34 * sin($t * M_PI * 0.9) - $t * $h * 0.05 + sin($t * M_PI) * $h * 0.22);
                }
                for ($i = 24; $i >= 0; $i--) {
                    $t = $i / 24;
                    $pts[] = $s($w + $side * ($w * 0.9) * $t * (1 - 0.15 * $t));
                    $pts[] = $s(2 + $h * 0.5 - $h * 0.34 * sin($t * M_PI * 0.9) - $t * $h * 0.05 - sin($t * M_PI) * $h * 0.08);
                }
                imagefilledpolygon($l, $pts, $col(Color::lighten($green, $side > 0 ? 0.1 : 0.0), 1.0));
            }
        });
    }

    /** Recycling-style three chasing arrows in a circle. */
    public static function cycle(Canvas $c, int $cx, int $cy, int $d, array $color): void
    {
        $c->draw($cx - $d, $cy - $d, 2 * $d, 2 * $d, function (GdImage $l, int $k, callable $col) use ($d, $color) {
            $s = $d * $k;
            $r = (int) ($s * 0.72);
            imagesetthickness($l, max(4, (int) ($d * 0.12 * $k)));
            foreach ([0, 120, 240] as $start) {
                imagearc($l, $s, $s, 2 * $r, 2 * $r, $start + 8, $start + 92, $col($color, 1.0));
                $a = deg2rad($start + 98);
                $ex = $s + cos($a) * $r;
                $ey = $s + sin($a) * $r;
                $tan = $a + M_PI / 2;
                imagefilledpolygon($l, [(int) ($ex + cos($tan) * $r * 0.22), (int) ($ey + sin($tan) * $r * 0.22), (int) ($ex + cos($a) * $r * 0.2), (int) ($ey + sin($a) * $r * 0.2), (int) ($ex - cos($a) * $r * 0.2), (int) ($ey - sin($a) * $r * 0.2)], $col($color, 1.0));
            }
        });
    }
}
