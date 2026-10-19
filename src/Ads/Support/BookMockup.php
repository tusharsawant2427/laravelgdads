<?php

namespace Packitifotech\Laragdads\Ads\Support;

use GdImage;

/**
 * Turns a flat cover image into a believable 3D book: darker spine along the left edge, a page block
 * along the bottom and a soft diagonal gloss. The result has a transparent surround, ready for
 * Canvas::sprite() to tilt and shadow.
 */
final class BookMockup
{
    public static function make(GdImage|string $cover): GdImage
    {
        $src = ImageLoader::load($cover);
        $cw = imagesx($src);
        $ch = imagesy($src);
        $spine = max(4, (int) round($cw * 0.045));
        $pages = max(3, (int) round($ch * 0.022));

        $w = $cw + $spine;
        $h = $ch + $pages;
        $out = imagecreatetruecolor($w, $h);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
        imagealphablending($out, true);

        // Page block under the cover, slightly inset: cream with fine lines.
        for ($y = 0; $y < $pages; $y++) {
            $shade = 238 - (int) (18 * $y / $pages) - ($y % 2 ? 6 : 0);
            imagefilledrectangle($out, $spine + 2, $ch + $y, $w - 3, $ch + $y, imagecolorallocate($out, $shade, $shade - 3, $shade - 12));
        }

        // Cover, shifted right to make room for the spine.
        imagecopy($out, $src, $spine, 0, 0, 0, $cw, $ch);

        // Spine: the cover's own average colour, darkened, with a lighter hinge line.
        $avg = self::average($src);
        for ($x = 0; $x < $spine; $x++) {
            $t = $x / max(1, $spine - 1);
            $c = Color::mix(Color::darken($avg, 0.55), Color::darken($avg, 0.25), $t);
            imagefilledrectangle($out, $x, 0, $x, $ch + $pages - 1, imagecolorallocate($out, ...$c));
        }
        imagefilledrectangle($out, $spine, 0, $spine + 1, $ch - 1, imagecolorallocatealpha($out, 255, 255, 255, 100));

        // Gloss: faint white sweep across the upper-left of the cover.
        for ($y = 0; $y < $ch; $y++) {
            $t = max(0.0, 1 - ($y / $ch) * 1.6);
            if ($t <= 0) {
                break;
            }
            imagefilledrectangle($out, $spine, $y, $w - 1, $y, imagecolorallocatealpha($out, 255, 255, 255, (int) (127 - 127 * 0.12 * $t)));
        }

        return $out;
    }

    private static function average(GdImage $img): array
    {
        $r = $g = $b = $n = 0;
        $w = imagesx($img);
        $h = imagesy($img);
        for ($y = 0; $y < $h; $y += max(1, intdiv($h, 24))) {
            for ($x = 0; $x < $w; $x += max(1, intdiv($w, 24))) {
                $c = imagecolorat($img, $x, $y);
                $r += ($c >> 16) & 255;
                $g += ($c >> 8) & 255;
                $b += $c & 255;
                $n++;
            }
        }

        return [(int) ($r / $n), (int) ($g / $n), (int) ($b / $n)];
    }
}
