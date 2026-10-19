<?php

namespace Packitifotech\Laragdads\Ads\Support;

use GdImage;

/**
 * Small line-art icons and doodles drawn with GD (no image assets): the kind of pictograms used on feature cards
 * and as hand-drawn decoration. Each icon is drawn in a square of $size pixels centred on ($cx, $cy).
 */
final class Icons
{
    /** @return string[] */
    public static function names(): array
    {
        return ['book', 'doc', 'list', 'bulb', 'atom', 'incline', 'prism', 'flask', 'cap', 'star', 'check', 'target', 'pencil', 'compass'];
    }

    public static function draw(Canvas $canvas, string $name, int $cx, int $cy, int $size, array $color, float $stroke = 0.055, float $opacity = 1.0): void
    {
        if (!in_array($name, self::names(), true)) {
            throw new \InvalidArgumentException("Unknown icon [{$name}]. Available: " . implode(', ', self::names()) . '.');
        }

        $canvas->draw($cx - intdiv($size, 2), $cy - intdiv($size, 2), $size, $size, function (GdImage $l, int $k, callable $c) use ($name, $size, $color, $stroke, $opacity) {
            $s = $size * $k;
            imagesetthickness($l, max(2, (int) round($size * $stroke * $k)));
            $col = $c($color, $opacity);
            $P = fn (float $x, float $y): array => [(int) round($x * $s), (int) round($y * $s)];
            $line = function (float $x1, float $y1, float $x2, float $y2) use ($l, $col, $P) {
                [$a, $b] = $P($x1, $y1);
                [$d, $e] = $P($x2, $y2);
                imageline($l, $a, $b, $d, $e, $col);
            };
            $poly = function (array $pts, bool $closed = true) use ($l, $col, $P) {
                $flat = [];
                foreach ($pts as [$x, $y]) {
                    [$a, $b] = $P($x, $y);
                    array_push($flat, $a, $b);
                }
                if ($closed) {
                    imagepolygon($l, $flat, $col);
                } else {
                    for ($i = 0; $i + 3 < count($flat); $i += 2) {
                        imageline($l, $flat[$i], $flat[$i + 1], $flat[$i + 2], $flat[$i + 3], $col);
                    }
                }
            };
            $ellipse = function (float $x, float $y, float $w, float $h, float $rot = 0.0) use ($l, $col, $s) {
                $pts = [];
                for ($i = 0; $i < 48; $i++) {
                    $a = $i / 48 * M_PI * 2;
                    $ex = cos($a) * $w / 2;
                    $ey = sin($a) * $h / 2;
                    $pts[] = (int) round(($x + $ex * cos($rot) - $ey * sin($rot)) * $s);
                    $pts[] = (int) round(($y + $ex * sin($rot) + $ey * cos($rot)) * $s);
                }
                imagepolygon($l, $pts, $col);
            };

            switch ($name) {
                case 'book':
                    $poly([[0.50, 0.30], [0.30, 0.22], [0.08, 0.26], [0.08, 0.76], [0.30, 0.72], [0.50, 0.80]]);
                    $poly([[0.50, 0.30], [0.70, 0.22], [0.92, 0.26], [0.92, 0.76], [0.70, 0.72], [0.50, 0.80]]);
                    $line(0.50, 0.30, 0.50, 0.80);
                    foreach ([0.38, 0.47, 0.56] as $y) {
                        $line(0.17, $y - 0.03, 0.40, $y);
                        $line(0.60, $y, 0.83, $y - 0.03);
                    }
                    break;
                case 'doc':
                    $poly([[0.24, 0.10], [0.60, 0.10], [0.78, 0.28], [0.78, 0.90], [0.24, 0.90]]);
                    $poly([[0.60, 0.10], [0.60, 0.28], [0.78, 0.28]], false);
                    foreach ([0.42, 0.54, 0.66, 0.78] as $y) {
                        $line(0.34, $y, 0.68, $y);
                    }
                    break;
                case 'list':
                    $poly([[0.14, 0.12], [0.86, 0.12], [0.86, 0.88], [0.14, 0.88]]);
                    foreach ([0.30, 0.50, 0.70] as $y) {
                        $poly([[0.24, $y - 0.05], [0.34, $y - 0.05], [0.34, $y + 0.05], [0.24, $y + 0.05]]);
                        $line(0.44, $y, 0.76, $y);
                    }
                    $line(0.255, 0.30, 0.285, 0.33);
                    $line(0.285, 0.33, 0.33, 0.265);
                    break;
                case 'bulb':
                    $ellipse(0.5, 0.36, 0.46, 0.46);
                    $poly([[0.38, 0.58], [0.40, 0.70], [0.60, 0.70], [0.62, 0.58]], false);
                    foreach ([0.77, 0.85] as $y) {
                        $line(0.40, $y, 0.60, $y);
                    }
                    $poly([[0.43, 0.46], [0.47, 0.30], [0.50, 0.40], [0.53, 0.30], [0.57, 0.46]], false);
                    foreach ([[0.5, 0.04, 0.5, -0.04], [0.12, 0.16, 0.05, 0.1], [0.88, 0.16, 0.95, 0.1]] as [$x1, $y1, $x2, $y2]) {
                        $line($x1, max(0.0, $y1), $x2, max(0.0, $y2));
                    }
                    break;
                case 'atom':
                    foreach ([0.0, M_PI / 3, -M_PI / 3] as $rot) {
                        $ellipse(0.5, 0.5, 0.92, 0.34, $rot);
                    }
                    imagefilledellipse($l, intdiv($s, 2), intdiv($s, 2), (int) ($s * 0.14), (int) ($s * 0.14), $col);
                    break;
                case 'incline':
                    $poly([[0.08, 0.84], [0.92, 0.84], [0.92, 0.30]]);
                    $poly([[0.46, 0.55], [0.58, 0.36], [0.70, 0.43], [0.58, 0.62]]);
                    $line(0.12, 0.84, 0.12, 0.84);
                    imagearc($l, (int) (0.08 * $s), (int) (0.84 * $s), (int) (0.38 * $s), (int) (0.38 * $s), -26, 0, $col);
                    break;
                case 'prism':
                    $poly([[0.50, 0.12], [0.18, 0.80], [0.82, 0.80]]);
                    $line(0.02, 0.62, 0.34, 0.52);
                    foreach ([[0.70, 0.52, 0.98, 0.42], [0.70, 0.56, 0.98, 0.52], [0.70, 0.60, 0.98, 0.62]] as [$x1, $y1, $x2, $y2]) {
                        $line($x1, $y1, $x2, $y2);
                    }
                    break;
                case 'flask':
                    $poly([[0.40, 0.10], [0.60, 0.10], [0.60, 0.40], [0.88, 0.86], [0.12, 0.86], [0.40, 0.40]]);
                    $line(0.34, 0.10, 0.66, 0.10);
                    $line(0.25, 0.68, 0.75, 0.68);
                    break;
                case 'cap':
                    $poly([[0.50, 0.22], [0.96, 0.44], [0.50, 0.66], [0.04, 0.44]]);
                    $poly([[0.24, 0.54], [0.24, 0.74], [0.50, 0.86], [0.76, 0.74], [0.76, 0.54]], false);
                    $line(0.90, 0.48, 0.90, 0.80);
                    break;
                case 'star':
                    $pts = [];
                    for ($i = 0; $i < 10; $i++) {
                        $r = $i % 2 === 0 ? 0.46 : 0.19;
                        $a = -M_PI / 2 + $i * M_PI / 5;
                        $pts[] = [0.5 + cos($a) * $r, 0.52 + sin($a) * $r];
                    }
                    $poly($pts);
                    break;
                case 'check':
                    $ellipse(0.5, 0.5, 0.84, 0.84);
                    $poly([[0.30, 0.52], [0.45, 0.66], [0.72, 0.36]], false);
                    break;
                case 'target':
                    foreach ([0.86, 0.56, 0.26] as $d) {
                        $ellipse(0.5, 0.5, $d, $d);
                    }
                    $line(0.5, 0.5, 0.92, 0.08);
                    break;
                case 'pencil':
                    $poly([[0.18, 0.82], [0.26, 0.58], [0.74, 0.10], [0.90, 0.26], [0.42, 0.74]]);
                    $line(0.26, 0.58, 0.42, 0.74);
                    $line(0.62, 0.22, 0.78, 0.38);
                    break;
                case 'compass':
                    $line(0.5, 0.14, 0.22, 0.88);
                    $line(0.5, 0.14, 0.78, 0.88);
                    $line(0.34, 0.56, 0.66, 0.56);
                    $ellipse(0.5, 0.12, 0.12, 0.12);
                    break;
            }
        });
    }
}
