<?php

namespace Packitifotech\Laragdads\Ads\Support;

use GdImage;

/**
 * Removes a flat light (white) background from product packshots such as book covers.
 *
 * 1. Flood-fills near-white pixels connected to the image border and makes them transparent.
 * 2. Grey pixels next to that region (the baked-in drop shadow) become translucent black, so the
 *    shadow survives as a real soft shadow on any background instead of a grey halo.
 * 3. Crops to the remaining content.
 *
 * White areas *inside* the product (e.g. a white footer on a cover) are not connected to the border
 * through near-white pixels, so they are kept.
 */
final class Cutout
{
    private const MAX_SIDE = 1400;

    /** Does the image have an opaque, near-white border (i.e. a studio packshot background)? */
    public static function hasLightBackground(GdImage $image, int $threshold = 238): bool
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $hits = 0;
        $total = 0;

        $sample = function (int $x, int $y) use ($image, &$hits, &$total, $threshold) {
            $c = imagecolorat($image, $x, $y);
            $total++;
            if ((($c >> 24) & 0x7F) < 8 && min(($c >> 16) & 0xFF, ($c >> 8) & 0xFF, $c & 0xFF) >= $threshold) {
                $hits++;
            }
        };

        $step = max(1, intdiv(max($w, $h), 200));
        for ($x = 0; $x < $w; $x += $step) {
            $sample($x, 0);
            $sample($x, $h - 1);
        }
        for ($y = 0; $y < $h; $y += $step) {
            $sample(0, $y);
            $sample($w - 1, $y);
        }

        return $total > 0 && $hits / $total >= 0.9;
    }

    /** @var \WeakMap<GdImage, GdImage>|null */
    private static ?\WeakMap $done = null;

    /** Cut-out of $source, computed once per source image (several ad sizes reuse it). */
    public static function apply(GdImage $source, int $tolerance = 10): GdImage
    {
        self::$done ??= new \WeakMap();

        return self::$done[$source] ??= self::compute($source, $tolerance);
    }

    private static function compute(GdImage $source, int $tolerance): GdImage
    {
        $img = self::workingCopy($source);
        $w = imagesx($img);
        $h = imagesy($img);
        $white = 255 - max(0, $tolerance);

        // Pass 1: border-connected near-white -> transparent.
        $state = new \SplFixedArray($w * $h); // 0 untouched, 1 background, 2 shadow
        $queue = new \SplQueue();
        $isWhite = static fn (int $c): bool => min(($c >> 16) & 0xFF, ($c >> 8) & 0xFF, $c & 0xFF) >= $white
            && ((($c >> 24) & 0x7F) < 64);

        $seed = function (int $x, int $y) use (&$state, $queue, $img, $w, $isWhite) {
            $i = $y * $w + $x;
            if (!$state[$i] && $isWhite(imagecolorat($img, $x, $y))) {
                $state[$i] = 1;
                $queue->enqueue($i);
            }
        };
        for ($x = 0; $x < $w; $x++) {
            $seed($x, 0);
            $seed($x, $h - 1);
        }
        for ($y = 0; $y < $h; $y++) {
            $seed(0, $y);
            $seed($w - 1, $y);
        }
        self::flood($state, $queue, $img, $w, $h, static fn ($c) => $isWhite($c), 1);

        // Pass 2: low-chroma greys touching the background -> translucent black shadow.
        // Strictly darker than the background white: never leak into white parts of the product itself.
        $isShadow = static function (int $c) use ($white): bool {
            $r = ($c >> 16) & 0xFF;
            $g = ($c >> 8) & 0xFF;
            $b = $c & 0xFF;

            return max($r, $g, $b) - min($r, $g, $b) <= 14 && min($r, $g, $b) >= 120 && max($r, $g, $b) < $white;
        };
        for ($i = 0, $n = $w * $h; $i < $n; $i++) {
            if ($state[$i] === 1) {
                $queue->enqueue($i);
            }
        }
        self::flood($state, $queue, $img, $w, $h, $isShadow, 2);

        imagealphablending($img, false);
        $clear = imagecolorallocatealpha($img, 0, 0, 0, 127);
        [$minX, $minY, $maxX, $maxY] = [$w, $h, -1, -1];
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $s = $state[$y * $w + $x];
                if ($s === 1) {
                    imagesetpixel($img, $x, $y, $clear);
                    continue;
                }
                if ($s === 2) {
                    $c = imagecolorat($img, $x, $y);
                    $grey = min(($c >> 16) & 0xFF, ($c >> 8) & 0xFF, $c & 0xFF);
                    $opacity = max(0.0, min(0.85, (255 - $grey) / 255 * 1.25));
                    $alpha = (int) round(127 * (1 - $opacity));
                    imagesetpixel($img, $x, $y, imagecolorallocatealpha($img, 0, 0, 0, $alpha));
                    if ($alpha > 120) {
                        continue; // too faint to count as content
                    }
                }
                $minX = min($minX, $x);
                $maxX = max($maxX, $x);
                $minY = min($minY, $y);
                $maxY = max($maxY, $y);
            }
        }
        imagesavealpha($img, true);

        if ($maxX < $minX) {
            return $img; // nothing left: keep the (fully transparent) image rather than fail
        }
        $cropped = imagecrop($img, ['x' => $minX, 'y' => $minY, 'width' => $maxX - $minX + 1, 'height' => $maxY - $minY + 1]);
        if ($cropped === false) {
            return $img;
        }
        imagealphablending($cropped, false);
        imagesavealpha($cropped, true);

        return $cropped;
    }

    private static function flood(\SplFixedArray $state, \SplQueue $queue, GdImage $img, int $w, int $h, callable $accept, int $mark): void
    {
        while (!$queue->isEmpty()) {
            $i = $queue->dequeue();
            $x = $i % $w;
            $y = intdiv($i, $w);
            foreach ([[$x - 1, $y], [$x + 1, $y], [$x, $y - 1], [$x, $y + 1]] as [$nx, $ny]) {
                if ($nx < 0 || $ny < 0 || $nx >= $w || $ny >= $h) {
                    continue;
                }
                $j = $ny * $w + $nx;
                if (!$state[$j] && $accept(imagecolorat($img, $nx, $ny))) {
                    $state[$j] = $mark;
                    $queue->enqueue($j);
                }
            }
        }
    }

    private static function workingCopy(GdImage $source): GdImage
    {
        $w = imagesx($source);
        $h = imagesy($source);
        $scale = min(1.0, self::MAX_SIDE / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $copy = imagecreatetruecolor($nw, $nh);
        imagealphablending($copy, false);
        imagesavealpha($copy, true);
        imagefill($copy, 0, 0, imagecolorallocatealpha($copy, 255, 255, 255, 0));
        imagecopyresampled($copy, $source, 0, 0, 0, 0, $nw, $nh, $w, $h);

        return $copy;
    }
}
