<?php

namespace Packitifotech\Laragdads\Ads\Support;

use GdImage;

/**
 * Thin drawing toolkit over a GD truecolor image: gradients, soft glows, shadows,
 * anti-aliased rounded shapes, cover/contain image placement and wrapped text.
 *
 * Colours are [r, g, b] arrays; opacity is 0 (invisible) to 1 (solid).
 */
final class Canvas
{
    private function __construct(private GdImage $image)
    {
        imagealphablending($this->image, true);
        imagesavealpha($this->image, true);
    }

    public static function create(int $width, int $height, array $background = [255, 255, 255]): self
    {
        $canvas = new self(imagecreatetruecolor($width, $height));
        $canvas->rect(0, 0, $width, $height, $background);

        return $canvas;
    }

    public static function wrap(GdImage $image): self
    {
        return new self($image);
    }

    public function image(): GdImage
    {
        return $this->image;
    }

    public function width(): int
    {
        return imagesx($this->image);
    }

    public function height(): int
    {
        return imagesy($this->image);
    }

    // ---------------------------------------------------------------- shapes

    public function rect(int $x, int $y, int $w, int $h, array $color, float $opacity = 1.0, int $radius = 0): self
    {
        if ($w <= 0 || $h <= 0) {
            return $this;
        }

        if ($radius <= 0) {
            imagefilledrectangle($this->image, $x, $y, $x + $w - 1, $y + $h - 1, $this->alloc($this->image, $color, $opacity));

            return $this;
        }

        $layer = $this->layer($w, $h);
        imagefilledrectangle($layer, 0, 0, $w - 1, $h - 1, $this->alloc($layer, $color, $opacity));
        $this->roundCorners($layer, $radius);
        $this->compose($layer, $x, $y);

        return $this;
    }

    /** Anti-aliased filled circle centred on ($cx, $cy). */
    public function circle(int $cx, int $cy, int $diameter, array $color, float $opacity = 1.0): self
    {
        $scale = 3;
        $big = $this->layer($diameter * $scale, $diameter * $scale);
        imagefilledellipse(
            $big,
            intdiv($diameter * $scale, 2),
            intdiv($diameter * $scale, 2),
            $diameter * $scale - 1,
            $diameter * $scale - 1,
            $this->alloc($big, $color, $opacity)
        );
        $small = $this->layer($diameter, $diameter);
        imagealphablending($small, false);
        imagecopyresampled($small, $big, 0, 0, 0, 0, $diameter, $diameter, $diameter * $scale, $diameter * $scale);
        $this->compose($small, $cx - intdiv($diameter, 2), $cy - intdiv($diameter, 2));

        return $this;
    }

    /**
     * Linear gradient over a region. $direction: vertical (top→bottom), horizontal (left→right),
     * diagonal (top-left→bottom-right).
     */
    public function gradient(
        array $from,
        array $to,
        string $direction = 'vertical',
        ?array $region = null,
        float $fromOpacity = 1.0,
        float $toOpacity = 1.0
    ): self {
        [$x, $y, $w, $h] = $region ?? [0, 0, $this->width(), $this->height()];
        if ($w <= 0 || $h <= 0) {
            return $this;
        }

        if ($direction === 'diagonal') {
            // Render small and upscale: smooth, and far cheaper than per-pixel work.
            $sw = max(2, (int) ceil($w / 8));
            $sh = max(2, (int) ceil($h / 8));
            $small = $this->layer($sw, $sh);
            imagealphablending($small, false);
            for ($py = 0; $py < $sh; $py++) {
                for ($px = 0; $px < $sw; $px++) {
                    $t = (($px / max(1, $sw - 1)) + ($py / max(1, $sh - 1))) / 2;
                    imagesetpixel($small, $px, $py, $this->alloc($small, Color::mix($from, $to, $t), $fromOpacity + ($toOpacity - $fromOpacity) * $t));
                }
            }
            $this->compose($this->upscale($small, $w, $h), $x, $y);

            return $this;
        }

        $steps = $direction === 'horizontal' ? $w : $h;
        for ($i = 0; $i < $steps; $i++) {
            $t = $steps > 1 ? $i / ($steps - 1) : 0;
            $c = $this->alloc($this->image, Color::mix($from, $to, $t), $fromOpacity + ($toOpacity - $fromOpacity) * $t);
            if ($direction === 'horizontal') {
                imagefilledrectangle($this->image, $x + $i, $y, $x + $i, $y + $h - 1, $c);
            } else {
                imagefilledrectangle($this->image, $x, $y + $i, $x + $w - 1, $y + $i, $c);
            }
        }

        return $this;
    }

    /** Soft radial light/colour blob that fades to transparent at its edge. */
    public function glow(int $cx, int $cy, int $radius, array $color, float $opacity = 0.5): self
    {
        $n = 64;
        $small = $this->layer($n, $n);
        imagealphablending($small, false);
        for ($py = 0; $py < $n; $py++) {
            for ($px = 0; $px < $n; $px++) {
                $d = hypot($px - ($n - 1) / 2, $py - ($n - 1) / 2) / (($n - 1) / 2);
                $a = $d >= 1 ? 0 : (1 - $d) ** 1.6 * $opacity;
                imagesetpixel($small, $px, $py, $this->alloc($small, $color, $a));
            }
        }
        $this->compose($this->upscale($small, $radius * 2, $radius * 2), $cx - $radius, $cy - $radius);

        return $this;
    }

    /**
     * Soft drop shadow under a (rounded) rectangle. Computed from the rectangle's distance field on a
     * coarse grid and upscaled smoothly, so it is cheap and has no hard edges.
     */
    public function shadow(int $x, int $y, int $w, int $h, int $radius = 0, int $blur = 16, float $opacity = 0.35, int $offsetY = 8): self
    {
        $blur = max(2, $blur);
        $pad = $blur;
        $div = max(1, min(4, intdiv(min($w, $h), 24)));
        $fullW = $w + 2 * $pad;
        $fullH = $h + 2 * $pad;
        $sw = max(2, (int) ceil($fullW / $div));
        $sh = max(2, (int) ceil($fullH / $div));
        $r = min($radius, intdiv(min($w, $h), 2));
        $small = $this->layer($sw, $sh);
        imagealphablending($small, false);

        $hx = $w / 2;
        $hy = $h / 2;
        for ($py = 0; $py < $sh; $py++) {
            $dy = abs((($py + 0.5) * $div - $pad) - $hy) - ($hy - $r);
            for ($px = 0; $px < $sw; $px++) {
                $dx = abs((($px + 0.5) * $div - $pad) - $hx) - ($hx - $r);
                $dist = hypot(max($dx, 0), max($dy, 0)) + min(max($dx, $dy), 0) - $r;
                $t = max(0.0, min(1.0, ($dist + $blur / 2) / $blur));
                $a = (1 - $t * $t * (3 - 2 * $t)) * $opacity;
                if ($a > 0.002) {
                    imagesetpixel($small, $px, $py, $this->alloc($small, [0, 0, 0], $a));
                }
            }
        }
        $this->compose($this->upscale($small, $fullW, $fullH), $x - $pad, $y - $pad + $offsetY);

        return $this;
    }

    // ---------------------------------------------------------------- images

    /**
     * Place an image into the box. 'cover' fills and crops; 'contain' fits inside and letterboxes.
     * $focus is the crop anchor for cover: [0.5, 0.5] = centre, [0.5, 0.0] = top.
     * $inset keeps a margin around the image in 'contain' mode.
     */
    public function picture(GdImage|string $source, int $x, int $y, int $w, int $h, string $fit = 'cover', int $radius = 0, array $focus = [0.5, 0.5], int $inset = 0): self
    {
        $src = ImageLoader::load($source);
        $sw = imagesx($src);
        $sh = imagesy($src);

        $layer = $this->layer($w, $h);
        imagealphablending($layer, false);

        if ($fit === 'contain') {
            $scale = min(($w - 2 * $inset) / $sw, ($h - 2 * $inset) / $sh);
            $dw = max(1, (int) round($sw * $scale));
            $dh = max(1, (int) round($sh * $scale));
            imagecopyresampled($layer, $src, intdiv($w - $dw, 2), intdiv($h - $dh, 2), 0, 0, $dw, $dh, $sw, $sh);
        } else {
            $scale = max($w / $sw, $h / $sh);
            $cw = (int) min($sw, round($w / $scale));
            $ch = (int) min($sh, round($h / $scale));
            $cx = (int) round(($sw - $cw) * max(0, min(1, $focus[0])));
            $cy = (int) round(($sh - $ch) * max(0, min(1, $focus[1])));
            imagecopyresampled($layer, $src, 0, 0, $cx, $cy, $w, $h, $cw, $ch);
        }

        $this->roundCorners($layer, $radius);
        $this->compose($layer, $x, $y);

        return $this;
    }

    /**
     * Draw an image scaled to fit ($boxW x $boxH) centred on ($cx, $cy), optionally rotated (degrees,
     * counter-clockwise). Transparent areas stay transparent, so cut-out products overlap naturally.
     *
     * @return array{0:int,1:int} drawn width, height
     */
    public function sprite(GdImage|string $source, int $cx, int $cy, int $boxW, int $boxH, float $angle = 0.0): array
    {
        $src = ImageLoader::load($source);
        $scale = min($boxW / imagesx($src), $boxH / imagesy($src));
        $w = max(1, (int) round(imagesx($src) * $scale));
        $h = max(1, (int) round(imagesy($src) * $scale));

        $scaled = $this->layer($w, $h);
        imagealphablending($scaled, false);
        imagecopyresampled($scaled, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src));

        if (abs($angle) > 0.01) {
            $rotated = imagerotate($scaled, $angle, imagecolorallocatealpha($scaled, 0, 0, 0, 127));
            if ($rotated !== false) {
                imagealphablending($rotated, false);
                imagesavealpha($rotated, true);
                $scaled = $rotated;
            }
        }
        $this->compose($scaled, $cx - intdiv(imagesx($scaled), 2), $cy - intdiv(imagesy($scaled), 2));

        return [$w, $h];
    }

    // ------------------------------------------------------------------ text

    /**
     * Draw already-wrapped lines. $y is the top of the first line box.
     *
     * @param string[] $lines
     */
    public function lines(
        array $lines,
        Typography $type,
        int $sizePx,
        int $x,
        int $y,
        int $boxWidth,
        array $color,
        string $align = 'left',
        int $lineHeightPx = 0,
        ?array $shadow = null,
        float $opacity = 1.0
    ): int {
        $lineHeightPx = $lineHeightPx ?: (int) round($sizePx * 1.2);
        $ascent = $type->ascent($sizePx);
        $leading = (int) round(($lineHeightPx - $sizePx) / 2);
        $baseline = $y + $leading + $ascent;
        $pt = $sizePx * Typography::PX_TO_PT;

        foreach ($lines as $line) {
            $tw = $type->measure($line, $sizePx)[0];
            $lx = match ($align) {
                'center' => $x + intdiv($boxWidth - $tw, 2),
                'right' => $x + $boxWidth - $tw,
                default => $x,
            };

            if ($shadow !== null) {
                $off = max(1, (int) round($sizePx * 0.04));
                imagettftext($this->image, $pt, 0, $lx + $off, $baseline + $off, $this->alloc($this->image, $shadow['color'] ?? [0, 0, 0], $shadow['opacity'] ?? 0.35), $type->fontPath, $line);
            }
            imagettftext($this->image, $pt, 0, $lx, $baseline, $this->alloc($this->image, $color, $opacity), $type->fontPath, $line);
            $baseline += $lineHeightPx;
        }

        return $y + count($lines) * $lineHeightPx;
    }

    // --------------------------------------------------------------- helpers

    /** Transparent layer to draw on before compositing. */
    private function layer(int $w, int $h): GdImage
    {
        $layer = imagecreatetruecolor(max(1, $w), max(1, $h));
        imagealphablending($layer, false);
        imagesavealpha($layer, true);
        imagefill($layer, 0, 0, imagecolorallocatealpha($layer, 0, 0, 0, 127));

        return $layer;
    }

    /** Smooth (bilinear) enlargement that keeps the alpha channel. */
    private function upscale(GdImage $small, int $w, int $h): GdImage
    {
        $big = imagescale($small, max(1, $w), max(1, $h), IMG_BILINEAR_FIXED);
        imagealphablending($big, false);
        imagesavealpha($big, true);

        return $big;
    }

    private function compose(GdImage $layer, int $x, int $y): void
    {
        imagealphablending($this->image, true);
        imagecopy($this->image, $layer, $x, $y, 0, 0, imagesx($layer), imagesy($layer));
    }

    private function alloc(GdImage $image, array $color, float $opacity): int
    {
        $alpha = (int) round(127 * (1 - max(0.0, min(1.0, $opacity))));

        return imagecolorallocatealpha($image, $color[0], $color[1], $color[2], $alpha);
    }

    /** Carve anti-aliased rounded corners into a layer by scaling the alpha of corner pixels. */
    private function roundCorners(GdImage $layer, int $radius): void
    {
        $w = imagesx($layer);
        $h = imagesy($layer);
        $r = min($radius, intdiv(min($w, $h), 2));
        if ($r <= 0) {
            return;
        }

        imagealphablending($layer, false);
        for ($dy = 0; $dy < $r; $dy++) {
            for ($dx = 0; $dx < $r; $dx++) {
                $coverage = max(0.0, min(1.0, $r - hypot($r - ($dx + 0.5), $r - ($dy + 0.5)) + 0.5));
                if ($coverage >= 1.0) {
                    continue;
                }
                foreach ([[$dx, $dy], [$w - 1 - $dx, $dy], [$dx, $h - 1 - $dy], [$w - 1 - $dx, $h - 1 - $dy]] as [$px, $py]) {
                    $c = imagecolorat($layer, $px, $py);
                    $alpha = ($c >> 24) & 0x7F;
                    $alpha = (int) round(127 - (127 - $alpha) * $coverage);
                    imagesetpixel($layer, $px, $py, ($alpha << 24) | ($c & 0xFFFFFF));
                }
            }
        }
    }
}
