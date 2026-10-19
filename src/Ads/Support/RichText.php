<?php

namespace Packitifotech\Laragdads\Ads\Support;

/**
 * Wrapping and fitting for lines that mix regular and **bold** words, e.g.
 * "Model Papers for **SSC Board Exam**". Bold words are drawn in the accent colour.
 */
final class RichText
{
    public function __construct(
        public readonly Typography $regular,
        public readonly Typography $bold
    ) {
    }

    /**
     * @return array<int, array<int, array{0:string,1:bool}>> lines of [word, isBold]
     */
    public function wrap(string $markup, int $size, int $maxWidth): array
    {
        $lines = [];
        foreach (preg_split('/\R/u', trim($markup)) as $paragraph) {
            $line = [];
            foreach ($this->words($paragraph) as $word) {
                $candidate = [...$line, $word];
                if ($line !== [] && $this->width($candidate, $size) > $maxWidth) {
                    $lines[] = $line;
                    $line = [$word];
                } else {
                    $line = $candidate;
                }
            }
            $lines[] = $line;
        }

        return $lines;
    }

    /** @param array<int, array{0:string,1:bool}> $line */
    public function width(array $line, int $size): int
    {
        $space = $this->regular->measure(' ', $size)[0];
        $w = 0;
        foreach ($line as $i => [$word, $isBold]) {
            $w += ($isBold ? $this->bold : $this->regular)->measure($word, $size)[0] + ($i > 0 ? $space : 0);
        }

        return $w;
    }

    /**
     * Largest size at which the markup wraps inside the box.
     *
     * @return array{size:int,lines:array,height:int,lineHeight:int}
     */
    public function fit(string $markup, int $boxW, int $boxH, int $maxSize, int $minSize = 9, float $lineHeight = 1.3): array
    {
        $best = null;
        for ($size = $maxSize; $size >= $minSize; $size--) {
            $lines = $this->wrap($markup, $size, $boxW);
            $lh = (int) round($size * $lineHeight);
            $widest = max(array_map(fn ($l) => $this->width($l, $size), $lines));
            $best = ['size' => $size, 'lines' => $lines, 'height' => count($lines) * $lh, 'lineHeight' => $lh];
            if ($best['height'] <= $boxH && $widest <= $boxW) {
                return $best;
            }
        }

        return $best;
    }

    /** @return array<int, array{0:string,1:bool}> */
    private function words(string $paragraph): array
    {
        $out = [];
        $bold = false;
        foreach (preg_split('/(\*\*)/u', $paragraph, -1, PREG_SPLIT_DELIM_CAPTURE) as $part) {
            if ($part === '**') {
                $bold = !$bold;
                continue;
            }
            foreach (preg_split('/\s+/u', $part, -1, PREG_SPLIT_NO_EMPTY) as $word) {
                $out[] = [$word, $bold];
            }
        }

        return $out;
    }
}
