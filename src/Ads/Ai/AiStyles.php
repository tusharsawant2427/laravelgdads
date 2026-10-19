<?php

namespace Packitifotech\Laragdads\Ads\Ai;

/**
 * Background art directions for the AI Horde. Every prompt asks for an empty, text-free backdrop: the
 * product, headline and buttons are drawn by the layout on top, so the model never has to spell anything.
 * "{colors}" is replaced with plain-English names of the cover's colours.
 */
final class AiStyles
{
    /** @var array<string, string> */
    private const STYLES = [
        'cinematic-bokeh' => 'cinematic abstract background, {colors} light, soft glowing particles, bokeh, depth of field, smooth gradient, professional advertising backdrop',
        'flat-geometric' => 'flat vector illustration background, overlapping geometric shapes, circles and triangles, {colors} palette, clean minimal modern poster design',
        'watercolor' => 'soft watercolor wash background, {colors} paint blooms, paper texture, delicate gradients, airy, artistic',
        'neon-cyber' => 'futuristic neon background, glowing {colors} light trails, dark backdrop, cyber grid lines, synthwave, high contrast',
        'paper-cut' => 'layered paper cut art background, smooth curved paper layers with soft shadows, {colors} tones, depth, minimal, 3d craft',
        'luxury-gold' => 'luxury dark background, flowing silk fabric and gold metallic accents, {colors} tones, elegant, premium, soft studio lighting',
        'chalkboard-doodle' => 'school chalkboard background with faint hand drawn science and maths doodles, formulas, atoms, {colors} chalk accents, education theme',
        'blueprint-science' => 'scientific blueprint background, fine technical grid, molecules, circuits and orbits line art, {colors} glow, futuristic education',
        'fluid-gradient' => 'abstract fluid gradient background, smooth flowing liquid shapes, {colors} colours, glossy, modern 3d render',
        'memphis-fun' => 'playful memphis pattern background, confetti shapes, squiggles and dots, {colors} palette, energetic, flat design',
        'space-galaxy' => 'beautiful galaxy nebula background, stars and cosmic dust, {colors} glow, deep space, dreamy, wallpaper',
        'low-poly' => 'low poly crystal facets background, faceted gradient mesh, {colors} tones, sharp light reflections, modern 3d',
    ];

    /** @return string[] */
    public static function names(): array
    {
        return array_keys(self::STYLES);
    }

    /** Pick a style: 'random' (optionally seeded) or one of names(). */
    public static function resolve(string $style, ?int $seed = null): string
    {
        if ($style === 'random') {
            $names = self::names();

            return $names[($seed ?? random_int(0, PHP_INT_MAX)) % count($names)];
        }
        if (!isset(self::STYLES[$style])) {
            throw new \InvalidArgumentException("Unknown AI style [{$style}]. Available: random, " . implode(', ', self::names()) . '.');
        }

        return $style;
    }

    /** @param string[] $colorNames */
    public static function prompt(string $style, array $colorNames): string
    {
        $colors = implode(' and ', array_unique($colorNames));

        return str_replace('{colors}', $colors, self::STYLES[$style]) . ', no text, no letters, no people, empty center space';
    }
}
