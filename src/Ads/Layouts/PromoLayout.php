<?php

namespace Packitifotech\Laragdads\Ads\Layouts;

use Packitifotech\Laragdads\Ads\AdSpec;
use Packitifotech\Laragdads\Ads\Support\BookMockup;
use Packitifotech\Laragdads\Ads\Support\Canvas;
use Packitifotech\Laragdads\Ads\Support\Color;
use Packitifotech\Laragdads\Ads\Support\ImageLoader;
use Packitifotech\Laragdads\Ads\Support\RichText;
use Packitifotech\Laragdads\Ads\Support\Typography;
use Packitifotech\Laragdads\Ads\Theme;

/**
 * Website-hero style promo (wide, about 3:1): tilted 3D book with a seal on the left, a big stacked title
 * in the middle, and an info column with a plaque and call-to-action button on the right.
 * Narrower sizes fall back to the books layout with the same copy.
 */
final class PromoLayout extends AbstractLayout
{
    private const SQUEEZE = 0.8;

    public function render(Canvas $canvas, AdSpec $spec, Theme $theme, Typography $headingFont, Typography $bodyFont): void
    {
        if ($spec->aspectRatio() < 2.2 || $spec->height < 240) {
            $this->fallback($canvas, $spec, $theme, $headingFont, $bodyFont);

            return;
        }

        $w = $spec->width;
        $h = $spec->height;
        $m = (int) round($h * 0.06);
        $rich = new RichText($bodyFont, $headingFont);

        $this->footer($canvas, $spec, $w, $h);
        $this->product($canvas, $spec, $theme, $headingFont, $w, $h);

        if ($spec->logo !== null) {
            $this->logo($canvas, $spec, (int) round($m * 1.1), (int) round($m * 1.1), (int) round($w * 0.13), (int) round($h * 0.085), true);
        }

        $this->hero($canvas, $spec, $theme, $headingFont, (int) round($w * 0.372), (int) round($h * 0.07), (int) round($w * 0.262), (int) round($h * 0.73));
        $this->info($canvas, $spec, $theme, $headingFont, $rich, (int) round($w * 0.652), $m, (int) round($w * 0.33), $h);
    }

    // ------------------------------------------------------------------ left

    private function footer(Canvas $canvas, AdSpec $spec, int $w, int $h): void
    {
        if ($spec->footerLogo === null) {
            return;
        }
        $top = (int) round($h * 0.815);
        $canvas->gradient([255, 255, 255], [255, 255, 255], 'horizontal', [0, $top, (int) round($w * 0.72), $h - $top], 0.95, 0.0);
        $boxH = (int) round($h * 0.12);
        $boxW = (int) round($w * 0.2);
        $canvas->picture($this->prepare($spec->footerLogo, $spec), (int) round($w * 0.5) - intdiv($boxW, 2), $top + intdiv($h - $top - $boxH, 2), $boxW, $boxH, 'contain');
    }

    private function product(Canvas $canvas, AdSpec $spec, Theme $theme, Typography $font, int $w, int $h): void
    {
        if (!$spec->hasImages()) {
            return;
        }
        $bx = (int) round($w * 0.065);
        $by = (int) round($h * 0.15);
        $bw = (int) round($w * 0.27);
        $bh = (int) round($h * 0.72);

        $cover = $this->prepare($spec->images[0], $spec);
        if ($spec->book3d) {
            $cover = BookMockup::make($cover);
        }
        $canvas->shadow($bx + (int) ($bw * 0.12), $by + (int) ($bh * 0.9), (int) ($bw * 0.7), (int) ($h * 0.03), (int) ($h * 0.015), (int) ($h * 0.06), 0.4, 0);
        [$sw, $sh] = $canvas->sprite($cover, $bx + intdiv($bw, 2), $by + intdiv($bh, 2), $bw, $bh, 6.0);

        if ($spec->seal !== null) {
            $d = (int) round($h * 0.27);
            $this->seal($canvas, $spec, $theme, $font, $bx + intdiv($bw, 2) + (int) round($sw * 0.42), $by + intdiv($bh, 2) + (int) round($sh * 0.3), $d);
        }
    }

    /** Round embossed seal: gold rim, crimson body, stacked lines (small / BIG / small). */
    private function seal(Canvas $canvas, AdSpec $spec, Theme $theme, Typography $font, int $cx, int $cy, int $d): void
    {
        $body = $spec->seal['color'] ?? [168, 24, 32];
        $gold = $theme->gold();
        $goldDark = Color::darken($gold, 0.35);

        $canvas->shadow($cx - intdiv($d, 2), $cy - intdiv($d, 2), $d, $d, intdiv($d, 2), (int) round($d * 0.14), 0.45, (int) round($d * 0.05));
        $canvas->circle($cx, $cy, $d, $goldDark);
        $canvas->circle($cx, $cy, (int) round($d * 0.965), $gold);
        $canvas->circle($cx, $cy, (int) round($d * 0.9), Color::darken($body, 0.25));
        $canvas->circle($cx, $cy, (int) round($d * 0.87), $body);
        $canvas->glow($cx - (int) round($d * 0.12), $cy - (int) round($d * 0.16), (int) round($d * 0.3), [255, 255, 255], 0.28);
        $canvas->circle($cx, $cy, (int) round($d * 0.73), $gold, 0.55);
        $canvas->circle($cx, $cy, (int) round($d * 0.715), $body);

        $inner = (int) round($d * 0.62);
        $lines = $spec->seal['lines'];
        $scale = [0.2, 0.4, 0.17];
        $colors = [[255, 255, 255], $gold, $gold];
        $fits = [];
        $total = 0;
        foreach (array_slice($lines, 0, 3) as $i => $text) {
            $fits[$i] = $font->fit($text, $inner, (int) round($d * 0.3), (int) round($d * $scale[$i]), 8, 1.05, $i === 2 ? 2 : 1);
            $total += $fits[$i]['height'];
        }
        $y = $cy - intdiv($total, 2);
        foreach ($fits as $i => $fit) {
            $canvas->lines($fit['lines'], $font, $fit['size'], $cx - intdiv($inner, 2), $y, $inner, $colors[$i], 'center', $fit['lineHeight']);
            $y += $fit['height'];
        }
    }

    // ---------------------------------------------------------------- middle

    private function hero(Canvas $canvas, AdSpec $spec, Theme $theme, Typography $font, int $x, int $y, int $w, int $h): void
    {
        $lines = $spec->hero !== [] ? $spec->hero : array_filter([$spec->headline]);
        if ($lines === []) {
            return;
        }
        $colors = [$theme->gold(), [255, 255, 255], Color::lighten($theme->primary, 0.55)];
        $caps = [0.5, 0.34, 0.2];

        $sizes = [];
        foreach (array_values($lines) as $i => $text) {
            $natural = max(1, $font->measure($text, 100)[0]);
            $byWidth = (int) floor($w / (self::SQUEEZE * $natural / 100));
            $sizes[$i] = max(10, min($byWidth, (int) round($h * $caps[$i])));
        }
        $gap = fn (int $s) => (int) round($s * 0.08);
        $total = 0;
        foreach ($sizes as $i => $s) {
            $total += (int) round($s * 1.25) + ($i > 0 ? $gap($s) : 0);
        }
        if ($total > $h) {
            $f = $h / $total;
            $sizes = array_map(fn ($s) => max(10, (int) floor($s * $f)), $sizes);
            $total = (int) round($total * $f);
        }

        $cursor = $y + max(0, intdiv($h - $total, 2));
        foreach (array_values($lines) as $i => $text) {
            if ($i > 0) {
                $cursor += $gap($sizes[$i]);
            }
            [, $lh] = $canvas->squeezedLine($text, $font, $sizes[$i], $x, $cursor, $w, $colors[$i], self::SQUEEZE);
            $cursor += $lh;
        }
    }

    // ----------------------------------------------------------------- right

    private function info(Canvas $canvas, AdSpec $spec, Theme $theme, Typography $font, RichText $rich, int $x, int $m, int $w, int $h): void
    {
        $rowH = (int) round($h * 0.165);
        $rowY = (int) round($h * 0.765);
        $hasRow = $spec->tag !== null || $spec->cta;
        $top = $m;
        $boxH = ($hasRow ? $rowY - (int) round($m * 0.5) : $h - $m) - $top;

        $items = $spec->details;
        if ($items !== []) {
            $this->details($canvas, $theme, $rich, $items, $x, $top, $w, $boxH);
        }

        if (!$hasRow) {
            return;
        }
        $gap = (int) round($h * 0.025);
        $tagW = 0;
        if ($spec->tag !== null) {
            $tagW = (int) round($w * 0.31);
            $this->plaque($canvas, $theme, $font, $spec->tag, $x, $rowY, $tagW, $rowH);
            $tagW += $gap;
        }
        if ($spec->cta) {
            $cx = $x + $tagW;
            $cw = $w - $tagW;
            if ($spec->ctaStyle === 'arrow') {
                $this->arrowPill($canvas, $theme, $font, $spec->cta, $cx, $rowY, $cw, $rowH);
            } else {
                $this->cta($canvas, $spec, $theme, $font, $cx, $rowY + intdiv($rowH - (int) ($rowH * 0.7), 2), $cw, (int) round($rowH * 0.7), 'center');
            }
        }
    }

    /** @param array<int, string|array{0:string,1:float}> $items */
    private function details(Canvas $canvas, Theme $theme, RichText $rich, array $items, int $x, int $y, int $w, int $h): void
    {
        $norm = array_map(fn ($i) => is_array($i) ? [(string) $i[0], (float) ($i[1] ?? 1.0)] : [(string) $i, 1.0], $items);

        $measure = function (int $base) use ($norm, $rich, $w): array {
            $out = [];
            $total = 0;
            foreach ($norm as [$text, $scale]) {
                if ($text === '---') {
                    $out[] = ['divider', (int) round($base * 0.9)];
                    $total += (int) round($base * 0.9);
                    continue;
                }
                $size = max(8, (int) round($base * $scale));
                $lines = $rich->wrap($text, $size, $w);
                $lh = (int) round($size * 1.32);
                $out[] = ['text', $size, $lines, $lh];
                $total += count($lines) * $lh + (int) round($size * 0.12);
            }

            return [$out, $total];
        };

        $texts = count(array_filter($norm, fn ($n) => $n[0] !== '---'));
        $base = max(9, (int) round($h * 0.1));
        [$blocks, $total] = $measure(9);
        for (; $base >= 9; $base--) {
            [$blocks, $total] = $measure($base);
            $lineCount = array_sum(array_map(fn ($b) => $b[0] === 'text' ? count($b[2]) : 0, $blocks));
            // Fits the height and wraps at most once or twice: shrink further rather than break lines badly.
            if ($total <= $h && ($lineCount <= $texts + 1 || $base <= 12)) {
                break;
            }
        }

        $cursor = $y + max(0, intdiv($h - $total, 2));
        foreach ($blocks as $b) {
            if ($b[0] === 'divider') {
                $this->divider($canvas, $x, $cursor + intdiv($b[1], 2), $w);
                $cursor += $b[1];
                continue;
            }
            [, $size, $lines, $lh] = $b;
            $cursor = $canvas->richLines($lines, $rich, $size, $x, $cursor, $w, [255, 255, 255], $theme->gold(), 'center', $lh) + (int) round($size * 0.12);
        }
    }

    private function divider(Canvas $canvas, int $x, int $y, int $w): void
    {
        $half = intdiv($w, 2);
        $canvas->gradient([255, 255, 255], [255, 255, 255], 'horizontal', [$x + (int) ($w * 0.04), $y, $half - (int) ($w * 0.04), 1], 0.0, 0.35);
        $canvas->gradient([255, 255, 255], [255, 255, 255], 'horizontal', [$x + $half, $y, $half - (int) ($w * 0.04), 1], 0.35, 0.0);
    }

    /** Dark plaque with a gold border: big title, small caps subtitle. */
    private function plaque(Canvas $canvas, Theme $theme, Typography $font, array $tag, int $x, int $y, int $w, int $h): void
    {
        $gold = $theme->gold();
        $r = (int) round($h * 0.14);
        $b = max(2, (int) round($h * 0.035));
        $canvas->shadow($x, $y, $w, $h, $r, (int) round($h * 0.18), 0.4, (int) round($h * 0.06));
        $canvas->rect($x, $y, $w, $h, Color::darken($gold, 0.2), 1.0, $r);
        $canvas->rect($x + $b, $y + $b, $w - 2 * $b, $h - 2 * $b, Color::darken($theme->backgroundFrom, 0.45), 1.0, max(2, $r - $b));
        $canvas->gradient([255, 255, 255], [255, 255, 255], 'vertical', [$x + $b, $y + $b, $w - 2 * $b, intdiv($h, 3)], 0.12, 0.0);

        $inner = $w - (int) round($h * 0.2);
        $title = $font->fit($tag[0], $inner, (int) round($h * 0.5), (int) round($h * 0.42), 10, 1.0, 1);
        $sub = $tag[1] !== '' ? $font->fit($tag[1], $inner, (int) round($h * 0.2), (int) round($h * 0.15), 7, 1.0, 1) : null;
        $total = $title['height'] + ($sub ? $sub['height'] + (int) round($h * 0.03) : 0);
        $cy = $y + intdiv($h - $total, 2);
        $canvas->lines($title['lines'], $font, $title['size'], $x, $cy, $w, $gold, 'center', $title['lineHeight']);
        if ($sub) {
            $canvas->lines($sub['lines'], $font, $sub['size'], $x, $cy + $title['height'] + (int) round($h * 0.03), $w, [255, 255, 255], 'center', $sub['lineHeight']);
        }
    }

    /** Light pill, dark text, round arrow button on the right. */
    private function arrowPill(Canvas $canvas, Theme $theme, Typography $font, string $label, int $x, int $y, int $w, int $h): void
    {
        $ph = (int) round($h * 0.82);
        $py = $y + intdiv($h - $ph, 2);
        $r = intdiv($ph, 2);
        $canvas->shadow($x, $py, $w, $ph, $r, (int) round($ph * 0.25), 0.4, (int) round($ph * 0.08));
        $canvas->rect($x, $py, $w, $ph, [250, 246, 238], 1.0, $r);
        $canvas->gradient([255, 255, 255], [215, 205, 190], 'horizontal', [$x + $r, $py + 1, $w - 2 * $r, $ph - 2], 0.0, 0.18);

        $btn = (int) round($ph * 0.64);
        $bx = $x + $w - (int) round($ph * 0.14) - $btn;
        $dark = Color::darken($theme->backgroundFrom, 0.3);
        $canvas->circle($bx + intdiv($btn, 2), $py + intdiv($ph, 2), $btn, $dark);
        $canvas->decor(function ($layer, int $k) use ($bx, $btn, $py, $ph, $canvas) {
            imagesetthickness($layer, max(2, (int) round($btn * 0.09 * $k)));
            $c = $canvas->colorOn($layer, [255, 255, 255], 1.0);
            $cx = ($bx + intdiv($btn, 2)) * $k;
            $cy = ($py + intdiv($ph, 2)) * $k;
            $s = (int) round($btn * 0.16 * $k);
            imageline($layer, $cx - (int) ($s * 0.5), $cy - $s, $cx + (int) ($s * 0.6), $cy, $c);
            imageline($layer, $cx + (int) ($s * 0.6), $cy, $cx - (int) ($s * 0.5), $cy + $s, $c);
        });

        $textW = $bx - $x - (int) round($ph * 0.5);
        $fit = $font->fit($label, $textW, (int) round($ph * 0.78), (int) round($ph * 0.3), 8, 1.2, 2);
        $canvas->lines($fit['lines'], $font, $fit['size'], $x + (int) round($ph * 0.35), $py + intdiv($ph - $fit['height'], 2), $textW, [28, 40, 70], 'center', $fit['lineHeight']);
    }

    // -------------------------------------------------------------- fallback

    /** Narrow formats: same copy through the books layout. */
    private function fallback(Canvas $canvas, AdSpec $spec, Theme $theme, Typography $headingFont, Typography $bodyFont): void
    {
        $s = clone $spec;
        $s->headline ??= trim(implode(' ', $spec->hero));
        if ($s->features === [] && $spec->details !== []) {
            foreach ($spec->details as $d) {
                $text = is_array($d) ? $d[0] : $d;
                if ($text !== '---' && count($s->features) < 4) {
                    $s->features[] = trim(str_replace(['**', '•'], '', $text));
                }
            }
        }
        $s->badge ??= $spec->seal['lines'][1] ?? null;
        $layout = $spec->aspectRatio() >= 3.2 ? new ShowcaseLayout() : new BooksLayout();
        $layout->render($canvas, $s, $theme, $headingFont, $bodyFont);
    }
}
