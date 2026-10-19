<?php

namespace Packitifotech\Laragdads\Ads\Ai;

/**
 * Background art directions for the AI Horde. Every prompt asks for an empty, text-free backdrop: the
 * product, headline and buttons are drawn by the layout on top, so the model never has to spell anything.
 *
 * Two families:
 *  - education scenes (classroom, exam hall, physics / chemistry / biology lab, study desk, ...), picked from the
 *    ad's own wording ("MHT-CET Physics" gets a physics scene, "SSC board papers" an exam hall)
 *  - abstract art (bokeh, neon, watercolor, ...)
 *
 * "{colors}" is replaced with plain-English names of the cover's colours.
 */
final class AiStyles
{
    private const SUFFIX = 'photorealistic, soft cinematic lighting, shallow depth of field, blurred background, empty space in the middle, no text, no letters, no logo, no people';

    /** @var array<string, string> */
    private const EDUCATION = [
        'classroom' => 'empty bright school classroom with wooden desks and a green chalkboard, sunlight through the windows, {colors} color grading',
        'exam-hall' => 'rows of empty examination desks and chairs in a large school hall, symmetrical perspective, soft daylight, {colors} color grading',
        'study-desk' => 'top view flat lay of a tidy study desk with open notebooks, pencils, a calculator, glasses and a coffee cup, {colors} color grading',
        'library' => 'cozy school library with tall wooden bookshelves full of books, warm light, bokeh, {colors} color grading',
        'science-lab' => 'school science laboratory bench with glass beakers and flasks, glowing bokeh lights, {colors} color grading',
        'physics-concept' => 'physics concept background, glowing atom orbits, electric circuit lines, lens and prism with light rays, dark gradient, {colors} glow, educational illustration',
        'chemistry-lab' => 'chemistry laboratory with colourful liquids in flasks and test tubes, molecular structures glowing, bokeh, {colors} color grading',
        'maths-geometry' => 'mathematics background with compass, ruler, protractor and geometric shapes on graph paper, chalk sketch lines, {colors} color grading',
        'biology-lab' => 'biology background with green leaves, a microscope, cells and a DNA helix, fresh light, {colors} color grading',
        'notebook-desk' => 'close-up of an open notebook with pencil sketches and graph paper on a wooden desk, a pencil and eraser, {colors} color grading',
        'graduation' => 'graduation caps thrown in the air with golden confetti and bokeh, success celebration, {colors} color grading',
        'books-stack' => 'stack of books with pencils and a green apple on a wooden desk by a window, {colors} color grading',
    ];

    /** @var array<string, string> */
    private const ART = [
        'cinematic-bokeh' => 'cinematic abstract background, {colors} light, soft glowing particles, bokeh, smooth gradient, professional advertising backdrop',
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

    /** keyword in the ad copy => education scenes that fit it (first match with most specific subject wins) */
    private const TOPICS = [
        'physics' => ['physics-concept', 'science-lab'],
        'chemistry' => ['chemistry-lab', 'science-lab'],
        'biology' => ['biology-lab', 'library'],
        'botany' => ['biology-lab'],
        'zoology' => ['biology-lab'],
        'math' => ['maths-geometry', 'notebook-desk'],
        'algebra' => ['maths-geometry'],
        'geometry' => ['maths-geometry'],
        'science' => ['science-lab', 'physics-concept'],
        'english' => ['library', 'books-stack'],
        'hindi' => ['library', 'books-stack'],
        'marathi' => ['library', 'books-stack'],
        'history' => ['library', 'classroom'],
        'geography' => ['library', 'classroom'],
        'neet' => ['biology-lab', 'study-desk'],
        'jee' => ['physics-concept', 'maths-geometry'],
        'cet' => ['study-desk', 'exam-hall'],
        'ssc' => ['exam-hall', 'classroom', 'study-desk'],
        'hsc' => ['exam-hall', 'classroom'],
        'board' => ['exam-hall', 'classroom'],
        'exam' => ['exam-hall', 'study-desk'],
        'question paper' => ['exam-hall', 'study-desk'],
        'mcq' => ['study-desk', 'notebook-desk'],
        'notes' => ['notebook-desk', 'study-desk'],
        'topper' => ['graduation', 'study-desk'],
        'success' => ['graduation'],
    ];

    /** @return string[] every style name */
    public static function names(): array
    {
        return array_merge(self::educationNames(), self::artNames());
    }

    /** @return string[] */
    public static function educationNames(): array
    {
        return array_keys(self::EDUCATION);
    }

    /** @return string[] */
    public static function artNames(): array
    {
        return array_keys(self::ART);
    }

    /**
     * Resolve a requested style to a concrete one.
     *   'education' | 'auto'  scene that fits $topic (the ad copy), else a random education scene
     *   'random'              a random education scene
     *   'abstract'            a random abstract-art style
     *   <name>                exactly that style
     */
    public static function resolve(string $style, ?int $seed = null, ?string $topic = null): string
    {
        $pick = fn (array $names) => $names[($seed ?? random_int(0, PHP_INT_MAX)) % count($names)];

        return match (true) {
            $style === 'education' || $style === 'auto' => self::forTopic($topic ?? '', $seed),
            $style === 'random' => $pick(self::educationNames()),
            $style === 'abstract' => $pick(self::artNames()),
            isset(self::EDUCATION[$style]) || isset(self::ART[$style]) => $style,
            default => throw new \InvalidArgumentException(
                "Unknown AI style [{$style}]. Use education, random, abstract or one of: " . implode(', ', self::names()) . '.'
            ),
        };
    }

    /** The education scene that fits the ad copy: subject keywords first (physics, chemistry...), then exam words. */
    public static function forTopic(string $text, ?int $seed = null): string
    {
        $text = mb_strtolower($text);
        $candidates = [];
        foreach (self::TOPICS as $keyword => $scenes) {
            if (str_contains($text, $keyword)) {
                $candidates = array_merge($candidates, $scenes);
                if (count($candidates) >= 2 && in_array($keyword, ['physics', 'chemistry', 'biology', 'math'], true)) {
                    break;   // a clear subject decides the scene
                }
            }
        }
        $candidates = array_values(array_unique($candidates ?: self::educationNames()));

        return $candidates[($seed ?? random_int(0, PHP_INT_MAX)) % count($candidates)];
    }

    /** @param string[] $colorNames */
    public static function prompt(string $style, array $colorNames): string
    {
        $colors = implode(' and ', array_unique($colorNames));
        $template = self::EDUCATION[$style] ?? self::ART[$style];
        $isScene = isset(self::EDUCATION[$style]);

        return str_replace('{colors}', $colors, $template) . ', ' . ($isScene ? self::SUFFIX : 'no text, no letters, no people, empty center space');
    }
}
