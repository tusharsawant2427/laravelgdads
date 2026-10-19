<?php

namespace Packitifotech\Laragdads\Ads\Support;

/**
 * Text measuring, wrapping and auto-fitting for a TrueType/OpenType font.
 *
 * All sizes in this class are pixels. GD renders at 96 dpi and takes points,
 * so sizes are converted with PX_TO_PT before reaching FreeType.
 */
final class Typography
{
    public const PX_TO_PT = 0.75;

    /** @var array<string, array{0:int,1:int}> */
    private array $cache = [];

    public function __construct(public readonly string $fontPath)
    {
        if (!is_file($fontPath) || !is_readable($fontPath)) {
            throw new \InvalidArgumentException("Font file not found or unreadable: [{$fontPath}].");
        }
    }

    /** @return array{0:int,1:int} width, height in px */
    public function measure(string $text, int $sizePx): array
    {
        $key = $sizePx . '|' . $text;
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $box = imagettfbbox($sizePx * self::PX_TO_PT, 0, $this->fontPath, $text);
        if ($box === false) {
            throw new \RuntimeException("Could not measure text with font [{$this->fontPath}].");
        }

        return $this->cache[$key] = [$box[2] - $box[0], $box[1] - $box[7]];
    }

    /** Distance from the top of a line box to the baseline of capital letters. */
    public function ascent(int $sizePx): int
    {
        $box = imagettfbbox($sizePx * self::PX_TO_PT, 0, $this->fontPath, 'H');

        return (int) -$box[7];
    }

    /** @return string[] */
    public function wrap(string $text, int $sizePx, int $maxWidth): array
    {
        $lines = [];
        foreach (preg_split('/\R/u', trim($text)) as $paragraph) {
            $current = '';
            foreach (preg_split('/\s+/u', trim($paragraph), -1, PREG_SPLIT_NO_EMPTY) as $word) {
                $candidate = $current === '' ? $word : $current . ' ' . $word;
                if ($current !== '' && $this->measure($candidate, $sizePx)[0] > $maxWidth) {
                    $lines[] = $current;
                    $current = $word;
                } else {
                    $current = $candidate;
                }
            }
            $lines[] = $current;
        }

        return $lines;
    }

    /**
     * Find the largest font size (px) at which $text wraps inside the box.
     * If even $minSize does not fit, lines are cut and ellipsised.
     *
     * @return array{size:int,lines:string[],width:int,height:int,lineHeight:int}
     */
    public function fit(
        string $text,
        int $boxWidth,
        int $boxHeight,
        int $maxSize,
        int $minSize = 10,
        float $lineHeight = 1.2,
        ?int $maxLines = null
    ): array {
        $maxSize = max($maxSize, $minSize);
        $lo = $minSize;
        $hi = $maxSize;
        $best = null;

        while ($lo <= $hi) {
            $mid = intdiv($lo + $hi, 2);
            $try = $this->layout($text, $mid, $boxWidth, $lineHeight);
            $fits = $try['height'] <= $boxHeight
                && $try['width'] <= $boxWidth
                && ($maxLines === null || count($try['lines']) <= $maxLines);

            if ($fits) {
                $best = $try;
                $lo = $mid + 1;
            } else {
                $hi = $mid - 1;
            }
        }

        if ($best !== null) {
            return $best;
        }

        // Nothing fits: clamp to the minimum size and ellipsise.
        $try = $this->layout($text, $minSize, $boxWidth, $lineHeight);
        $limit = max(1, min(
            $maxLines ?? PHP_INT_MAX,
            (int) floor($boxHeight / max(1, $try['lineHeight']))
        ));

        if (count($try['lines']) > $limit) {
            $try['lines'] = array_slice($try['lines'], 0, $limit);
            $try['lines'][$limit - 1] = $this->ellipsize($try['lines'][$limit - 1], $minSize, $boxWidth);
        }
        $try['height'] = count($try['lines']) * $try['lineHeight'];

        return $try;
    }

    /** @return array{size:int,lines:string[],width:int,height:int,lineHeight:int} */
    private function layout(string $text, int $size, int $boxWidth, float $lineHeight): array
    {
        $lines = $this->wrap($text, $size, $boxWidth);
        $width = 0;
        foreach ($lines as $line) {
            $width = max($width, $this->measure($line, $size)[0]);
        }
        $lh = (int) round($size * $lineHeight);

        return [
            'size' => $size,
            'lines' => $lines,
            'width' => $width,
            'height' => count($lines) * $lh,
            'lineHeight' => $lh,
        ];
    }

    private function ellipsize(string $line, int $size, int $maxWidth): string
    {
        $line = rtrim($line);
        while ($line !== '' && $this->measure($line . '…', $size)[0] > $maxWidth) {
            $line = mb_substr($line, 0, -1);
        }

        return rtrim($line) . '…';
    }
}
