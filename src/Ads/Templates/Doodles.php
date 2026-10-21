<?php

namespace Packitifotech\Laragdads\Ads\Templates;

use GdImage;
use Packitifotech\Laragdads\Ads\Support\Canvas;
use Packitifotech\Laragdads\Ads\Support\Color;
use Packitifotech\Laragdads\Ads\Support\Typography;

/**
 * Drawing primitives for the template backgrounds: watercolour washes, waves, ribbons, brush splashes, patterns,
 * leaves, and small illustrated props (atom, bulb, books, plant, prism, magnet, Newton's cradle, graphs, notebook...).
 * Every function draws onto a Canvas and uses pixel coordinates; sizes are usually given from the canvas height
 * so the same recipe works for any banner width.
 */
final class Doodles
{
    private const SS = 2;   // supersampling for line art

    // ----------------------------------------------------------- base shapes

    /** Vertical or diagonal two-colour wash over the whole canvas. */
    public static function wash(Canvas $c, array $from, array $to, string $dir = 'diagonal'): void
    {
        $c->gradient($from, $to, $dir);
    }

    /** Watercolour blob: a few nested soft blobs of one colour. */
    public static function blob(Canvas $c, int $cx, int $cy, int $rx, int $ry, array $color, float $alpha = 0.6, int $seed = 1): void
    {
        $pad = (int) (max($rx, $ry) * 0.5);
        $c->soft($cx - $rx - $pad, $cy - $ry - $pad, 2 * ($rx + $pad), 2 * ($ry + $pad), function (GdImage $l, int $k, callable $col) use ($rx, $ry, $pad, $color, $alpha, $seed) {
            foreach ([[1.0, 1.0], [0.8, 0.9], [0.55, 0.8]] as $n => [$f, $a]) {
                $pts = Canvas::blobPoints(($rx + $pad) / $k, ($ry + $pad) / $k, $rx * $f / $k, $ry * $f / $k, $seed + $n, 0.1, 1);
                imagefilledpolygon($l, $pts, $col($color, min(1.0, $alpha * $a)));
            }
        }, 4, 2, $color);
    }

    /** Solid sphere with a soft highlight. */
    public static function sphere(Canvas $c, int $cx, int $cy, int $d, array $color): void
    {
        $c->circle($cx, $cy, $d, Color::darken($color, 0.12));
        $c->circle($cx, $cy, (int) ($d * 0.92), $color);
        $c->glow($cx - (int) ($d * 0.18), $cy - (int) ($d * 0.2), (int) ($d * 0.38), [255, 255, 255], 0.55);
    }

    /** A translucent wave band across [$x0, $x1]. $up draws the filled area above the curve instead of below it. */
    public static function wave(Canvas $c, int $x0, int $x1, float $base, float $amp, float $periods, float $phase, array $color, float $alpha = 0.5, bool $up = false, float $amp2 = 0.0, bool $taper = true): void
    {
        $h = $c->height();
        $c->draw($x0, 0, $x1 - $x0, $h, function (GdImage $l, int $k, callable $col) use ($c, $x0, $x1, $base, $amp, $periods, $phase, $color, $alpha, $up, $amp2, $h, $taper) {
            $w = $x1 - $x0;
            $pts = [];
            for ($x = 0; $x <= $w; $x += max(3, intdiv($w, 160))) {
                $t = $x / max(1, $w);
                $y = $base + $amp * sin($t * $periods * M_PI * 2 + $phase) + $amp2 * sin($t * $periods * M_PI * 5 + $phase * 2);
                if ($taper && $x1 - $x0 < $c->width() * 0.999) {
                    // Slide the shape off the canvas at both ends instead of cutting it with a vertical edge.
                    $e = min(1.0, $t / 0.22, (1 - $t) / 0.22);
                    $e = $e * $e * (3 - 2 * $e);
                    $y = $up ? $y * $e - $h * 0.3 * (1 - $e) : $y * $e + $h * 1.15 * (1 - $e);
                }
                array_push($pts, (int) ($x * $k), (int) ($y * $k));
            }
            array_push($pts, (int) ($w * $k), $up ? 0 : (int) ($h * $k), 0, $up ? 0 : (int) ($h * $k));
            imagefilledpolygon($l, $pts, $col($color, $alpha));
        }, 1);
    }

    /** A curved ribbon of constant width along a sine path (the yellow sweep). */
    public static function ribbon(Canvas $c, int $x0, int $x1, float $base, float $amp, float $periods, float $phase, float $width, array $color, float $alpha = 1.0, float $slope = 0.0): void
    {
        $h = $c->height();
        $c->draw($x0, 0, $x1 - $x0, $h, function (GdImage $l, int $k, callable $col) use ($x0, $x1, $base, $amp, $periods, $phase, $width, $color, $alpha, $slope, $h) {
            $w = $x1 - $x0;
            $top = [];
            $bot = [];
            for ($x = 0; $x <= $w; $x += max(3, intdiv($w, 160))) {
                $t = $x / max(1, $w);
                $y = $base + $slope * $t + $amp * sin($t * $periods * M_PI * 2 + $phase);
                $top[] = [$x * $k, ($y - $width / 2) * $k];
                $bot[] = [$x * $k, ($y + $width / 2) * $k];
            }
            $flat = [];
            foreach (array_merge($top, array_reverse($bot)) as [$px, $py]) {
                array_push($flat, (int) $px, (int) $py);
            }
            imagefilledpolygon($l, $flat, $col($color, $alpha));
        }, 1);
    }

    /** Dry-brush swipes: slanted strokes of random length/width clustered at an edge. */
    public static function swipes(Canvas $c, int $x, int $y, int $w, int $h, array $colors, int $count, int $seed, float $angle = -35.0, float $maxLen = 1.0): void
    {
        $rand = Canvas::seeded($seed);
        for ($i = 0; $i < $count; $i++) {
            $len = (int) ($w * (0.35 + 0.65 * $rand()) * $maxLen);
            $th = (int) max(3, $h * (0.05 + 0.13 * $rand()));
            $px = $x + (int) (($w - $len) * $rand());
            $py = $y + (int) (($h - $th) * $rand());
            $c->brush($px, $py, $len, $th, $colors[$i % count($colors)], $angle + ($rand() - 0.5) * 14, 0.78 + 0.2 * $rand(), $seed + $i);
        }
    }

    // --------------------------------------------------------------- patterns

    public static function dots(Canvas $c, int $x, int $y, int $cols, int $rows, int $gap, array $color, float $alpha = 0.55): void
    {
        $c->draw($x, $y, $cols * $gap, $rows * $gap, function (GdImage $l, int $k, callable $col) use ($cols, $rows, $gap, $color, $alpha) {
            $cc = $col($color, $alpha);
            $d = max(2, (int) ($gap * 0.22 * $k));
            for ($r = 0; $r < $rows; $r++) {
                for ($q = 0; $q < $cols; $q++) {
                    imagefilledellipse($l, (int) (($q + 0.5) * $gap * $k), (int) (($r + 0.5) * $gap * $k), $d, $d, $cc);
                }
            }
        });
    }

    /** Hexagon outlines (with a dot on some corners) over a region: the "molecule" pattern. */
    public static function hexes(Canvas $c, int $x, int $y, int $w, int $h, int $r, array $color, float $alpha = 0.3, array $fill = [], int $seed = 5): void
    {
        $rand = Canvas::seeded($seed);
        $c->draw($x, $y, $w, $h, function (GdImage $l, int $k, callable $col) use ($w, $h, $r, $color, $alpha, $fill, $rand) {
            imagesetthickness($l, max(1, $k));
            $line = $col($color, $alpha);
            $dx = $r * sqrt(3);
            $dy = $r * 1.5;
            for ($row = -1; $row * $dy < $h + $r; $row++) {
                for ($cc = -1; $cc * $dx < $w + $dx; $cc++) {
                    $cx = ($cc + ($row % 2 ? 0.5 : 0)) * $dx;
                    $cy = $row * $dy;
                    $pts = [];
                    for ($v = 0; $v < 6; $v++) {
                        $a = M_PI / 6 + $v * M_PI / 3;
                        $pts[] = (int) (($cx + $r * cos($a)) * $k);
                        $pts[] = (int) (($cy + $r * sin($a)) * $k);
                    }
                    if ($fill !== [] && $rand() < 0.16) {
                        imagefilledpolygon($l, $pts, $col($fill, 0.28));
                    }
                    imagepolygon($l, $pts, $line);
                    if ($rand() < 0.35) {
                        imagefilledellipse($l, $pts[0], $pts[1], 3 * $k, 3 * $k, $col($color, min(1.0, $alpha * 2.2)));
                    }
                }
            }
        });
    }

    /** Graph paper. */
    public static function grid(Canvas $c, int $gap, array $color, float $alpha = 0.18): void
    {
        $w = $c->width();
        $h = $c->height();
        $c->draw(0, 0, $w, $h, function (GdImage $l, int $k, callable $col) use ($w, $h, $gap, $color, $alpha) {
            $line = $col($color, $alpha);
            for ($x = 0; $x <= $w; $x += $gap) {
                imageline($l, $x * $k, 0, $x * $k, $h * $k, $line);
            }
            for ($y = 0; $y <= $h; $y += $gap) {
                imageline($l, 0, $y * $k, $w * $k, $y * $k, $line);
            }
        }, 1);
    }

    /** Overlapping translucent diamonds (the green "facets" look). */
    public static function facets(Canvas $c, int $x, int $y, int $w, int $h, int $size, array $color, float $alpha, int $seed): void
    {
        $rand = Canvas::seeded($seed);
        $c->draw($x, $y, $w, $h, function (GdImage $l, int $k, callable $col) use ($w, $h, $size, $color, $alpha, $rand) {
            for ($row = -1; $row * $size < $h + $size; $row++) {
                for ($cc = -1; $cc * $size < $w + $size; $cc++) {
                    if ($rand() < 0.5) {
                        continue;
                    }
                    $cx = ($cc + ($row % 2 ? 0.5 : 0)) * $size;
                    $cy = $row * $size * 0.6;
                    imagefilledpolygon($l, [(int) ($cx * $k), (int) (($cy - $size / 2) * $k), (int) (($cx + $size / 2) * $k), (int) ($cy * $k), (int) ($cx * $k), (int) (($cy + $size / 2) * $k), (int) (($cx - $size / 2) * $k), (int) ($cy * $k)], $col($color, $alpha * (0.4 + $rand() * 0.6)));
                }
            }
        });
    }

    public static function sparkle(Canvas $c, int $cx, int $cy, int $size, array $color, float $alpha = 0.9): void
    {
        $c->draw($cx - $size, $cy - $size, 2 * $size, 2 * $size, function (GdImage $l, int $k, callable $col) use ($size, $color, $alpha) {
            $s = $size * $k;
            $in = $s * 0.16;
            imagefilledpolygon($l, [$s, 0, (int) ($s + $in), (int) ($s - $in), 2 * $s, $s, (int) ($s + $in), (int) ($s + $in), $s, 2 * $s, (int) ($s - $in), (int) ($s + $in), 0, $s, (int) ($s - $in), (int) ($s - $in)], $col($color, $alpha));
        });
    }

    // ----------------------------------------------------------------- nature

    /**
     * A sprig of leaves along a stem. $outline draws line art, otherwise filled shapes. $angle rotates the whole sprig
     * (degrees, 0 = growing up).
     */
    public static function sprig(Canvas $c, int $x, int $y, int $len, float $angle, array $color, bool $outline = false, int $leaves = 7, float $alpha = 1.0, int $seed = 3): void
    {
        $rand = Canvas::seeded($seed);
        $pad = (int) ($len * 0.45);
        $c->draw($x - $len - $pad, $y - $len - $pad, 2 * ($len + $pad), 2 * ($len + $pad), function (GdImage $l, int $k, callable $col) use ($len, $pad, $angle, $color, $outline, $leaves, $alpha, $rand) {
            $ox = ($len + $pad) * $k;
            $oy = ($len + $pad) * $k;
            $rad = deg2rad($angle);
            $dir = fn (float $a): array => [sin($rad + $a), -cos($rad + $a)];
            [$sx, $sy] = $dir(0);
            $cc = $col($color, $alpha);
            imagesetthickness($l, max(1, (int) ($len * 0.012 * $k)));
            imageline($l, (int) $ox, (int) $oy, (int) ($ox + $sx * $len * $k), (int) ($oy + $sy * $len * $k), $cc);

            for ($i = 0; $i < $leaves; $i++) {
                $t = 0.18 + 0.82 * $i / max(1, $leaves - 1);
                $side = $i % 2 === 0 ? 1 : -1;
                $bx = $ox + $sx * $len * $t * $k;
                $by = $oy + $sy * $len * $t * $k;
                $a = $side * (0.7 + 0.25 * $rand());
                [$lx, $ly] = $dir($a);
                $ll = $len * (0.34 - 0.15 * $t + 0.05 * $rand());
                $lw = $ll * 0.42;
                $pts = [];
                for ($j = 0; $j <= 20; $j++) {
                    $u = $j / 20;
                    $off = sin($u * M_PI) * $lw / 2;
                    $px = -$ly;
                    $py = $lx;   // perpendicular
                    $pts[] = (int) ($bx + ($lx * $ll * $u + $px * $off) * $k);
                    $pts[] = (int) ($by + ($ly * $ll * $u + $py * $off) * $k);
                }
                for ($j = 20; $j >= 0; $j--) {
                    $u = $j / 20;
                    $off = -sin($u * M_PI) * $lw / 2;
                    $pts[] = (int) ($bx + ($lx * $ll * $u + (-$ly) * $off) * $k);
                    $pts[] = (int) ($by + ($ly * $ll * $u + $lx * $off) * $k);
                }
                $outline ? imagepolygon($l, $pts, $cc) : imagefilledpolygon($l, $pts, $cc);
                if ($outline) {
                    imageline($l, (int) $bx, (int) $by, (int) ($bx + $lx * $ll * $k), (int) ($by + $ly * $ll * $k), $cc);
                }
            }
        });
    }

    /** Pot plant: white pot, a fan of green leaves. $h is the overall height; ($x, $y) is the bottom centre. */
    public static function plant(Canvas $c, int $x, int $y, int $h): void
    {
        $potH = (int) ($h * 0.3);
        $potW = (int) ($h * 0.3);
        $greens = [[44, 120, 60], [64, 150, 70], [30, 96, 52], [88, 170, 84]];
        foreach ([[-38, 0.9, 0], [-14, 1.0, 1], [12, 0.95, 2], [34, 0.85, 3], [-60, 0.65, 2], [58, 0.6, 1]] as $i => [$ang, $sc, $gi]) {
            self::sprigLeaf($c, $x, $y - $potH, (int) (($h - $potH) * $sc), $ang, $greens[$gi], $i);
        }
        $c->draw($x - $potW, $y - $potH, 2 * $potW, $potH, function (GdImage $l, int $k, callable $col) use ($potW, $potH) {
            $pts = [(int) ($potW * 0.2 * $k), 0, (int) ($potW * 1.8 * $k), 0, (int) ($potW * 1.55 * $k), $potH * $k, (int) ($potW * 0.45 * $k), $potH * $k];
            imagefilledpolygon($l, $pts, $col([236, 236, 240], 1.0));
            imagefilledpolygon($l, [(int) ($potW * 0.2 * $k), 0, (int) ($potW * 1.8 * $k), 0, (int) ($potW * 1.76 * $k), (int) ($potH * 0.14 * $k), (int) ($potW * 0.24 * $k), (int) ($potH * 0.14 * $k)], $col([210, 212, 222], 1.0));
        });
    }

    /** One large leaf (a pointed oval) with a centre vein. */
    private static function sprigLeaf(Canvas $c, int $x, int $y, int $len, float $angle, array $color, int $seed): void
    {
        $pad = (int) ($len * 0.3);
        $c->draw($x - $len - $pad, $y - $len - $pad, 2 * ($len + $pad), 2 * ($len + $pad), function (GdImage $l, int $k, callable $col) use ($len, $pad, $angle, $color) {
            $ox = ($len + $pad) * $k;
            $oy = ($len + $pad) * $k;
            $rad = deg2rad($angle);
            $dx = sin($rad);
            $dy = -cos($rad);
            $w = $len * 0.26;
            $pts = [];
            for ($j = 0; $j <= 24; $j++) {
                $u = $j / 24;
                $pts[] = (int) ($ox + ($dx * $len * $u + (-$dy) * sin($u * M_PI) * $w * (1 - 0.25 * $u)) * $k);
                $pts[] = (int) ($oy + ($dy * $len * $u + $dx * sin($u * M_PI) * $w * (1 - 0.25 * $u)) * $k);
            }
            for ($j = 24; $j >= 0; $j--) {
                $u = $j / 24;
                $pts[] = (int) ($ox + ($dx * $len * $u - (-$dy) * sin($u * M_PI) * $w * (1 - 0.25 * $u)) * $k);
                $pts[] = (int) ($oy + ($dy * $len * $u - $dx * sin($u * M_PI) * $w * (1 - 0.25 * $u)) * $k);
            }
            imagefilledpolygon($l, $pts, $col($color, 1.0));
            imagesetthickness($l, max(1, $k));
            imageline($l, (int) $ox, (int) $oy, (int) ($ox + $dx * $len * $k), (int) ($oy + $dy * $len * $k), $col(Color::lighten($color, 0.35), 0.8));
        });
    }

    // ------------------------------------------------------------------ props

    /**
     * A stack of books lying flat. ($x, $y) is the bottom-left of the stack; $w the width of the widest book.
     *
     * @param array<int, array{0:int,1:int,2:int}> $colors cover colours, bottom to top
     */
    public static function books(Canvas $c, int $x, int $y, int $w, array $colors, int $seed = 2): void
    {
        $rand = Canvas::seeded($seed);
        $th = (int) ($w * 0.17);
        $cursor = $y;
        foreach ($colors as $i => $col) {
            $bw = (int) ($w * (0.86 + 0.14 * $rand()));
            $off = (int) (($w - $bw) * $rand());
            $bx = $x + $off;
            $by = $cursor - $th;
            $c->draw($bx - 2, $by - 2, $bw + 4, $th + 6, function (GdImage $l, int $k, callable $cc) use ($bw, $th, $col) {
                $s = fn (float $v): int => (int) ($v * $k);
                imagefilledrectangle($l, $s(2), $s(2), $s(2 + $bw), $s(2 + $th), $cc($col, 1.0));
                imagefilledrectangle($l, $s(2 + $bw * 0.04), $s(2 + $th * 0.2), $s(2 + $bw * 0.97), $s(2 + $th * 0.75), $cc([244, 240, 228], 1.0));   // pages
                imagefilledrectangle($l, $s(2), $s(2), $s(2 + $bw * 0.05), $s(2 + $th), $cc(Color::darken($col, 0.28), 1.0));                      // spine
                imagefilledrectangle($l, $s(2), $s(2), $s(2 + $bw), $s(2 + $th * 0.2), $cc(Color::lighten($col, 0.12), 1.0));                       // top board
                imagefilledrectangle($l, $s(2), $s(2 + $th * 0.76), $s(2 + $bw), $s(2 + $th), $cc(Color::darken($col, 0.1), 1.0));                  // bottom board
            });
            $cursor = $by + (int) ($th * 0.08);
        }
    }

    /** Glass bulb. $lit gives the glowing yellow version, otherwise line art in $color. */
    public static function bulb(Canvas $c, int $cx, int $cy, int $size, array $color, bool $lit = true): void
    {
        $c->draw($cx - $size, $cy - $size, 2 * $size, 2 * $size, function (GdImage $l, int $k, callable $col) use ($size, $color, $lit) {
            $s = $size * $k;
            $mx = $s;
            $my = (int) ($s * 0.88);
            $r = (int) ($s * 0.42);
            if ($lit) {
                imagefilledellipse($l, $mx, $my, 2 * $r, 2 * $r, $col([255, 214, 70], 1.0));
                imagefilledellipse($l, $mx, $my, (int) (1.55 * $r), (int) (1.55 * $r), $col([255, 236, 140], 1.0));
                imagefilledellipse($l, (int) ($mx - $r * 0.25), (int) ($my - $r * 0.3), (int) ($r * 0.5), (int) ($r * 0.4), $col([255, 255, 255], 0.55));
                imagefilledrectangle($l, (int) ($mx - $r * 0.42), (int) ($my + $r * 0.78), (int) ($mx + $r * 0.42), (int) ($my + $r * 1.25), $col([90, 96, 110], 1.0));
                imagefilledrectangle($l, (int) ($mx - $r * 0.34), (int) ($my + $r * 1.25), (int) ($mx + $r * 0.34), (int) ($my + $r * 1.42), $col([60, 64, 76], 1.0));
            } else {
                imagesetthickness($l, max(2, (int) ($size * 0.05 * $k)));
                imageellipse($l, $mx, $my, 2 * $r, 2 * $r, $col($color, 1.0));
                imageline($l, (int) ($mx - $r * 0.42), (int) ($my + $r * 0.85), (int) ($mx - $r * 0.42), (int) ($my + $r * 1.3), $col($color, 1.0));
                imageline($l, (int) ($mx + $r * 0.42), (int) ($my + $r * 0.85), (int) ($mx + $r * 0.42), (int) ($my + $r * 1.3), $col($color, 1.0));
                imageline($l, (int) ($mx - $r * 0.42), (int) ($my + $r * 1.3), (int) ($mx + $r * 0.42), (int) ($my + $r * 1.3), $col($color, 1.0));
            }
            imagesetthickness($l, max(2, (int) ($size * 0.04 * $k)));
            $ray = $col($lit ? [250, 190, 40] : $color, 0.9);
            foreach ([-150, -120, -90, -60, -30, 0, 180] as $deg) {
                $a = deg2rad($deg);
                imageline($l, (int) ($mx + cos($a) * $r * 1.22), (int) ($my + sin($a) * $r * 1.22), (int) ($mx + cos($a) * $r * 1.55), (int) ($my + sin($a) * $r * 1.55), $ray);
            }
        });
    }

    /** Atom: three orbits, nucleus, a few electrons. */
    public static function atom(Canvas $c, int $cx, int $cy, int $size, array $color, array $nucleus = [], float $stroke = 0.03): void
    {
        $nucleus = $nucleus ?: Color::darken($color, 0.15);
        $c->draw($cx - $size, $cy - $size, 2 * $size, 2 * $size, function (GdImage $l, int $k, callable $col) use ($size, $color, $nucleus, $stroke) {
            $s = $size * $k;
            imagesetthickness($l, max(2, (int) ($size * $stroke * $k * 2)));
            $line = $col($color, 1.0);
            foreach ([0.0, M_PI / 3, -M_PI / 3] as $rot) {
                $pts = [];
                for ($i = 0; $i < 72; $i++) {
                    $a = $i / 72 * M_PI * 2;
                    $ex = cos($a) * $s * 0.95;
                    $ey = sin($a) * $s * 0.34;
                    $pts[] = (int) ($s + $ex * cos($rot) - $ey * sin($rot));
                    $pts[] = (int) ($s + $ex * sin($rot) + $ey * cos($rot));
                }
                imagepolygon($l, $pts, $line);
            }
            imagefilledellipse($l, $s, $s, (int) ($s * 0.34), (int) ($s * 0.34), $col($nucleus, 1.0));
            imagefilledellipse($l, (int) ($s - $s * 0.05), (int) ($s - $s * 0.06), (int) ($s * 0.12), (int) ($s * 0.1), $col([255, 255, 255], 0.5));
            foreach ([[0.86, 0.0], [-0.45, 0.78], [-0.4, -0.82]] as [$ex, $ey]) {
                imagefilledellipse($l, (int) ($s + $ex * $s * 0.95), (int) ($s + $ey * $s * 0.4), (int) ($s * 0.1), (int) ($s * 0.1), $col($color, 1.0));
            }
        });
    }

    /** Glass prism splitting a white beam into a rainbow fan. ($x, $y) is the prism's centre. */
    public static function prism(Canvas $c, int $x, int $y, int $size, int $fanLength): void
    {
        $rainbow = [[236, 64, 64], [255, 150, 50], [255, 220, 70], [90, 200, 110], [60, 150, 230], [100, 90, 220], [160, 90, 220]];
        $c->draw($x - (int) ($size * 1.2), $y - $size, $fanLength + (int) ($size * 1.2) + $size, 2 * $size, function (GdImage $l, int $k, callable $col) use ($size, $fanLength, $rainbow) {
            $ox = (int) ($size * 1.2) * $k;
            $oy = $size * $k;
            $s = $size * $k;
            // white beam in from the left
            imagefilledpolygon($l, [0, (int) ($oy + $s * 0.05), (int) ($ox - $s * 0.18), (int) ($oy - $s * 0.02), (int) ($ox - $s * 0.18), (int) ($oy + $s * 0.18), 0, (int) ($oy + $s * 0.12)], $col([255, 255, 255], 0.95));
            // rainbow fan out of the right face
            $n = count($rainbow);
            foreach ($rainbow as $i => $rc) {
                $a0 = -0.05 + $i * 0.052;
                $a1 = $a0 + 0.056;
                imagefilledpolygon($l, [(int) ($ox + $s * 0.2), (int) ($oy + $s * 0.05 + $i * $s * 0.02), (int) ($ox + $fanLength * $k), (int) ($oy + $s * 0.1 + $a0 * $fanLength * $k * 0.5), (int) ($ox + $fanLength * $k), (int) ($oy + $s * 0.1 + $a1 * $fanLength * $k * 0.5), (int) ($ox + $s * 0.2), (int) ($oy + $s * 0.05 + ($i + 1) * $s * 0.02)], $col($rc, 0.92));
            }
            // the prism: translucent glass with bright edges
            $tri = [$ox, (int) ($oy - $s * 0.72), (int) ($ox + $s * 0.62), (int) ($oy + $s * 0.52), (int) ($ox - $s * 0.62), (int) ($oy + $s * 0.52)];
            imagefilledpolygon($l, $tri, $col([200, 226, 250], 0.78));
            imagefilledpolygon($l, [$ox, (int) ($oy - $s * 0.72), (int) ($ox + $s * 0.2), (int) ($oy + $s * 0.52), (int) ($ox - $s * 0.62), (int) ($oy + $s * 0.52)], $col([255, 255, 255], 0.35));
            imagesetthickness($l, max(2, (int) ($k * 1.5)));
            imagepolygon($l, $tri, $col([140, 190, 240], 1.0));
        });
    }

    /** Horseshoe magnet with field lines. ($cx, $cy) is the centre of the magnet. */
    public static function magnet(Canvas $c, int $cx, int $cy, int $size, array $fieldColor): void
    {
        $c->draw($cx - (int) ($size * 1.6), $cy - $size, (int) ($size * 3.2), 2 * $size, function (GdImage $l, int $k, callable $col) use ($size, $fieldColor) {
            $ox = (int) ($size * 1.6) * $k;
            $oy = $size * $k;
            $s = $size * $k;
            // field lines: nested loops either side of the poles
            imagesetthickness($l, max(1, (int) ($k * 1.3)));
            $line = $col($fieldColor, 0.9);
            foreach ([0.55, 0.85, 1.15, 1.45] as $f) {
                foreach ([-1, 1] as $side) {
                    $pts = [];
                    for ($i = 0; $i <= 40; $i++) {
                        $t = $i / 40 * M_PI;
                        $pts[] = [(int) ($ox + $side * ($s * 0.25 + sin($t) * $s * $f * 0.95)), (int) ($oy - $s * 0.05 - cos($t) * $s * $f * 0.62)];
                    }
                    for ($i = 0; $i + 1 < count($pts); $i++) {
                        imageline($l, $pts[$i][0], $pts[$i][1], $pts[$i + 1][0], $pts[$i + 1][1], $line);
                    }
                }
            }
            // the magnet: U made of an arc and two legs, red and blue halves
            $outer = $s * 0.5;
            $inner = $s * 0.27;
            $ring = function (float $a0, float $a1, array $color) use ($l, $ox, $oy, $outer, $inner, $col, $s) {
                $pts = [];
                for ($i = 0; $i <= 24; $i++) {
                    $a = $a0 + ($a1 - $a0) * $i / 24;
                    $pts[] = (int) ($ox + cos($a) * $outer);
                    $pts[] = (int) ($oy + $s * 0.05 + sin($a) * $outer);
                }
                for ($i = 24; $i >= 0; $i--) {
                    $a = $a0 + ($a1 - $a0) * $i / 24;
                    $pts[] = (int) ($ox + cos($a) * $inner);
                    $pts[] = (int) ($oy + $s * 0.05 + sin($a) * $inner);
                }
                imagefilledpolygon($l, $pts, $col($color, 1.0));
            };
            $ring(M_PI * 0.5, M_PI, [222, 60, 80]);
            $ring(0.0, M_PI * 0.5, [60, 90, 220]);
            $leg = function (int $sign, array $color) use ($l, $ox, $oy, $outer, $inner, $col, $s) {
                $x0 = $ox + $sign * (int) $inner;
                $x1 = $ox + $sign * (int) $outer;
                imagefilledrectangle($l, min($x0, $x1), (int) ($oy + $s * 0.05 - $s * 0.55), max($x0, $x1), (int) ($oy + $s * 0.05), $col($color, 1.0));
                imagefilledrectangle($l, min($x0, $x1), (int) ($oy + $s * 0.05 - $s * 0.55), max($x0, $x1), (int) ($oy + $s * 0.05 - $s * 0.38), $col([232, 232, 240], 1.0));
            };
            $leg(-1, [222, 60, 80]);
            $leg(1, [60, 90, 220]);
        });
    }

    /** Newton's cradle: frame, five steel balls, the left one swung out. ($x, $y) is the bottom-left of the base. */
    public static function cradle(Canvas $c, int $x, int $y, int $w): void
    {
        $h = (int) ($w * 0.62);
        $c->draw($x - 4, $y - $h - 4, $w + 8, $h + 8, function (GdImage $l, int $k, callable $col) use ($w, $h) {
            $s = fn (float $v): int => (int) ($v * $k);
            $barY = 6;
            $base = $h;
            // base and frame
            imagefilledrectangle($l, $s(4), $s($base - $h * 0.07), $s($w + 4), $s($base), $col([226, 228, 236], 1.0));
            imagefilledrectangle($l, $s(4), $s($base - $h * 0.07), $s($w + 4), $s($base - $h * 0.05), $col([255, 255, 255], 1.0));
            imagesetthickness($l, max(2, (int) ($w * 0.018 * $k)));
            $steel = $col([84, 90, 106], 1.0);
            imageline($l, $s(4 + $w * 0.06), $s($barY), $s(4 + $w * 0.94), $s($barY), $steel);
            imageline($l, $s(4 + $w * 0.06), $s($barY), $s(4 + $w * 0.06), $s($base - $h * 0.06), $steel);
            imageline($l, $s(4 + $w * 0.94), $s($barY), $s(4 + $w * 0.94), $s($base - $h * 0.06), $steel);
            // balls
            $r = $w * 0.062;
            $len = $h * 0.66;
            imagesetthickness($l, max(1, (int) ($k * 1.2)));
            for ($i = 0; $i < 5; $i++) {
                $px = 4 + $w * (0.31 + 0.095 * $i);
                $ang = $i === 0 ? -0.62 : 0.0;
                $bx = $px + sin($ang) * $len;
                $by = $barY + cos($ang) * $len;
                imageline($l, $s($px), $s($barY), $s($bx), $s($by), $col([120, 126, 140], 0.9));
                imagefilledellipse($l, $s($bx), $s($by), $s(2 * $r), $s(2 * $r), $col([96, 102, 118], 1.0));
                imagefilledellipse($l, $s($bx - $r * 0.25), $s($by - $r * 0.3), $s($r * 0.9), $s($r * 0.7), $col([220, 226, 240], 0.85));
            }
        });
    }

    /**
     * Axes with a curve: 'sine', 'decay' or 'parabola'. ($x, $y) is the top-left of the graph, $w/$h its size.
     */
    public static function graph(Canvas $c, int $x, int $y, int $w, int $h, string $kind, array $color, bool $labels = true, ?Typography $font = null): void
    {
        $c->draw($x - 6, $y - 6, $w + 12, $h + 12, function (GdImage $l, int $k, callable $col) use ($w, $h, $kind, $color) {
            $s = fn (float $v): int => (int) (($v + 6) * $k);
            imagesetthickness($l, max(2, (int) ($k * 1.6)));
            $ink = $col($color, 1.0);
            // axes with arrow heads
            $ox = $w * 0.12;
            $oy = $kind === 'sine' ? $h * 0.5 : $h * 0.92;
            imageline($l, $s($ox), $s(0), $s($ox), $s($h), $ink);
            imageline($l, $s($ox), $s($oy), $s($w), $s($oy), $ink);
            imageline($l, $s($ox), $s(0), $s($ox - 4), $s(8), $ink);
            imageline($l, $s($ox), $s(0), $s($ox + 4), $s(8), $ink);
            imageline($l, $s($w), $s($oy), $s($w - 8), $s($oy - 4), $ink);
            imageline($l, $s($w), $s($oy), $s($w - 8), $s($oy + 4), $ink);
            // curve
            $pts = [];
            for ($i = 0; $i <= 90; $i++) {
                $t = $i / 90;
                $px = $ox + 6 + $t * ($w - $ox - 14);
                $py = match ($kind) {
                    'sine' => $oy - sin($t * M_PI * 4) * $h * 0.4,
                    'decay' => $h * 0.08 + (1 - exp(-$t * 3.6)) * ($h * 0.82),
                    default => $oy - sin($t * M_PI) * $h * 0.78,
                };
                $pts[] = [$px, $py];
            }
            for ($i = 0; $i + 1 < count($pts); $i++) {
                imageline($l, $s($pts[$i][0]), $s($pts[$i][1]), $s($pts[$i + 1][0]), $s($pts[$i + 1][1]), $ink);
            }
            // dashed guides
            if ($kind === 'decay') {
                for ($d = 0; $d < 10; $d++) {
                    imageline($l, $s($ox + $d * 14), $s($h * 0.3), $s($ox + $d * 14 + 7), $s($h * 0.3), $ink);
                }
            }
        });
        if ($labels && $font !== null) {
            $size = max(9, (int) ($h * 0.14));
            $c->text($kind === 'sine' ? 'y' : 'v', $font, $size, $x + $w * 0.03, $y + $size, $color);
            $c->text('t', $font, $size, $x + $w - $size, $y + ($kind === 'sine' ? $h * 0.5 : $h * 0.92) + $size, $color);
        }
    }

    /** Spiral notebook, slightly rotated, with a pen. ($x, $y) is the top-left of the notebook. */
    public static function notebook(Canvas $c, int $x, int $y, int $w, int $h, float $angle, ?Typography $script = null, string $text = '', array $ink = [40, 40, 110]): void
    {
        $pad = (int) (max($w, $h) * 0.3);
        $c->draw($x - $pad, $y - $pad, $w + 2 * $pad, $h + 2 * $pad, function (GdImage $l, int $k, callable $col) use ($w, $h, $pad, $angle, $ink) {
            $rot = deg2rad($angle);
            $cx = ($pad + $w / 2) * $k;
            $cy = ($pad + $h / 2) * $k;
            $tx = function (float $px, float $py) use ($rot, $cx, $cy, $w, $h, $k): array {
                $dx = ($px - $w / 2) * $k;
                $dy = ($py - $h / 2) * $k;

                return [(int) ($cx + $dx * cos($rot) - $dy * sin($rot)), (int) ($cy + $dx * sin($rot) + $dy * cos($rot))];
            };
            $poly = function (array $corners, array $color, float $a = 1.0) use ($l, $tx, $col) {
                $flat = [];
                foreach ($corners as [$px, $py]) {
                    array_push($flat, ...$tx($px, $py));
                }
                imagefilledpolygon($l, $flat, $col($color, $a));
            };
            $poly([[4, 6], [$w + 4, 6], [$w + 4, $h + 6], [4, $h + 6]], [0, 0, 0], 0.12);               // shadow
            $poly([[0, 0], [$w, 0], [$w, $h], [0, $h]], [252, 250, 244]);
            imagesetthickness($l, max(1, (int) ($k * 1.1)));
            for ($i = 1; $i < 9; $i++) {   // ruled lines
                [$a, $b] = $tx($w * 0.06, $h * (0.18 + 0.1 * $i));
                [$c2, $d] = $tx($w * 0.94, $h * (0.18 + 0.1 * $i));
                imageline($l, $a, $b, $c2, $d, $col([190, 200, 224], 0.7));
            }
            for ($i = 0; $i < 7; $i++) {   // spiral rings along the top
                [$rx, $ry] = $tx($w * (0.12 + 0.12 * $i), 0);
                imagefilledellipse($l, $rx, $ry, (int) ($w * 0.05 * $k), (int) ($w * 0.05 * $k), $col([70, 74, 90], 1.0));
            }
            // pen
            $poly([[$w * 0.88, $h * 1.0], [$w * 0.915, $h * 1.0], [$w * 1.12, -$h * 0.08], [$w * 1.085, -$h * 0.09]], [58, 56, 92]);
        });
        if ($script !== null && $text !== '') {
            $size = max(10, (int) ($h * 0.18));
            $c->text($text, $script, $size, $x + $w * 0.1, $y + $h * 0.62, $ink, -$angle);
        }
    }

    public static function pencilCup(Canvas $c, int $x, int $y, int $w): void
    {
        $h = (int) ($w * 1.1);
        $colors = [[236, 80, 70], [250, 190, 50], [70, 170, 100], [70, 120, 220], [150, 90, 200]];
        $c->draw($x - 4, $y - $h - 4, $w + 8, $h + 8, function (GdImage $l, int $k, callable $col) use ($w, $h, $colors) {
            $s = fn (float $v): int => (int) (($v + 4) * $k);
            foreach ($colors as $i => $pc) {
                $px = $w * (0.2 + 0.15 * $i);
                $top = $h * (0.0 + 0.05 * ($i % 3));
                imagefilledrectangle($l, $s($px - $w * 0.035), $s($top + $h * 0.12), $s($px + $w * 0.035), $s($h * 0.6), $col($pc, 1.0));
                imagefilledpolygon($l, [$s($px - $w * 0.035), $s($top + $h * 0.12), $s($px + $w * 0.035), $s($top + $h * 0.12), $s($px), $s($top)], $col([240, 210, 170], 1.0));
            }
            imagefilledpolygon($l, [$s($w * 0.08), $s($h * 0.42), $s($w * 0.92), $s($h * 0.42), $s($w * 0.82), $s($h), $s($w * 0.18), $s($h)], $col([54, 60, 78], 1.0));
            imagefilledrectangle($l, $s($w * 0.08), $s($h * 0.42), $s($w * 0.92), $s($h * 0.5), $col([84, 90, 110], 1.0));
        });
    }

    public static function planet(Canvas $c, int $cx, int $cy, int $d, array $body = [244, 140, 70]): void
    {
        $c->draw($cx - $d, $cy - $d, 2 * $d, 2 * $d, function (GdImage $l, int $k, callable $col) use ($d, $body) {
            $s = $d * $k;
            imagefilledellipse($l, $s, $s, (int) ($s * 0.9), (int) ($s * 0.9), $col($body, 1.0));
            imagefilledellipse($l, (int) ($s * 0.88), (int) ($s * 0.86), (int) ($s * 0.5), (int) ($s * 0.46), $col(Color::lighten($body, 0.25), 0.6));
            imagesetthickness($l, max(2, (int) ($k * 2)));
            $pts = [];
            for ($i = 0; $i < 60; $i++) {
                $a = $i / 60 * M_PI * 2;
                $pts[] = (int) ($s + cos($a) * $s * 0.98);
                $pts[] = (int) ($s + sin($a) * $s * 0.3);
            }
            imagepolygon($l, $pts, $col([250, 190, 70], 1.0));
        });
    }
}
