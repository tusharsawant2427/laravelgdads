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
    /** @var (callable(string,int,int,int,int):void)|null */
    private static $observer = null;

    /**
     * Debug hook: the callback receives (kind, x, y, width, height) for every region drawn by draw(), rect(), text() and circle().
     * Used by TemplateLibrary::artwork() to measure where a template's illustrations are. Pass null to stop observing.
     *
     * @param (callable(string,int,int,int,int):void)|null $observer
     */
    public static function observe(?callable $observer): void
    {
        self::$observer = $observer;
    }

    private static function note(string $kind, int|float $x, int|float $y, int|float $w, int|float $h): void
    {
        if (self::$observer !== null) {
            (self::$observer)($kind, (int) round($x), (int) round($y), (int) round($w), (int) round($h));
        }
    }
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

    /** Point drawing at another image of the same size (a transparent layer) and return the one it was drawing on. */
    public function retarget(GdImage $image): GdImage
    {
        $previous = $this->image;
        $this->image = $image;
        imagealphablending($this->image, true);
        imagesavealpha($this->image, true);

        return $previous;
    }

    /** Whether something is watching the drawing calls (see observe()). */
    public static function observing(): bool
    {
        return self::$observer !== null;
    }

    /** Report a region to the observer, if any. */
    public static function report(string $kind, int $x, int $y, int $w, int $h): void
    {
        self::note($kind, $x, $y, $w, $h);
    }

    /** Stop reporting while $run executes (nested drawing that is reported as a whole afterwards). */
    public static function unobserved(callable $run): void
    {
        $saved = self::$observer;
        self::$observer = null;
        try {
            $run();
        } finally {
            self::$observer = $saved;
        }
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
        self::note('rect', $x, $y, $w, $h);
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
        self::note('circle', $cx - $diameter / 2, $cy - $diameter / 2, $diameter, $diameter);
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

    /**
     * Draw anti-aliased decoration: $draw receives a transparent layer at $scale times the canvas size
     * (so 1px strokes stay smooth after downsampling) and the scale factor.
     *
     * @param callable(GdImage, int): void $draw
     */
    public function decor(callable $draw, int $scale = 2): self
    {
        $w = $this->width();
        $h = $this->height();
        $big = $this->layer($w * $scale, $h * $scale);
        $draw($big, $scale);

        $small = $this->layer($w, $h);
        imagealphablending($small, false);
        imagecopyresampled($small, $big, 0, 0, 0, 0, $w, $h, $w * $scale, $h * $scale);
        $this->compose($small, 0, 0);

        return $this;
    }

    public function colorOn(GdImage $layer, array $color, float $opacity): int
    {
        return $this->alloc($layer, $color, $opacity);
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



    // ----------------------------------------------------- hand-made shapes

    /**
     * Draw anti-aliased vector shapes inside a region. $draw receives a transparent layer scaled by $k
     * (coordinates are local to the region, multiplied by $k) and a colour allocator fn(array $rgb, float $opacity): int.
     * Cheaper than decor() for small shapes: only the region is rendered and downsampled.
     *
     * @param callable(GdImage, int, callable): void $draw
     */
    public function draw(int|float $x, int|float $y, int|float $w, int|float $h, callable $draw, int $k = 3): self
    {
        [$x, $y, $w, $h] = [(int) round($x), (int) round($y), (int) round($w), (int) round($h)];
        if ($w < 1 || $h < 1) {
            return $this;
        }
        $big = $this->layer($w * $k, $h * $k);
        $draw($big, $k, fn (array $c, float $a = 1.0): int => $this->alloc($big, $c, $a));

        $small = $this->layer($w, $h);
        imagealphablending($small, false);
        imagecopyresampled($small, $big, 0, 0, 0, 0, $w, $h, $w * $k, $h * $k);
        if (self::$observer !== null) {
            // Report the visible shapes, not the (padded) region they were drawn in.
            [$x0, $y0, $x1, $y1] = [$w, $h, -1, -1];
            for ($py = 0; $py < $h; $py += 2) {
                for ($px = 0; $px < $w; $px += 2) {
                    if ((((int) imagecolorat($small, $px, $py)) >> 24 & 0x7F) < 100) {
                        [$x0, $y0, $x1, $y1] = [min($x0, $px), min($y0, $py), max($x1, $px), max($y1, $py)];
                    }
                }
            }
            if ($x1 >= $x0) {
                self::note('draw', $x + $x0, $y + $y0, $x1 - $x0 + 2, $y1 - $y0 + 2);
            }
        }
        $this->compose($small, $x, $y);

        return $this;
    }

    /**
     * Soft, feathered shapes of one colour (watercolour washes). $draw paints at 1/$shrink scale; the colours it asks for
     * are only used for their opacity. The result is blurred, enlarged smoothly and used as the transparency of $tint.
     * (GD's blur filter leaves the alpha channel alone, so the blur is done on an opaque mask instead.)
     *
     * @param callable(GdImage, int, callable): void $draw receives (mask, shrink, fn(array $rgb, float $opacity): int)
     */
    public function soft(int $x, int $y, int $w, int $h, callable $draw, int $shrink = 4, int $blur = 2, array $tint = [128, 128, 128]): self
    {
        $sw = max(2, intdiv($w, $shrink));
        $sh = max(2, intdiv($h, $shrink));
        $mask = imagecreatetruecolor($sw, $sh);
        imagefill($mask, 0, 0, imagecolorallocate($mask, 0, 0, 0));
        $draw($mask, $shrink, function (array $c, float $a = 1.0) use ($mask): int {
            $g = (int) round(255 * max(0.0, min(1.0, $a)));

            return imagecolorallocate($mask, $g, $g, $g);
        });
        for ($i = 0; $i < $blur; $i++) {
            imagefilter($mask, IMG_FILTER_GAUSSIAN_BLUR);
        }
        $big = imagescale($mask, max(1, $w), max(1, $h), IMG_BILINEAR_FIXED);

        $layer = $this->layer($w, $h);
        imagealphablending($layer, false);
        for ($py = 0; $py < $h; $py++) {
            for ($px = 0; $px < $w; $px++) {
                $g = imagecolorat($big, $px, $py) & 0xFF;
                if ($g > 1) {
                    imagesetpixel($layer, $px, $py, imagecolorallocatealpha($layer, $tint[0], $tint[1], $tint[2], 127 - (int) ($g * 127 / 255)));
                }
            }
        }
        $this->compose($layer, $x, $y);

        return $this;
    }

    /**
     * Organic filled blob (soft, irregular ellipse): the paint-splash shapes behind products and slogans.
     */
    public function blob(int $cx, int $cy, int $rx, int $ry, array $color, float $opacity = 1.0, int $seed = 1, float $wobble = 0.1): self
    {
        $pad = (int) ceil(max($rx, $ry) * $wobble * 2.2) + 2;
        $x = $cx - $rx - $pad;
        $y = $cy - $ry - $pad;
        $w = 2 * ($rx + $pad);
        $h = 2 * ($ry + $pad);

        return $this->draw($x, $y, $w, $h, function (GdImage $layer, int $k, callable $c) use ($rx, $ry, $pad, $color, $opacity, $seed, $wobble) {
            $pts = self::blobPoints($rx + $pad, $ry + $pad, $rx, $ry, $seed, $wobble, $k);
            imagefilledpolygon($layer, $pts, $c($color, $opacity));
        });
    }

    /** @return int[] flat polygon points of a wobbly ellipse, scaled by $k */
    public static function blobPoints(float $cx, float $cy, float $rx, float $ry, int $seed, float $wobble = 0.1, int $k = 1, int $n = 72): array
    {
        $p1 = fmod($seed * 12.9898, 6.283);
        $p2 = fmod($seed * 78.233, 6.283);
        $p3 = fmod($seed * 37.719, 6.283);
        $pts = [];
        for ($i = 0; $i < $n; $i++) {
            $a = $i / $n * M_PI * 2;
            $r = 1 + $wobble * (sin(2 * $a + $p1) * 0.9 + sin(3 * $a + $p2) * 0.6 + sin(5 * $a + $p3) * 0.3);
            $pts[] = (int) round(($cx + cos($a) * $rx * $r) * $k);
            $pts[] = (int) round(($cy + sin($a) * $ry * $r) * $k);
        }

        return $pts;
    }

    /**
     * A highlighter / brush stroke: a slightly rotated band with ragged, tapering ends.
     */
    public function brush(int $x, int $y, int $w, int $h, array $color, float $angleDeg = -3.0, float $opacity = 1.0, int $seed = 3): self
    {
        $pad = (int) ceil($w * 0.04) + (int) ceil(abs(sin(deg2rad($angleDeg))) * $w) + 4;

        return $this->draw($x - $pad, $y - $pad, $w + 2 * $pad, $h + 2 * $pad, function (GdImage $layer, int $k, callable $c) use ($pad, $w, $h, $color, $opacity, $angleDeg, $seed) {
            $rand = self::seeded($seed);
            $top = [];
            $bottom = [];
            $steps = 28;
            for ($i = 0; $i <= $steps; $i++) {
                $t = $i / $steps;
                $edge = $i === 0 || $i === $steps ? 0.22 : 0.06;          // ragged, tapering ends
                $top[] = [$t * $w + ($i === 0 ? $w * 0.012 : ($i === $steps ? -$w * 0.01 : 0)), $h * ($edge * $rand())];
                $bottom[] = [$t * $w + ($rand() - 0.5) * $w * 0.006, $h * (1 - $edge * $rand())];
            }
            $poly = array_merge($top, array_reverse($bottom));
            $cx = $w / 2;
            $cy = $h / 2;
            $rad = deg2rad(-$angleDeg);
            $flat = [];
            foreach ($poly as [$px, $py]) {
                $dx = $px - $cx;
                $dy = $py - $cy;
                $flat[] = (int) round(($pad + $cx + $dx * cos($rad) - $dy * sin($rad)) * $k);
                $flat[] = (int) round(($pad + $cy + $dx * sin($rad) + $dy * cos($rad)) * $k);
            }
            imagefilledpolygon($layer, $flat, $c($color, $opacity));
        });
    }

    /** Deterministic 0..1 generator. */
    public static function seeded(int $seed): \Closure
    {
        $state = abs($seed) % 2147483647 ?: 1;

        return function () use (&$state): float {
            $state = ($state * 48271) % 2147483647;

            return $state / 2147483647;
        };
    }

    // ---------------------------------------------------------- fancy text

    /**
     * Text at any angle. ($x, $baselineY) is the start of the baseline; $angleDeg is counter-clockwise.
     */
    public function text(string $text, Typography $type, int $sizePx, float $x, float $baselineY, array $color, float $angleDeg = 0.0, float $opacity = 1.0): self
    {
        self::note('text', $x, $baselineY - $sizePx, $type->measure($text, $sizePx)[0], $sizePx * 1.3);
        imagettftext($this->image, $sizePx * Typography::PX_TO_PT, $angleDeg, (int) round($x), (int) round($baselineY), $this->alloc($this->image, $color, $opacity), $type->fontPath, $text);

        return $this;
    }

    /**
     * "Sticker" lettering: thick text with an outline and a hard offset shadow in another colour, optionally
     * squeezed horizontally. ($x, $y) is the top-left of the line box; returns [width, height] drawn.
     *
     * @return array{0:int,1:int}
     */
    public function stickerText(string $text, Typography $type, int $sizePx, int $x, int $y, array $fill, array $outline, array $shadow, int $outlinePx = 3, int $shadowPx = 6, float $squeeze = 1.0, int $bold = 2): array
    {
        $k = 2;
        $pad = ($outlinePx + $shadowPx + $bold + 4) * $k;
        [$tw] = $type->measure($text, $sizePx * $k);
        $lh = (int) round($sizePx * 1.2);
        $lw = $tw + 2 * $pad;
        $layer = $this->layer($lw, $lh * $k + 2 * $pad);
        $pt = $sizePx * $k * Typography::PX_TO_PT;
        $baseline = $pad + (int) (($lh * $k - $sizePx * $k * 1.2) / 2 + $type->ascent($sizePx * $k) + $sizePx * $k * 0.1);

        $ring = static function (int $r, int $steps): array {
            $o = [];
            for ($i = 0; $i < $steps; $i++) {
                $o[] = [(int) round(cos($i / $steps * M_PI * 2) * $r), (int) round(sin($i / $steps * M_PI * 2) * $r)];
            }

            return $o;
        };
        $drawText = function (int $dx, int $dy, array $col) use ($layer, $pt, $baseline, $pad, $type, $text) {
            imagettftext($layer, $pt, 0, $pad + $dx, $baseline + $dy, $this->alloc($layer, $col, 1.0), $type->fontPath, $text);
        };

        // 1) offset shadow: the outline ring, moved down-right
        foreach ([$shadowPx * $k, (int) ($shadowPx * $k * 0.5)] as $off) {
            foreach ($ring(($outlinePx + $bold) * $k, 20) as [$ox, $oy]) {
                $drawText($ox + $off, $oy + $off, $shadow);
            }
        }
        // 2) outline
        foreach ($ring(($outlinePx + $bold) * $k, 24) as [$ox, $oy]) {
            $drawText($ox, $oy, $outline);
        }
        // 3) fill, fattened a little
        foreach ($ring($bold * $k, 12) as [$ox, $oy]) {
            $drawText($ox, $oy, $fill);
        }
        $drawText(0, 0, $fill);

        $dw = max(1, (int) round($lw / $k * $squeeze));
        $dh = (int) round(imagesy($layer) / $k);
        $small = $this->layer($dw, $dh);
        imagealphablending($small, false);
        imagecopyresampled($small, $layer, 0, 0, 0, 0, $dw, $dh, imagesx($layer), imagesy($layer));
        $this->compose($small, $x - (int) round($pad / $k * $squeeze), $y - (int) round($pad / $k));

        return [(int) round($tw / $k * $squeeze), $lh];
    }

    /**
     * Multiply an image over a region (white stays unchanged, darker shows through): lets a picture on a white
     * background melt into a light page. The left and top edges fade out over $fade pixels.
     */
    public function multiply(GdImage|string $source, int $x, int $y, int $w, int $h, int $fade = 60): self
    {
        $src = ImageLoader::load($source);
        $tile = $this->layer($w, $h);
        imagealphablending($tile, false);
        imagecopyresampled($tile, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src));

        for ($py = 0; $py < $h; $py++) {
            $fy = $fade > 0 ? min(1.0, $py / $fade) : 1.0;
            for ($px = 0; $px < $w; $px++) {
                $dx = $x + $px;
                $dy = $y + $py;
                if ($dx < 0 || $dy < 0 || $dx >= $this->width() || $dy >= $this->height()) {
                    continue;
                }
                $fx = $fade > 0 ? min(1.0, $px / $fade) : 1.0;
                $amount = $fx * $fy;
                $s = imagecolorat($tile, $px, $py);
                $d = imagecolorat($this->image, $dx, $dy);
                $out = 0;
                foreach ([16, 8, 0] as $shift) {
                    $sc = (($s >> $shift) & 255) / 255;
                    $dc = (($d >> $shift) & 255);
                    $mul = $dc * $sc;
                    $out |= ((int) round($dc + ($mul - $dc) * $amount)) << $shift;
                }
                imagesetpixel($this->image, $dx, $dy, $out);
            }
        }

        return $this;
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
