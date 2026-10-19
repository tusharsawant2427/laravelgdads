<?php

namespace Packitifotech\Laragdads\Ads\Layouts;

use GdImage;
use Packitifotech\Laragdads\Ads\AdSpec;
use Packitifotech\Laragdads\Ads\Support\BookMockup;
use Packitifotech\Laragdads\Ads\Support\Canvas;
use Packitifotech\Laragdads\Ads\Support\Color;
use Packitifotech\Laragdads\Ads\Support\Icons;
use Packitifotech\Laragdads\Ads\Support\Typography;
use Packitifotech\Laragdads\Ads\Theme;

/**
 * A light, friendly "illustrated" ad, wide (about 3:1) or as a 6:1 strip:
 *
 *   [book on a brand-coloured blob]  [handwritten tagline on a brush stroke]   [slogan on a blob
 *                                     [HUGE sticker title + dark subject pill]    + doodles + artwork]
 *                                     [four icon cards: title / subtitle]
 *
 * Needs ->hero('MHT-CET', 'PHYSICS') (title and subject), optionally ->tagline(), ->highlights(), ->slogan(),
 * ->logo() (top right) and ->artwork(). Everything is drawn with GD from the brand colour of the cover.
 */
final class CompanionLayout extends AbstractLayout
{
    private const PASTELS = [[226, 214, 246], [253, 238, 186], [250, 216, 232], [212, 240, 228]];

    public function render(Canvas $canvas, AdSpec $spec, Theme $theme, Typography $headingFont, Typography $bodyFont): void
    {
        $ratio = $spec->aspectRatio();
        if ($ratio < 2.4 || ($ratio >= 4.5 && $spec->height < 140)) {
            $this->fallback($canvas, $spec, $theme, $headingFont, $bodyFont);

            return;
        }

        $script = new Typography($spec->scriptFontPath ?? dirname(__DIR__, 3) . '/resources/fonts/LiberationSerif-BoldItalic.ttf');
        [$h, $s] = Color::toHsl($theme->primary);
        $pal = [
            'ink' => Color::fromHsl($h, min(0.7, max($s, 0.35)), 0.2),
            'brand' => Color::fromHsl($h, max($s, 0.5), 0.46),
            // The brush stroke and shadow are a true highlighter yellow, whatever the cover's accent is.
            'gold' => [253, 204, 72],
        ];

        $ratio >= 4.5
            ? $this->strip($canvas, $spec, $headingFont, $bodyFont, $script, $pal)
            : $this->full($canvas, $spec, $headingFont, $bodyFont, $script, $pal);
    }

    // ------------------------------------------------------------------- full

    private function full(Canvas $canvas, AdSpec $spec, Typography $bold, Typography $body, Typography $script, array $pal): void
    {
        $w = $spec->width;
        $h = $spec->height;
        $ink = $pal['ink'];
        $brand = $pal['brand'];
        $gold = $pal['gold'];

        // Backdrop: brand blob + paler blob + pedestal for the book.
        $canvas->blob((int) ($w * 0.075), (int) ($h * 0.5), (int) ($w * 0.19), (int) ($h * 0.7), $brand, 1.0, 11, 0.06);
        $canvas->blob((int) ($w * 0.02), (int) ($h * 0.05), (int) ($w * 0.1), (int) ($h * 0.3), Color::lighten($brand, 0.2), 0.55, 4, 0.1);
        $this->dots($canvas, (int) ($w * 0.17), (int) ($h * 0.7), 9, 7, (int) ($h * 0.022), Color::lighten($brand, 0.45), 0.7);
        $px = (int) ($w * 0.012);
        $pw = (int) ($w * 0.235);
        $canvas->shadow($px, (int) ($h * 0.86), $pw, (int) ($h * 0.16), (int) ($h * 0.03), (int) ($h * 0.05), 0.25, (int) ($h * 0.02));
        $canvas->rect($px, (int) ($h * 0.86), $pw, (int) ($h * 0.2), Color::lighten($brand, 0.3), 1.0, (int) ($h * 0.035));
        $canvas->rect($px, (int) ($h * 0.855), $pw, (int) ($h * 0.04), Color::lighten($brand, 0.55), 1.0, (int) ($h * 0.02));

        $this->book($canvas, $spec, (int) ($w * 0.038), (int) ($h * 0.085), (int) ($w * 0.205), (int) ($h * 0.78), 0.0);

        // Centre column.
        $zx = (int) ($w * 0.272);
        $zw = (int) ($w * 0.43);
        $lines = $spec->hero !== [] ? $spec->hero : array_filter([$spec->headline]);
        $title = (string) ($lines[0] ?? '');
        $subject = (string) ($lines[1] ?? '');

        if ($spec->tagline) {
            $this->tagline($canvas, $spec->tagline, $script, $zx + (int) ($zw * 0.03), (int) ($h * 0.075), (int) ($zw * 0.96), (int) ($h * 0.1), $ink, $gold);
        }
        $this->sparkles($canvas, $zx, $zw, (int) ($h * 0.3), (int) ($h * 0.3), $gold);

        $titleSize = $this->fitWidth($bold, $title, (int) ($zw * 0.94), (int) ($h * 0.27), 0.92);
        [$tw] = $canvas->stickerText($title, $bold, $titleSize, $zx + (int) (($zw - $this->squeezed($bold, $title, $titleSize, 0.92)) / 2), (int) ($h * 0.165), $ink, [255, 255, 255], $gold, max(2, (int) ($h * 0.007)), max(4, (int) ($h * 0.012)), 0.92, max(1, (int) ($h * 0.004)));

        if ($subject !== '') {
            $this->pill($canvas, $subject, $bold, $zx, $zw, (int) ($h * 0.435), (int) ($h * 0.15), $ink, $gold);
        }

        // Feature cards.
        if ($spec->highlights !== []) {
            $this->cards($canvas, $spec->highlights, $bold, $body, $zx - (int) ($w * 0.012), $zw + (int) ($w * 0.024), (int) ($h * 0.625), $h, $ink, false);
        }

        $this->rightZone($canvas, $spec, $script, $body, $pal);

        if ($spec->logo !== null) {
            $this->logoBox($canvas, $spec, (int) ($w * 0.815), (int) ($h * 0.035), (int) ($w * 0.165), (int) ($h * 0.15));
        }
    }

    private function rightZone(Canvas $canvas, AdSpec $spec, Typography $script, Typography $body, array $pal): void
    {
        $w = $spec->width;
        $h = $spec->height;
        $brand = $pal['brand'];
        $ink = $pal['ink'];
        $topic = mb_strtolower(implode(' ', array_merge($spec->hero, [$spec->headline ?? ''])));

        if ($spec->artwork !== null) {
            $aw = (int) ($w * 0.3);
            $ah = (int) ($h * 0.5);
            $canvas->multiply($spec->artwork, $w - $aw, $h - $ah, $aw, $ah, (int) ($h * 0.12));
        }

        if ($spec->slogan !== []) {
            $canvas->blob((int) ($w * 0.8), (int) ($h * 0.36), (int) ($w * 0.085), (int) ($h * 0.24), Color::mix([255, 255, 255], $brand, 0.22), 1.0, 7, 0.08);
            $size = (int) round($h * 0.085);
            $y = $h * 0.3;
            foreach ($spec->slogan as $i => $word) {
                $fit = $this->fitWidth($script, $word, (int) ($w * 0.11), $size, 1.0);
                $canvas->text($word, $script, $fit, $w * (0.742 + 0.012 * $i), $y + $i * $h * 0.098, $ink, 12);
            }
            $canvas->brush((int) ($w * 0.77), (int) ($h * 0.53), (int) ($w * 0.065), (int) ($h * 0.012), $pal['gold'], 12, 1.0, 5);
        }

        // Hand-drawn doodles for the subject.
        [$icons, $formula] = $this->doodles($topic);
        $line = Color::lighten($brand, 0.1);
        $positions = [[0.887, 0.22, 0.13], [0.84, 0.66, 0.16], [0.775, 0.84, 0.12], [0.94, 0.52, 0.12]];
        foreach ($icons as $i => $icon) {
            if (isset($positions[$i])) {
                [$px, $py, $ps] = $positions[$i];
                Icons::draw($canvas, $icon, (int) ($w * $px), (int) ($h * $py), (int) ($h * $ps), $line, 0.04, 0.85);
            }
        }
        if ($formula !== '') {
            $canvas->text($formula, $script, (int) ($h * 0.075), $w * 0.9, $h * 0.40, $line, 8, 0.85);
        }
    }

    // ------------------------------------------------------------------ strip

    private function strip(Canvas $canvas, AdSpec $spec, Typography $bold, Typography $body, Typography $script, array $pal): void
    {
        $w = $spec->width;
        $h = $spec->height;
        $ink = $pal['ink'];
        $brand = $pal['brand'];
        $gold = $pal['gold'];

        $canvas->blob((int) ($w * 0.065), (int) ($h * 0.5), (int) ($w * 0.125), (int) ($h * 0.78), $brand, 1.0, 11, 0.06);
        $canvas->blob((int) ($w * 0.005), (int) ($h * 0.02), (int) ($w * 0.05), (int) ($h * 0.4), Color::lighten($brand, 0.2), 0.55, 4, 0.1);
        $this->book($canvas, $spec, (int) ($w * 0.018), (int) ($h * 0.07), (int) ($w * 0.12), (int) ($h * 0.86), 0.0);

        $zx = (int) ($w * 0.17);
        $zw = (int) ($w * 0.43);
        $lines = $spec->hero !== [] ? $spec->hero : array_filter([$spec->headline]);
        $title = (string) ($lines[0] ?? '');
        $subject = (string) ($lines[1] ?? '');

        if ($spec->tagline) {
            $this->tagline($canvas, $spec->tagline, $script, $zx + (int) ($zw * 0.02), (int) ($h * 0.105), (int) ($zw * 0.92), (int) ($h * 0.14), $ink, $gold);
        }
        $titleSize = $this->fitWidth($bold, $title, (int) ($zw * 0.92), (int) ($h * 0.4), 0.9);
        $canvas->stickerText($title, $bold, $titleSize, $zx + (int) (($zw - $this->squeezed($bold, $title, $titleSize, 0.9)) / 2), (int) ($h * 0.215), $ink, [255, 255, 255], $gold, 2, 4, 0.9, 1);
        if ($subject !== '') {
            $this->pill($canvas, $subject, $bold, $zx, $zw, (int) ($h * 0.655), (int) ($h * 0.27), $ink, $gold);
        }

        if ($spec->highlights !== []) {
            $items = array_slice($spec->highlights, 0, 4);
            $gx = (int) ($w * 0.625);
            $gw = (int) ($w * 0.365);
            $rows = array_chunk($items, 2);
            foreach ($rows as $r => $row) {
                $this->cards($canvas, $row, $bold, $body, $gx, $gw, (int) ($h * (0.23 + 0.385 * $r)), (int) ($h * 0.385), $ink, true);
            }
        }

        if ($spec->logo !== null) {
            $this->logoBox($canvas, $spec, (int) ($w * 0.855), (int) ($h * 0.03), (int) ($w * 0.135), (int) ($h * 0.16));
        }
    }

    // ---------------------------------------------------------------- pieces

    /** Place the product: a packshot becomes a 3D book (white backdrop removed), anything else is shown whole in a card. */
    private function book(Canvas $canvas, AdSpec $spec, int $x, int $y, int $w, int $h, float $angle): void
    {
        if (!$spec->hasImages()) {
            return;
        }
        $image = $spec->images[0];
        $cutout = $spec->cutout ?? $this->isPackshot($image);
        if ($cutout) {
            $cover = BookMockup::make($this->prepare($image, $spec));
            $canvas->shadow($x + (int) ($w * 0.1), $y + (int) ($h * 0.93), (int) ($w * 0.8), (int) ($h * 0.03), (int) ($h * 0.015), (int) ($h * 0.05), 0.4, 0);
            $canvas->sprite($cover, $x + intdiv($w, 2), $y + intdiv($h, 2), $w, $h, $angle);

            return;
        }
        $fitted = clone $spec;
        $fitted->imageFit = 'contain';
        $this->card($canvas, $fitted, $image, $x, $y, $w, $h, (int) round(min($w, $h) * 0.04));
    }

    /** Handwritten line on a yellow brush stroke, both tilted up to the right. */
    private function tagline(Canvas $canvas, string $text, Typography $script, int $x, int $y, int $w, int $h, array $ink, array $gold): void
    {
        $size = $this->fitWidth($script, $text, (int) ($w * 0.9), (int) ($h * 0.78), 1.0);
        $canvas->brush($x, $y, $w, $h, $gold, -3.0, 1.0, 9);
        $textW = $script->measure($text, $size)[0];
        $canvas->text($text, $script, $size, $x + ($w - $textW) / 2, $y + $h * 0.7, $ink, 3.0);
    }

    /** Dark rounded subject pill with a hard yellow offset shadow. */
    private function pill(Canvas $canvas, string $text, Typography $bold, int $zx, int $zw, int $y, int $h, array $ink, array $gold): void
    {
        $size = $this->fitWidth($bold, $text, (int) ($zw * 0.7), (int) ($h * 0.62), 1.0);
        $tw = $bold->measure($text, $size)[0];
        $pw = min($zw, $tw + (int) ($h * 0.9));
        $x = $zx + intdiv($zw - $pw, 2);
        $off = max(3, (int) ($h * 0.07));

        $canvas->rect($x + $off, $y + $off, $pw, $h, $gold, 1.0, intdiv($h, 2));
        $canvas->rect($x, $y, $pw, $h, $ink, 1.0, intdiv($h, 2));
        $canvas->gradient([255, 255, 255], [255, 255, 255], 'vertical', [$x + intdiv($h, 3), $y + 2, $pw - 2 * intdiv($h, 3), intdiv($h, 3)], 0.12, 0.0);
        $lh = (int) round($size * 1.2);
        // Fatten the white lettering a little, like the sticker title.
        foreach ([[0, 0], [1, 0], [0, 1], [1, 1]] as [$dx, $dy]) {
            $canvas->lines([$text], $bold, $size, $x + $dx, $y + intdiv($h - $lh, 2) + $dy, $pw, [255, 255, 255], 'center', $lh);
        }
    }

    /**
     * A row of icon cards: pastel blob, line icon, bold title, lighter subtitle. $compact puts the text beside the icon.
     *
     * @param array<int, array{0:string,1:string,2:string}> $items
     */
    private function cards(Canvas $canvas, array $items, Typography $bold, Typography $body, int $x, int $w, int $y, int $h, array $ink, bool $compact): void
    {
        $n = count($items);
        if ($n === 0) {
            return;
        }
        $colW = intdiv($w, $n);

        foreach (array_values($items) as $i => [$icon, $title, $subtitle]) {
            $cx = $x + $i * $colW;
            $pastel = self::PASTELS[$i % 4];

            if ($compact) {
                $d = (int) ($h * 0.74);
                $icx = $cx + intdiv($d, 2) + 4;
                $icy = $y + intdiv($h, 2);
                $canvas->blob($icx, $icy, intdiv($d, 2), intdiv($d, 2), $pastel, 1.0, 3 + $i, 0.05);
                Icons::draw($canvas, $icon, $icx, $icy, (int) ($d * 0.6), $ink, 0.055);
                $tx = $cx + $d + 14;
                $tw = $colW - $d - 18;
                $t = $bold->fit($title, $tw, (int) ($h * 0.46), (int) ($h * 0.22), 9, 1.1, 2);
                $sub = $subtitle !== '' ? $body->fit($subtitle, $tw, (int) ($h * 0.3), (int) ($h * 0.18), 8, 1.1, 1) : null;
                $total = $t['height'] + ($sub['height'] ?? 0);
                $ty = $y + intdiv($h - $total, 2);
                $canvas->lines($t['lines'], $bold, $t['size'], $tx, $ty, $tw, $ink, 'left', $t['lineHeight']);
                if ($sub) {
                    $canvas->lines($sub['lines'], $body, $sub['size'], $tx, $ty + $t['height'], $tw, $ink, 'left', $sub['lineHeight'], null, 0.8);
                }
                continue;
            }

            $d = (int) ($h * 0.16);
            $icx = $cx + intdiv($colW, 2);
            $icy = $y + intdiv($d, 2);
            $canvas->blob($icx, $icy, intdiv($d, 2), (int) ($d * 0.48), $pastel, 1.0, 3 + $i, 0.06);
            Icons::draw($canvas, $icon, $icx, $icy, (int) ($d * 0.56), $ink, 0.05);

            $tw = (int) ($colW * 0.94);
            $textY = $y + $d + (int) ($h * 0.012);
            $t = $bold->fit($title, $tw, (int) ($h * 0.1), (int) ($h * 0.042), 10, 1.1, 3);
            $canvas->lines($t['lines'], $bold, $t['size'], $cx + intdiv($colW - $tw, 2), $textY, $tw, $ink, 'center', $t['lineHeight']);
            if ($subtitle !== '') {
                $sub = $body->fit($subtitle, $tw, (int) ($h * 0.075), (int) ($h * 0.034), 9, 1.1, 2);
                $canvas->lines($sub['lines'], $body, $sub['size'], $cx + intdiv($colW - $tw, 2), $textY + $t['height'], $tw, $ink, 'center', $sub['lineHeight'], null, 0.8);
            }
        }
    }

    private function sparkles(Canvas $canvas, int $zx, int $zw, int $y, int $span, array $gold): void
    {
        foreach ([[-0.045, 0.0, 55], [-0.055, 0.1, 75], [0.99, 0.12, 125], [1.0, 0.24, 105]] as [$fx, $fy, $angle]) {
            $canvas->brush($zx + (int) ($zw * $fx), $y + (int) ($span * $fy), (int) ($zw * 0.045), (int) ($zw * 0.012), $gold, (float) $angle, 1.0, 17);
        }
    }

    private function dots(Canvas $canvas, int $x, int $y, int $cols, int $rows, int $gap, array $color, float $opacity): void
    {
        $canvas->draw($x, $y, $cols * $gap, $rows * $gap, function (GdImage $l, int $k, callable $c) use ($cols, $rows, $gap, $color, $opacity) {
            $col = $c($color, $opacity);
            for ($r = 0; $r < $rows; $r++) {
                for ($q = 0; $q < $cols; $q++) {
                    imagefilledellipse($l, (int) (($q + 0.5) * $gap * $k), (int) (($r + 0.5) * $gap * $k), (int) ($gap * 0.35 * $k), (int) ($gap * 0.35 * $k), $col);
                }
            }
        });
    }

    private function logoBox(Canvas $canvas, AdSpec $spec, int $x, int $y, int $w, int $h): void
    {
        $canvas->picture($this->prepare($spec->logo, $spec), $x, $y, $w, $h, 'contain');
    }

    /** @return array{0:string[],1:string} doodle icons and an optional handwritten formula for the subject */
    private function doodles(string $topic): array
    {
        return match (true) {
            str_contains($topic, 'physics') => [['bulb', 'atom', 'incline', 'prism'], 'E = mc²'],
            str_contains($topic, 'chem') => [['flask', 'atom', 'bulb', 'target'], 'H₂O'],
            str_contains($topic, 'math') => [['compass', 'pencil', 'target', 'star'], 'a² + b² = c²'],
            str_contains($topic, 'bio') => [['atom', 'flask', 'bulb', 'star'], 'DNA'],
            default => [['cap', 'book', 'star', 'bulb'], ''],
        };
    }

    // --------------------------------------------------------------- helpers

    /** Largest size (px) at which $text, squeezed horizontally by $squeeze, fits $maxW. */
    private function fitWidth(Typography $font, string $text, int $maxW, int $maxSize, float $squeeze): int
    {
        $lo = 8;
        $hi = max(8, $maxSize);
        while ($lo < $hi) {
            $mid = intdiv($lo + $hi + 1, 2);
            if ($font->measure($text, $mid)[0] * $squeeze <= $maxW) {
                $lo = $mid;
            } else {
                $hi = $mid - 1;
            }
        }

        return $lo;
    }

    private function squeezed(Typography $font, string $text, int $size, float $squeeze): int
    {
        return (int) round($font->measure($text, $size)[0] * $squeeze);
    }

    private function fallback(Canvas $canvas, AdSpec $spec, Theme $theme, Typography $bold, Typography $body): void
    {
        $s = clone $spec;
        $s->headline ??= trim(implode(' ', $spec->hero));
        $s->subheadline ??= $spec->tagline;
        foreach ($spec->highlights as [, $title, $subtitle]) {
            if (count($s->features) < 4) {
                $s->features[] = trim($title . ($subtitle !== '' ? ' ' . $subtitle : ''));
            }
        }
        $layout = $spec->aspectRatio() >= 3.2 ? new ShowcaseLayout() : new BooksLayout();
        $layout->render($canvas, $s, $theme, $bold, $body);
    }
}
