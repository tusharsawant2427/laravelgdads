<?php

namespace Packitifotech\Laragdads\Ads\Layouts;

use GdImage;
use Packitifotech\Laragdads\Ads\AdSpec;
use Packitifotech\Laragdads\Ads\Support\Canvas;
use Packitifotech\Laragdads\Ads\Support\Color;
use Packitifotech\Laragdads\Ads\Support\Cutout;
use Packitifotech\Laragdads\Ads\Support\ImageLoader;
use Packitifotech\Laragdads\Ads\Support\Typography;
use Packitifotech\Laragdads\Ads\Theme;

/**
 * Shared building blocks: the headline/sub/CTA text stack, the CTA button, the badge and the logo.
 */
abstract class AbstractLayout implements Layout
{
    /**
     * Headline + subheadline + CTA, vertically centred in the box. Text is auto-sized to fit.
     */
    protected function textColumn(
        Canvas $canvas,
        AdSpec $spec,
        Theme $theme,
        Typography $heading,
        Typography $body,
        int $x,
        int $y,
        int $w,
        int $h,
        string $align,
        array $textColor,
        bool $withCta = true
    ): void {
        if ($w < 20 || $h < 14) {
            return;
        }

        $align = $spec->textAlign ?? $align;
        $shadow = $spec->textShadow ? ['color' => [0, 0, 0], 'opacity' => 0.6] : null;
        if ($spec->textPanel) {
            $this->panel($canvas, $theme, $x, $y, $w, $h);
        }

        $gap = max(4, (int) round($h * 0.06));
        $ctaH = ($withCta && $spec->cta) ? max(26, min(88, (int) round($h * 0.2))) : 0;
        $ctaSpace = $ctaH ? $ctaH + (int) round($gap * 1.6) : 0;
        $textH = $h - $ctaSpace;

        $features = ($textH >= 210 && $spec->features) ? array_slice($spec->features, 0, 5) : [];
        $hasSub = $spec->subheadline && $textH > 70;

        $headBox = match (true) {
            $features !== [] => (int) round($textH * 0.4),
            $hasSub => (int) round($textH * 0.66),
            default => $textH,
        };
        $head = $spec->headline
            ? $heading->fit($spec->headline, $w, $headBox, (int) round(min($h * 0.46, $w * 0.22)), 11, 1.18, 4)
            : null;

        $sub = null;
        if ($hasSub && $head !== null) {
            $subBox = max(0, ($features !== [] ? (int) round($textH * 0.2) : $textH - $head['height'] - $gap));
            $sub = $subBox > 12
                ? $body->fit($spec->subheadline, $w, $subBox, max(11, (int) round($head['size'] * 0.58)), 10, 1.3, 3)
                : null;
        }

        $list = null;
        if ($features !== []) {
            $room = $textH - ($head['height'] ?? 0) - ($sub ? $sub['height'] + $gap : 0) - $gap;
            $list = $this->featureList($features, $body, $w, $room, (int) round(($head['size'] ?? 30) * 0.5));
        }

        $content = ($head['height'] ?? 0) + ($sub ? $gap + $sub['height'] : 0) + ($list ? $gap + $list['height'] : 0) + $ctaSpace;
        $cursor = $y + max(0, intdiv($h - $content, 2));

        if ($head !== null) {
            $cursor = $canvas->lines($head['lines'], $heading, $head['size'], $x, $cursor, $w, $textColor, $align, $head['lineHeight'], $shadow);
        }
        if ($sub !== null) {
            $cursor += $gap;
            $cursor = $canvas->lines($sub['lines'], $body, $sub['size'], $x, $cursor, $w, $textColor, $align, $sub['lineHeight'], $shadow, 0.92);
        }
        if ($list !== null) {
            $cursor += $gap;
            $cursor = $this->drawFeatures($canvas, $theme, $body, $list, $x, $cursor, $w, $align, $textColor);
        }
        if ($ctaH) {
            $this->cta($canvas, $spec, $theme, $heading, $x, $cursor + (int) round($gap * 1.6), $w, $ctaH, $align);
        }
    }

    /** Frosted panel behind a text block, for readability on photographic backgrounds. */
    protected function panel(Canvas $canvas, Theme $theme, int $x, int $y, int $w, int $h): void
    {
        $pad = (int) round(min($w, $h) * 0.08);
        $dark = Color::luminance($theme->text) > 0.5;
        $fill = $dark ? Color::darken($theme->primary, 0.7) : [255, 255, 255];
        $r = (int) round($pad * 1.6);

        $canvas->rect($x - $pad, $y - $pad, $w + 2 * $pad, $h + 2 * $pad, $dark ? [255, 255, 255] : [0, 0, 0], 0.12, $r);
        $canvas->rect($x - $pad + 2, $y - $pad + 2, $w + 2 * $pad - 4, $h + 2 * $pad - 4, $fill, $dark ? 0.62 : 0.7, max(2, $r - 2));
    }

    /**
     * Pick one font size at which every feature fits on a single line, then see how many lines fit the room.
     *
     * @param  string[] $features
     * @return array{items:string[],size:int,lineHeight:int,height:int,width:int}|null
     */
    private function featureList(array $features, Typography $font, int $w, int $room, int $maxSize): ?array
    {
        $maxSize = max(11, $maxSize);
        for ($size = $maxSize; $size >= 11; $size--) {
            $indent = (int) round($size * 1.7);
            $lineH = (int) round($size * 1.55);
            $count = min(count($features), intdiv($room, $lineH));
            if ($count < 1) {
                continue;
            }
            $items = array_slice($features, 0, $count);
            $widest = max(array_map(fn ($f) => $font->measure($f, $size)[0], $items));
            if ($widest + $indent <= $w) {
                return ['items' => $items, 'size' => $size, 'lineHeight' => $lineH, 'height' => $count * $lineH, 'width' => $widest + $indent];
            }
        }

        return null;
    }

    private function drawFeatures(Canvas $canvas, Theme $theme, Typography $font, array $list, int $x, int $y, int $w, string $align, array $textColor): int
    {
        $size = $list['size'];
        $indent = (int) round($size * 1.7);
        $left = match ($align) {
            'center' => $x + intdiv($w - $list['width'], 2),
            'right' => $x + $w - $list['width'],
            default => $x,
        };
        $dia = (int) round($size * 1.2);

        foreach ($list['items'] as $item) {
            $cy = $y + intdiv($list['lineHeight'], 2);
            $canvas->circle($left + intdiv($dia, 2), $cy, $dia, $theme->accent);
            $tick = '✓';
            $tw = $font->measure($tick, (int) round($size * 0.8))[0];
            $canvas->lines([$tick], $font, (int) round($size * 0.8), $left + intdiv($dia - $tw, 2), $cy - intdiv((int) round($size * 0.8 * 1.2), 2), $dia, $theme->ctaText(), 'left', (int) round($size * 0.8 * 1.2));
            $canvas->lines([$item], $font, $size, $left + $indent, $y, $w, $textColor, 'left', $list['lineHeight']);
            $y += $list['lineHeight'];
        }

        return $y;
    }

    /**
     * Pill call-to-action button. Returns its [x, y, width, height].
     *
     * @return array{0:int,1:int,2:int,3:int}
     */
    protected function cta(Canvas $canvas, AdSpec $spec, Theme $theme, Typography $font, int $x, int $y, int $maxW, int $h, string $align = 'left'): array
    {
        $size = max(10, (int) round($h * 0.4));
        $label = $font->fit($spec->cta, $maxW, $h, $size, 9, 1.0, 1);
        $width = min($maxW, $label['width'] + (int) round($h * 0.9));

        $bx = match ($align) {
            'center' => $x + intdiv($maxW - $width, 2),
            'right' => $x + $maxW - $width,
            default => $x,
        };

        if ($spec->shadows && $h >= 36) {
            $canvas->shadow($bx, $y, $width, $h, intdiv($h, 2), (int) round($h * 0.35), 0.28, (int) round($h * 0.14));
        }
        $canvas->rect($bx, $y, $width, $h, $theme->accent, 1.0, intdiv($h, 2));
        $canvas->lines(
            $label['lines'],
            $font,
            $label['size'],
            $bx,
            $y + intdiv($h - $label['lineHeight'], 2),
            $width,
            $theme->ctaText(),
            'center',
            $label['lineHeight']
        );

        return [$bx, $y, $width, $h];
    }

    /** Round "-30%" style sticker centred on ($cx, $cy). */
    protected function badge(Canvas $canvas, AdSpec $spec, Theme $theme, Typography $font, int $cx, int $cy, int $diameter): void
    {
        if (!$spec->badge || $diameter < 24) {
            return;
        }

        if ($spec->shadows) {
            $canvas->shadow($cx - intdiv($diameter, 2), $cy - intdiv($diameter, 2), $diameter, $diameter, intdiv($diameter, 2), (int) round($diameter * 0.18), 0.3, (int) round($diameter * 0.05));
        }
        // Thin lighter ring around the sticker keeps it crisp on any background.
        $canvas->circle($cx, $cy, $diameter, Color::mix($theme->accent, $theme->ctaText(), 0.45));
        $canvas->circle($cx, $cy, (int) round($diameter * 0.92), $theme->accent);

        $inner = (int) round($diameter * 0.68);
        $max = (int) round($diameter * 0.34);
        $fit = $font->fit($spec->badge, $inner, $inner, $max, 9, 1.05, 1);
        if ($fit['size'] < $max * 0.62) {
            $fit = $font->fit($spec->badge, $inner, $inner, $max, 9, 1.05, 3);
        }
        $canvas->lines(
            $fit['lines'],
            $font,
            $fit['size'],
            $cx - intdiv($inner, 2),
            $cy - intdiv($fit['height'], 2),
            $inner,
            $theme->ctaText(),
            'center',
            $fit['lineHeight']
        );
    }

    /** Logo contained in a box, with an optional light pill behind it so any logo stays legible. */
    protected function logo(Canvas $canvas, AdSpec $spec, int $x, int $y, int $w, int $h, bool $plate = false): void
    {
        if ($spec->logo === null || $w < 16 || $h < 12) {
            return;
        }

        if ($plate) {
            $pad = (int) round($h * 0.18);
            $canvas->rect($x - $pad, $y - $pad, $w + 2 * $pad, $h + 2 * $pad, [255, 255, 255], 0.92, (int) round(($h + 2 * $pad) * 0.3));
        }
        $canvas->picture($spec->logo, $x, $y, $w, $h, 'contain');
    }

    /** Strip the white packshot background (unless disabled or the image already has transparency). */
    protected function prepare(GdImage|string $image, AdSpec $spec): GdImage
    {
        $img = ImageLoader::load($image);
        $wanted = $spec->cutout ?? Cutout::hasLightBackground($img);

        return $wanted ? Cutout::apply($img) : $img;
    }

    /** A studio shot on a flat white background reads best on a white card. */
    protected function isPackshot(mixed $image): bool
    {
        try {
            return Cutout::hasLightBackground(ImageLoader::load($image));
        } catch (\Throwable) {
            return false;
        }
    }

    protected function margin(AdSpec $spec): int
    {
        return max(6, (int) round(min($spec->width, $spec->height) * 0.06));
    }

    /** Image card: shadow + rounded cover/contain picture. */
    protected function card(Canvas $canvas, AdSpec $spec, mixed $image, int $x, int $y, int $w, int $h, int $radius): void
    {
        if ($spec->shadows && $spec->imageFit !== 'contain' && $w > 60 && $h > 60) {
            $canvas->shadow($x, $y, $w, $h, $radius, (int) round(min($w, $h) * 0.1), 0.35, (int) round(min($w, $h) * 0.04));
        }
        if ($spec->imageFit === 'contain') {
            // Product cut-outs float on a frosted plate instead of the bare background.
            $canvas->rect($x, $y, $w, $h, [255, 255, 255], $this->isPackshot($image) ? 1.0 : 0.16, $radius);
        }
        $canvas->picture($image, $x, $y, $w, $h, $spec->imageFit, $radius, [0.5, 0.5], (int) round(min($w, $h) * ($spec->imageFit === 'contain' ? 0.06 : 0)));
    }
}
