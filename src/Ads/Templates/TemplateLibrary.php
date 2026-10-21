<?php

namespace Packitifotech\Laragdads\Ads\Templates;

use Packitifotech\Laragdads\Ads\Support\Canvas;
use Packitifotech\Laragdads\Ads\Support\Color;
use Packitifotech\Laragdads\Ads\Support\Icons;
use Packitifotech\Laragdads\Ads\Support\Typography;

/**
 * Ready-made light banner backgrounds with illustrated edges and an open middle for the product and copy.
 * Each template is a recipe that draws onto a Canvas of any size: decorations hug the left and right edges and are sized
 * from the canvas height, so the same recipe works for 1200x200 strips and 2000x256 banners alike.
 *
 *   TemplateLibrary::paint('lavender-books', $canvas);
 *   TemplateLibrary::names();            // all template names
 *   TemplateLibrary::palette('sky-atom') // ['ink' => [r,g,b], 'accent' => [r,g,b]] for text and buttons on it
 */
final class TemplateLibrary
{
    /** Width / height from which the full illustration is drawn; below it the compact variant. */
    private const WIDE = 7.0;

    /** @var array<string, array{ink:string,accent:string,zone:array{0:float,1:float}}> text colour, button colour and the free middle
     *      zone (left and right decoration widths, in units of the canvas height) per template */
private const PALETTE = [
        'sky-atom' => ['ink' => '#1e3a6e', 'accent' => '#1d5fd8', 'zone' => [1.4, 1.7], 'compact' => [1.4, 1.15]],
        'peach-leaves' => ['ink' => '#4a2e1c', 'accent' => '#e8742c', 'zone' => [1.55, 1.2], 'compact' => [1.55, 1.2]],
        'lavender-bubbles' => ['ink' => '#3b2470', 'accent' => '#7c4dd8', 'zone' => [1.35, 0.8], 'compact' => [1.35, 0.7]],
        'aqua-hex' => ['ink' => '#0f4a66', 'accent' => '#0e9ac0', 'zone' => [0.1, 0.1], 'compact' => [0.1, 0.1]],
        'yellow-brush' => ['ink' => '#3d3020', 'accent' => '#e9a800', 'zone' => [0.1, 0.1], 'compact' => [0.1, 0.1]],
        'lavender-books' => ['ink' => '#2f2a70', 'accent' => '#5b4fd8', 'zone' => [0.1, 1.05], 'compact' => [0.1, 1.05]],
        'doodle-grid' => ['ink' => '#2b2a6b', 'accent' => '#2f6ce0', 'zone' => [2.15, 2.45], 'compact' => [0.85, 1.4]],
        'coral-waves' => ['ink' => '#5a2a3a', 'accent' => '#e8566f', 'zone' => [0.1, 1.05], 'compact' => [0.1, 0.85]],
        'green-leaves' => ['ink' => '#1f4a28', 'accent' => '#3f9a3e', 'zone' => [1.2, 1.2], 'compact' => [1.2, 1.2]],
        'blue-waves-atom' => ['ink' => '#173a72', 'accent' => '#2a74e0', 'zone' => [0.1, 1.35], 'compact' => [0.1, 1.35]],
        'books-emc2' => ['ink' => '#2b2a6b', 'accent' => '#e9a000', 'zone' => [1.2, 1.95], 'compact' => [1.2, 1.15]],
        'lavender-notebook' => ['ink' => '#33276e', 'accent' => '#7a4fd6', 'zone' => [0.1, 2.2], 'compact' => [0.1, 1.4]],
        'cradle-blue' => ['ink' => '#1c3a6a', 'accent' => '#2f7be0', 'zone' => [1.4, 1.6], 'compact' => [1.4, 0.85]],
        'peach-physics' => ['ink' => '#46301c', 'accent' => '#e57a2a', 'zone' => [1.8, 1.35], 'compact' => [1.15, 1.35]],
        'magnet-purple' => ['ink' => '#37236e', 'accent' => '#7d4bd6', 'zone' => [1.45, 1.15], 'compact' => [1.45, 1.15]],
        'green-graph' => ['ink' => '#1c4a30', 'accent' => '#2c9a5c', 'zone' => [1.55, 1.25], 'compact' => [1.55, 1.25]],
        'bulb-leaves' => ['ink' => '#4a2e1a', 'accent' => '#e7791f', 'zone' => [1.85, 0.95], 'compact' => [1.85, 0.95]],
        'aqua-atom' => ['ink' => '#123c6c', 'accent' => '#2c86d8', 'zone' => [0.1, 1.35], 'compact' => [0.1, 1.35]],
        'prism-rainbow' => ['ink' => '#2b2f78', 'accent' => '#5a5fe0', 'zone' => [2.1, 1.75], 'compact' => [1.7, 1.2]],
        'yellow-projectile' => ['ink' => '#3d3018', 'accent' => '#e8a200', 'zone' => [0.1, 2], 'compact' => [0.1, 1.2]],
        'mint-chemistry' => ['ink' => '#0f4d45', 'accent' => '#0e9a82', 'zone' => [1.55, 1.8], 'compact' => [1, 1]],
        'sunrise-maths' => ['ink' => '#5a2a1c', 'accent' => '#ee6a2a', 'zone' => [2.25, 2.05], 'compact' => [1.65, 1.1]],
        'forest-biology' => ['ink' => '#1f4a28', 'accent' => '#3f9a3e', 'zone' => [1.85, 2.1], 'compact' => [1.15, 1.25]],
        'ocean-globe' => ['ink' => '#0f4a5c', 'accent' => '#0e92b0', 'zone' => [1.95, 1.95], 'compact' => [1.05, 0.8]],
        'navy-success' => ['ink' => '#ffffff', 'accent' => '#f6b823', 'zone' => [1, 1.35], 'compact' => [1.05, 1.35]],
        'cream-stationery' => ['ink' => '#4a3520', 'accent' => '#e08a2a', 'zone' => [1.75, 1.5], 'compact' => [0.95, 1.1]],
        'rose-celebration' => ['ink' => '#5a2040', 'accent' => '#e0457a', 'zone' => [0.95, 1.55], 'compact' => [1, 1.55]],
        'blueprint-dark' => ['ink' => '#ffffff', 'accent' => '#ffd24a', 'zone' => [2.4, 1.9], 'compact' => [1.15, 0.95]],
        'sunny-rocket' => ['ink' => '#1c4a78', 'accent' => '#f08a1e', 'zone' => [1.3, 2.15], 'compact' => [0.95, 1]],
        'mint-graph-paper' => ['ink' => '#1c4a38', 'accent' => '#2ca066', 'zone' => [1.25, 1.55], 'compact' => [1.25, 0.55]],
        'teal-exam-clock' => ['ink' => '#0f4a4a', 'accent' => '#16a39a', 'zone' => [1.75, 1.6], 'compact' => [1.15, 1]],
        'plum-premium' => ['ink' => '#ffffff', 'accent' => '#f4c040', 'zone' => [1.5, 2.7], 'compact' => [1.1, 1.85]],
        'amber-chemistry' => ['ink' => '#4a2a10', 'accent' => '#e8742c', 'zone' => [1.7, 1.8], 'compact' => [0.95, 0.95]],
        'chalk-calculus' => ['ink' => '#ffffff', 'accent' => '#f6c13a', 'zone' => [2, 1.75], 'compact' => [1.15, 0.6]],
        'violet-algebra' => ['ink' => '#2f2470', 'accent' => '#7a4fd6', 'zone' => [2.35, 1.7], 'compact' => [1.35, 1]],
        'ink-letters' => ['ink' => '#2a1f3a', 'accent' => '#e8586a', 'zone' => [2.35, 1.95], 'compact' => [0.95, 0.85]],
        'rose-bubbles' => ['ink' => '#5a2040', 'accent' => '#d94a8a', 'zone' => [1.55, 1.35], 'compact' => [1.55, 1]],
        'parchment-history' => ['ink' => '#4a3018', 'accent' => '#b4562a', 'zone' => [1.35, 1.85], 'compact' => [1.35, 1.45]],
        'sepia-pillars' => ['ink' => '#4a3420', 'accent' => '#c4782a', 'zone' => [1.25, 1.95], 'compact' => [1.25, 1.4]],
        'terrain-explorer' => ['ink' => '#1c4a50', 'accent' => '#e0762a', 'zone' => [1.95, 2.3], 'compact' => [1.1, 0.65]],
        'scales-justice' => ['ink' => '#ffffff', 'accent' => '#f4c040', 'zone' => [1.9, 1.15], 'compact' => [0.6, 1.15]],
        'gold-finance' => ['ink' => '#3d3014', 'accent' => '#d99a10', 'zone' => [1.25, 1.95], 'compact' => [1.25, 1.05]],
        'green-ledger' => ['ink' => '#1c4a30', 'accent' => '#2c9a5c', 'zone' => [1.45, 2.05], 'compact' => [1.45, 1.15]],
        'circuit-tech' => ['ink' => '#ffffff', 'accent' => '#3ad0b8', 'zone' => [1, 1.45], 'compact' => [1, 1.45]],
        'code-screen' => ['ink' => '#1c3a6a', 'accent' => '#2f7be0', 'zone' => [2.15, 2.1], 'compact' => [0.75, 1.05]],
        'brain-calm' => ['ink' => '#3b2470', 'accent' => '#8a5be0', 'zone' => [1.7, 1.85], 'compact' => [1.25, 1]],
        'palette-splash' => ['ink' => '#3a2a4a', 'accent' => '#e8586a', 'zone' => [1.4, 2], 'compact' => [1.4, 1.45]],
        'crayon-bright' => ['ink' => '#3a3a4a', 'accent' => '#3a9ae0', 'zone' => [1.2, 1.2], 'compact' => [1.2, 1.2]],
        'puzzle-bulb' => ['ink' => '#2a2a6b', 'accent' => '#f6a41a', 'zone' => [1.8, 2.05], 'compact' => [1.25, 1.1]],
        'sports-field' => ['ink' => '#143a1c', 'accent' => '#e8742c', 'zone' => [1.75, 2.15], 'compact' => [1.05, 1.2]],
        'defence-shield' => ['ink' => '#ffffff', 'accent' => '#f4c040', 'zone' => [1.1, 1.45], 'compact' => [1.1, 1.5]],
        'water-green' => ['ink' => '#0f4a50', 'accent' => '#1a9a8a', 'zone' => [2.15, 1.8], 'compact' => [0.9, 0.95]],
        'earth-clean' => ['ink' => '#1f4a3a', 'accent' => '#3f9a6a', 'zone' => [2.15, 2.15], 'compact' => [1, 0.9]],
    ];


    /**
     * Theme groups of each template (see Subjects::GROUPS). A template can serve several groups; 'general' is the pool for
     * multi-subject books and unknown subjects.
     *
     * @var array<string, string[]>
     */
    private const TAGS = [
        'sky-atom' => ['physics'], 'blue-waves-atom' => ['physics'], 'aqua-atom' => ['physics'], 'cradle-blue' => ['physics'],
        'magnet-purple' => ['physics'], 'prism-rainbow' => ['physics'], 'peach-physics' => ['physics'], 'doodle-grid' => ['physics', 'maths'],
        'yellow-projectile' => ['physics', 'maths'], 'blueprint-dark' => ['physics', 'computer'], 'books-emc2' => ['physics', 'general'],
        'mint-chemistry' => ['chemistry'], 'aqua-hex' => ['general', 'chemistry'], 'amber-chemistry' => ['chemistry'],
        'forest-biology' => ['biology'], 'green-leaves' => ['biology', 'environment'], 'peach-leaves' => ['general', 'biology', 'languages'],
        'bulb-leaves' => ['biology', 'reasoning'],
        'sunrise-maths' => ['maths'], 'mint-graph-paper' => ['maths', 'maths-advanced'], 'green-graph' => ['maths'],
        'chalk-calculus' => ['maths-advanced'], 'violet-algebra' => ['maths-advanced'],
        'ink-letters' => ['languages'], 'rose-bubbles' => ['languages'], 'lavender-books' => ['general', 'languages'],
        'cream-stationery' => ['general', 'languages'], 'lavender-notebook' => ['general', 'languages'],
        'parchment-history' => ['history'], 'sepia-pillars' => ['history', 'civics'],
        'ocean-globe' => ['geography'], 'terrain-explorer' => ['geography'],
        'scales-justice' => ['civics'],
        'gold-finance' => ['commerce'], 'green-ledger' => ['commerce'],
        'circuit-tech' => ['computer'], 'code-screen' => ['computer'],
        'brain-calm' => ['psychology'], 'lavender-bubbles' => ['general', 'psychology'],
        'palette-splash' => ['art'], 'crayon-bright' => ['art'], 'yellow-brush' => ['general', 'art'],
        'puzzle-bulb' => ['reasoning'], 'teal-exam-clock' => ['general', 'reasoning'],
        'sports-field' => ['sports'], 'defence-shield' => ['sports'],
        'water-green' => ['environment'], 'earth-clean' => ['environment'],
        'coral-waves' => ['general'], 'navy-success' => ['general'], 'rose-celebration' => ['general'],
        'plum-premium' => ['general'], 'sunny-rocket' => ['general'],
    ];

    /** @return string[] */
    public static function names(): array
    {
        return array_keys(self::PALETTE);
    }

    /**
     * Free zone for content in pixels: [left edge, right edge] for a canvas of this size. Narrower strips (under 7:1)
     * use the template's compact variant, which leaves out the secondary doodles.
     *
     * @return array{0:int,1:int}
     */
    public static function zone(string $name, int $w, int $h, int $reserveRight = 0): array
    {
        $w -= max(0, $reserveRight);
        $p = self::PALETTE[$name] ?? throw new \InvalidArgumentException("Unknown template [{$name}].");
        [$l, $r] = ($w / max(1, $h) < self::WIDE && isset($p['compact'])) ? $p['compact'] : $p['zone'];

        return [(int) round($h * $l), $w - (int) round($h * $r)];
    }



    /** Drawing helpers that make backgrounds and patterns, not illustrations: text may run over these. */
    private const DECOR = ['wave', 'ribbon', 'swipes', 'dots', 'hexes', 'grid', 'facets', 'blob', 'glow', 'sparkle', 'circuit', 'wash'];

    /**
     * Measure where the template's illustrations are: the right edge of the artwork on the left half and the left edge of the
     * artwork on the right half, as a share of the canvas height (so it can be compared with the declared zone). Backgrounds,
     * waves and patterns do not count, only props, symbols and formulas.
     *
     * @param  int $reserveRight width of the button slot, as passed to paint()
     * @return array{left:float,right:float} left = free space starts after this many heights from the left edge,
     *                                       right = free space ends this many heights before the artwork-free right edge
     */
    public static function artwork(string $name, int $width, int $height, int $reserveRight = 0): array
    {
        $effective = $width - max(0, $reserveRight);
        $boxes = [];
        Canvas::observe(function (string $kind, int $x, int $y, int $w, int $h) use (&$boxes) {
            foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 8) as $frame) {
                if (($frame['class'] ?? '') === Canvas::class || str_contains((string) ($frame['function'] ?? ''), '{closure')) {
                    continue;
                }
                if (in_array($frame['function'] ?? '', self::DECOR, true)) {
                    return;   // background / pattern helper
                }
                break;
            }
            $boxes[] = [$x, $y, $w, $h];
        });
        try {
            $canvas = Canvas::create($width, $height, [255, 255, 255]);
            self::paint($name, $canvas, null, $reserveRight);
        } finally {
            Canvas::observe(null);
        }

        $left = 0;
        $right = $effective;
        foreach ($boxes as [$x, $y, $w, $h]) {
            $x0 = max(0, $x);
            $x1 = min($effective, $x + $w);
            if ($x1 <= $x0 || $w >= $effective * 0.6) {
                continue;   // off canvas, or a whole-width pattern
            }
            if (($x0 + $x1) / 2 < $effective / 2) {
                $left = max($left, $x1);
            } else {
                $right = min($right, $x0);
            }
        }

        return ['left' => $left / $height, 'right' => ($effective - $right) / $height];
    }

    /**
     * The free zone a template needs, measured from its own drawing: it starts after the left artwork, ends before the right
     * artwork, and only spans columns where the template's ink colour is readable (WCAG 4.5:1) on the painted background.
     * Used to generate the PALETTE table and by the tests to check it stays correct when a recipe changes.
     *
     * @param  float $productWidth width of the product block that sits at the start of the zone, in heights (see TemplateLayout)
     * @return array{left:float,right:float} the same units as the PALETTE 'zone' entries
     */
    public static function measureZone(string $name, int $width, int $height, int $reserveRight, float $productWidth = 0.8): array
    {
        $art = self::artwork($name, $width, $height, $reserveRight);
        $effective = $width - $reserveRight;
        $canvas = Canvas::create($width, $height, [255, 255, 255]);
        self::paint($name, $canvas, null, $reserveRight);
        $image = $canvas->image();
        $ink = self::palette($name)['ink'];

        $readable = function (int $x) use ($image, $ink, $height): bool {
            foreach ([0.3, 0.5, 0.7] as $fy) {
                $c = imagecolorat($image, $x, (int) ($height * $fy));
                if (Color::contrast($ink, [($c >> 16) & 255, ($c >> 8) & 255, $c & 255]) < 4.5) {
                    return false;
                }
            }

            return true;
        };

        $limit = (int) floor($effective - $art['right'] * $height);
        $textStart = (int) ceil($art['left'] * $height + $productWidth * $height);
        while ($textStart < $limit && !$readable($textStart)) {
            $textStart += 4;
        }
        $end = $textStart;
        while ($end < $limit && $readable($end)) {
            $end += 4;
        }

        return ['left' => ($textStart - $productWidth * $height) / $height, 'right' => ($effective - $end) / $height];
    }

    /** @return string[] template names tagged with the theme group (see Subjects::GROUPS) */
    public static function forGroup(string $group): array
    {
        if (!in_array($group, Subjects::GROUPS, true)) {
            throw new \InvalidArgumentException("Unknown group [{$group}]. Available: " . implode(', ', Subjects::GROUPS) . '.');
        }

        $names = array_values(array_filter(array_keys(self::PALETTE), fn ($n) => in_array($group, self::TAGS[$n] ?? [], true)));

        // Templates made for this group first (it is their first tag), borrowed ones after: the default pick is the closest match.
        usort($names, fn ($a, $b) => array_search($group, self::TAGS[$a]) <=> array_search($group, self::TAGS[$b]));

        return $names;
    }

    /** @return array<string, string[]> group => template names */
    public static function byGroup(): array
    {
        $out = [];
        foreach (Subjects::GROUPS as $g) {
            $out[$g] = self::forGroup($g);
        }

        return $out;
    }

    /** @return string[] every template that suits the subject label ("Physics", "PCM", "Eng/Mar/His" ...) */
    public static function forSubject(string $label): array
    {
        $names = [];
        foreach (Subjects::pool($label) as $group) {
            foreach (self::forGroup($group) as $n) {
                $names[$n] = true;
            }
        }

        return array_keys($names);
    }

    /** One template for the subject; the same $seed always picks the same one, different seeds rotate through the options. */
    public static function pickFor(string $label, int $seed = 0): string
    {
        $names = self::forSubject($label) ?: self::forGroup('general');

        return $names[abs($seed) % count($names)];
    }

    /** @return array{ink:array,accent:array,zone:array{0:float,1:float}} */
    public static function palette(string $name): array
    {
        $p = self::PALETTE[$name] ?? throw new \InvalidArgumentException("Unknown template [{$name}]. Available: " . implode(', ', self::names()) . '.');

        return ['ink' => Color::fromHex($p['ink']), 'accent' => Color::fromHex($p['accent']), 'zone' => $p['zone']];
    }

    /**
     * @param int $reserveRight pixels at the right edge left free of illustration (the fixed button slot): the artwork is
     *                          laid out as if the canvas were that much narrower, the background still fills the slot
     */
    public static function paint(string $name, Canvas $c, ?Typography $script = null, int $reserveRight = 0): void
    {
        self::palette($name);   // validates the name
        $w = $c->width() - max(0, $reserveRight);
        $h = $c->height();
        $script ??= new Typography(dirname(__DIR__, 3) . '/resources/fonts/LiberationSerif-BoldItalic.ttf');
        $method = 't' . str_replace('-', '', ucwords($name, '-'));
        self::$method($c, $w, $h, $script, $w / max(1, $h) >= self::WIDE);
    }

    private static function hex(string $hex): array
    {
        return Color::fromHex($hex);
    }

    // ============================================================== sheet 1

    private static function tSkyAtom(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#e6f2ff'), self::hex('#f6fbff'), 'horizontal');
        Doodles::blob($c, (int) ($w * 0.12), (int) ($h * 0.4), (int) ($h * 1.4), (int) ($h * 0.7), self::hex('#cfe5fb'), 0.8, 2);
        Doodles::wave($c, (int) ($w * 0.52), $w, $h * 0.55, $h * 0.62, 0.5, M_PI / 2, self::hex('#78b3f2'), 0.55);
        Doodles::wave($c, (int) ($w * 0.66), $w, $h * 0.72, $h * 0.55, 0.5, M_PI / 2, self::hex('#2f7be0'), 0.95);
        Doodles::ribbon($c, (int) ($w * 0.5), $w, $h * 0.62, $h * 0.5, 0.5, M_PI / 2, $h * 0.1, self::hex('#f4c02e'), 0.95);
        Doodles::wave($c, 0, (int) ($w * 0.3), $h * 1.05, $h * 0.12, 1.2, 0.5, self::hex('#f4c02e'), 0.9);
        Doodles::atom($c, (int) ($h * 0.85), (int) ($h * 0.5), (int) ($h * 0.5), self::hex('#5b8bd6'), self::hex('#3a73d8'));
        Doodles::dots($c, (int) ($w * 0.9), (int) ($h * 0.38), 9, 5, (int) ($h * 0.1), self::hex('#ffffff'), 0.7);
        Doodles::dots($c, (int) ($h * 1.5), (int) ($h * 0.08), 7, 3, (int) ($h * 0.09), self::hex('#8fb6e6'), 0.5);
    }

    private static function tPeachLeaves(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#ffefd9'), self::hex('#fff6e8'), 'horizontal');
        foreach ([[0.0, 0.5, 1.0], [0.07, 1.05, 0.8]] as $i => [$fx, $fy, $sz]) {
            Doodles::blob($c, (int) ($w * $fx + $h * 0.5), (int) ($h * $fy), (int) ($h * 0.7 * $sz), (int) ($h * 0.55 * $sz), self::hex('#f48b4a'), 0.75, 3 + $i);
        }
        Doodles::blob($c, (int) ($w * 0.95), (int) ($h * 0.2), (int) ($h * 1.5), (int) ($h * 0.8), self::hex('#fbd28c'), 0.8, 5);
        Doodles::blob($c, (int) ($w * 0.99), (int) ($h * 0.15), (int) ($h * 0.7), (int) ($h * 0.5), self::hex('#f28b3c'), 0.7, 6);
        Doodles::sprig($c, (int) ($h * 1.15), (int) ($h * 1.0), (int) ($h * 0.85), 12, self::hex('#3b3a44'), true, 6, 0.9, 4);
        Doodles::sprig($c, (int) ($w - $h * 0.75), (int) ($h * 1.02), (int) ($h * 0.9), -14, self::hex('#3b3a44'), true, 7, 0.9, 7);
        Doodles::dots($c, (int) ($w * 0.12), (int) ($h * 0.12), 6, 4, (int) ($h * 0.09), self::hex('#e8a060'), 0.5);
        Doodles::dots($c, (int) ($w * 0.88), (int) ($h * 0.1), 5, 4, (int) ($h * 0.09), self::hex('#e8a060'), 0.5);
    }

    private static function tLavenderBubbles(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#efe3ff'), self::hex('#fbf0ff'), 'horizontal');
        Doodles::blob($c, (int) ($w * 0.08), (int) ($h * 0.75), (int) ($h * 1.5), (int) ($h * 0.6), self::hex('#d8c0f6'), 0.8, 8);
        Doodles::blob($c, (int) ($w * 0.93), (int) ($h * 0.9), (int) ($h * 1.7), (int) ($h * 0.7), self::hex('#c6a4f0'), 0.8, 9);
        Doodles::wave($c, (int) ($w * 0.8), $w, $h * 0.85, $h * 0.12, 1.5, 1.0, self::hex('#a47ae6'), 0.7);
        Doodles::sphere($c, (int) ($h * 0.42), (int) ($h * 0.3), (int) ($h * 0.48), self::hex('#a47be8'));
        Doodles::sphere($c, (int) ($h * 1.15), (int) ($h * 0.72), (int) ($h * 0.22), self::hex('#9a6fe0'));
        Doodles::sphere($c, (int) ($w * 0.975), (int) ($h * 0.35), (int) ($h * 0.92), self::hex('#a47be8'));
        Doodles::dots($c, (int) ($w * 0.06), (int) ($h * 0.5), 8, 5, (int) ($h * 0.085), self::hex('#9b78d6'), 0.45);
        Doodles::dots($c, (int) ($w * 0.62), (int) ($h * 0.08), 8, 4, (int) ($h * 0.085), self::hex('#9b78d6'), 0.4);
    }

    private static function tAquaHex(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#d4f1fb'), self::hex('#effaff'), 'horizontal');
        Doodles::hexes($c, 0, 0, $w, $h, (int) ($h * 0.34), self::hex('#7cc9e8'), 0.5, self::hex('#4fc0ea'));
        Doodles::blob($c, (int) ($w * 0.5), (int) ($h * 0.5), (int) ($w * 0.34), (int) ($h * 0.6), self::hex('#f5fcff'), 0.85, 11);
        Doodles::hexes($c, (int) ($w * 0.88), 0, (int) ($w * 0.12), $h, (int) ($h * 0.3), self::hex('#38b6e0'), 0.55, self::hex('#38b6e0'), 14);
    }

    private static function tYellowBrush(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#fdf6e6'), self::hex('#fff9ee'), 'horizontal');
        Doodles::blob($c, (int) ($w * 0.5), (int) ($h * 0.5), (int) ($w * 0.38), (int) ($h * 0.7), self::hex('#f6ecd6'), 0.7, 12);
        Doodles::swipes($c, 0, 0, (int) ($h * 1.5), $h, [self::hex('#f6b823'), self::hex('#fbd05a')], 6, 21, -38, 1.0);
        Doodles::swipes($c, (int) ($w - $h * 1.6), 0, (int) ($h * 1.6), $h, [self::hex('#f6b823'), self::hex('#fbd05a')], 7, 22, -38, 1.0);
        Doodles::dots($c, (int) ($h * 0.5), (int) ($h * 0.6), 8, 3, (int) ($h * 0.08), self::hex('#2aa0a8'), 0.6);
        Doodles::dots($c, (int) ($w * 0.9), (int) ($h * 0.74), 5, 3, (int) ($h * 0.08), self::hex('#2aa0a8'), 0.55);
    }

    private static function tLavenderBooks(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#efe9ff'), self::hex('#f8f5ff'), 'horizontal');
        Doodles::wave($c, 0, (int) ($w * 0.5), $h * 0.5, $h * 0.28, 1.0, 0.3, self::hex('#b190ee'), 0.4, true, $h * 0.04);
        Doodles::wave($c, 0, (int) ($w * 0.36), $h * 0.78, $h * 0.18, 1.3, 1.0, self::hex('#8f7be8'), 0.45);
        Doodles::wave($c, (int) ($w * 0.8), $w, $h * 0.96, $h * 0.16, 1.0, 0.0, self::hex('#7aa6ee'), 0.8);
        Doodles::dots($c, (int) ($w * 0.76), (int) ($h * 0.3), 9, 5, (int) ($h * 0.09), self::hex('#8e8ad6'), 0.45);
        Doodles::dots($c, (int) ($h * 0.3), (int) ($h * 0.1), 7, 4, (int) ($h * 0.085), self::hex('#8e8ad6'), 0.4);
        $bx = (int) ($w - $h * 1.0);
        Doodles::plant($c, (int) ($w - $h * 0.22), (int) ($h * 0.99), (int) ($h * 0.85));
        Doodles::books($c, $bx, (int) ($h * 0.99), (int) ($h * 0.85), [[246, 246, 250], [92, 78, 210], [250, 190, 40], [46, 70, 170]], 3);
    }

    private static function tDoodleGrid(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#ffffff'), self::hex('#f7f8fd'), 'horizontal');
        Doodles::grid($c, (int) ($h * 0.16), self::hex('#9fb0dc'), 0.2);
        Doodles::swipes($c, 0, (int) ($h * 0.5), (int) ($h * 0.7), (int) ($h * 0.5), [self::hex('#f6b823')], 5, 31, -38, 1.0);
        Doodles::swipes($c, (int) ($w - $h * 0.8), 0, (int) ($h * 0.8), $h, [self::hex('#2f6ce0'), self::hex('#f6b823')], 7, 33, -38, 1.0);
        Doodles::bulb($c, (int) ($h * 0.5), (int) ($h * 0.36), (int) ($h * 0.45), self::hex('#3a3a9a'), false);
        if ($wide) {
            Doodles::graph($c, (int) ($h * 0.95), (int) ($h * 0.28), (int) ($h * 1.1), (int) ($h * 0.62), 'parabola', self::hex('#3a3a9a'), false);
        }
        Doodles::atom($c, (int) ($w - ($wide ? $h * 1.95 : $h * 0.9)), (int) ($h * 0.5), (int) ($h * 0.42), self::hex('#3a3a9a'), self::hex('#3a2f8f'));
        if ($wide) {
            $c->text('E = mc²', $s, (int) ($h * 0.3), $w - $h * 1.55, $h * 0.62, self::hex('#3a3a9a'));
        }
        Doodles::dots($c, (int) ($w * 0.9), (int) ($h * 0.1), 5, 3, (int) ($h * 0.08), self::hex('#2aa0a8'), 0.55);
    }

    private static function tCoralWaves(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#ffe9e4'), self::hex('#fff4ee'), 'diagonal');
        Doodles::wave($c, 0, (int) ($w * 0.66), $h * 0.45, $h * 0.3, 1.0, 0.2, self::hex('#f9b4a8'), 0.45, false, $h * 0.04);
        Doodles::wave($c, 0, (int) ($w * 0.4), $h * 0.8, $h * 0.2, 1.4, 1.2, self::hex('#ee6f86'), 0.6);
        Doodles::wave($c, (int) ($w * 0.34), $w, $h * 0.5, $h * 0.25, 1.1, 2.2, self::hex('#fbcdbd'), 0.5, false, $h * 0.05);
        Doodles::wave($c, (int) ($w * 0.7), $w, $h * 0.85, $h * 0.18, 1.2, 0.4, self::hex('#f0728a'), 0.6);
        Doodles::ribbon($c, 0, (int) ($w * 0.5), $h * 0.55, $h * 0.22, 1.0, 0.6, 2, self::hex('#f4c44a'), 0.9);
        Doodles::ribbon($c, (int) ($w * 0.55), $w, $h * 0.6, $h * 0.2, 1.1, 2.4, 2, self::hex('#f4c44a'), 0.9);
        Doodles::sphere($c, (int) ($w * 0.93), (int) ($h * 0.32), (int) ($h * 0.62), self::hex('#f69a90'));
        Doodles::dots($c, (int) ($w * 0.5), (int) ($h * 0.08), 9, 3, (int) ($h * 0.08), self::hex('#e07a86'), 0.4);
    }

    private static function tGreenLeaves(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#d8eea4'), self::hex('#fbf7cf'), 'horizontal');
        Doodles::wave($c, 0, (int) ($w * 0.5), $h * 0.62, $h * 0.18, 1.2, 0.0, self::hex('#8fcf54'), 0.7);
        Doodles::wave($c, (int) ($w * 0.5), $w, $h * 0.62, $h * 0.18, 1.2, 1.0, self::hex('#8fcf54'), 0.7);
        Doodles::wave($c, 0, $w, $h * 0.9, $h * 0.14, 1.6, 0.6, self::hex('#5fb23c'), 0.75);
        Doodles::sprig($c, (int) ($h * 0.35), (int) ($h * 1.0), (int) ($h * 0.95), 38, self::hex('#2b6b2f'), false, 7, 0.95, 5);
        Doodles::sprig($c, (int) ($w - $h * 0.4), (int) ($h * 1.0), (int) ($h * 0.95), -38, self::hex('#2b6b2f'), false, 7, 0.95, 8);
        Doodles::dots($c, (int) ($w * 0.14), (int) ($h * 0.05), 8, 3, (int) ($h * 0.085), self::hex('#4b9a3a'), 0.45);
        Doodles::dots($c, (int) ($w * 0.84), (int) ($h * 0.7), 6, 3, (int) ($h * 0.085), self::hex('#4b9a3a'), 0.45);
    }

    private static function tBlueWavesAtom(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#d9ecff'), self::hex('#eef7ff'), 'horizontal');
        Doodles::wave($c, 0, $w, $h * 0.42, $h * 0.3, 1.6, 0.0, self::hex('#8cc2f4'), 0.5, false, $h * 0.04);
        Doodles::wave($c, 0, $w, $h * 0.62, $h * 0.22, 1.9, 1.2, self::hex('#4f9cf0'), 0.6, false, $h * 0.03);
        Doodles::wave($c, 0, $w, $h * 0.85, $h * 0.14, 2.2, 0.3, self::hex('#2a74e0'), 0.75);
        Doodles::atom($c, (int) ($w - $h * 0.75), (int) ($h * 0.5), (int) ($h * 0.52), self::hex('#4a86d6'), self::hex('#2e6fd6'));
        Doodles::dots($c, (int) ($w * 0.08), (int) ($h * 0.12), 8, 3, (int) ($h * 0.09), self::hex('#ffffff'), 0.7);
    }

    // ============================================================== sheet 2

    private static function tBooksEmc2(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#fbf6e6'), self::hex('#fffbf0'), 'horizontal');
        Doodles::blob($c, (int) ($h * 0.9), (int) ($h * 0.9), (int) ($h * 1.4), (int) ($h * 0.6), self::hex('#f9d27a'), 0.7, 13);
        Doodles::blob($c, (int) ($w * 0.94), (int) ($h * 0.95), (int) ($h * 1.6), (int) ($h * 0.6), self::hex('#f7c35a'), 0.65, 14);
        Doodles::plant($c, (int) ($h * 0.82), (int) ($h * 0.97), (int) ($h * 0.82));
        Doodles::books($c, 0, (int) ($h * 0.99), (int) ($h * 0.95), [[104, 78, 168], [247, 178, 40], [40, 60, 140]], 2);
        if ($wide) {
            Doodles::atom($c, (int) ($w - $h * 1.45), (int) ($h * 0.45), (int) ($h * 0.42), self::hex('#3a3a9a'), self::hex('#2f3a9a'));
            $c->text('E = mc²', $s, (int) ($h * 0.27), $w - $h * 1.02, $h * 0.44, self::hex('#3a3a9a'));
            Doodles::planet($c, (int) ($w - $h * 0.3), (int) ($h * 0.62), (int) ($h * 0.22));
        } else {
            Doodles::atom($c, (int) ($w - $h * 0.65), (int) ($h * 0.5), (int) ($h * 0.42), self::hex('#3a3a9a'), self::hex('#2f3a9a'));
        }
        Doodles::dots($c, (int) ($h * 2.05), (int) ($h * 0.1), 6, 3, (int) ($h * 0.085), self::hex('#e0a030'), 0.5);
        Doodles::dots($c, (int) ($w - $h * 2.4), (int) ($h * 0.5), 6, 3, (int) ($h * 0.085), self::hex('#e0a030'), 0.5);
    }

    private static function tLavenderNotebook(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#f1e6ff'), self::hex('#faf3ff'), 'horizontal');
        Doodles::wave($c, 0, (int) ($w * 0.55), $h * 0.5, $h * 0.3, 1.0, 0.5, self::hex('#a47be8'), 0.7, true, $h * 0.04);
        Doodles::wave($c, (int) ($w * 0.65), $w, $h * 0.8, $h * 0.2, 1.1, 0.4, self::hex('#b794f0'), 0.65, false, $h * 0.04);
        Doodles::wave($c, (int) ($w * 0.8), $w, $h * 0.95, $h * 0.2, 1.1, 1.6, self::hex('#8f6ee0'), 0.7);
        Doodles::notebook($c, (int) ($w - $h * 1.25), (int) ($h * 0.1), (int) ($h * 0.9), (int) ($h * 0.95), 8, $s, '', self::hex('#2a2a80'));
        if ($wide) {
            Doodles::bulb($c, (int) ($w - $h * 1.9), (int) ($h * 0.46), (int) ($h * 0.38), self::hex('#2f2a90'), false);
        }
        Doodles::dots($c, (int) ($w * 0.15), (int) ($h * 0.15), 7, 3, (int) ($h * 0.085), self::hex('#8a78d6'), 0.45);
    }

    private static function tCradleBlue(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#e3f2ff'), self::hex('#f2f9ff'), 'horizontal');
        Doodles::blob($c, (int) ($h * 1.3), (int) ($h * 0.35), (int) ($h * 0.55), (int) ($h * 0.55), self::hex('#bcdcf7'), 0.7, 15);
        Doodles::wave($c, 0, (int) ($w * 0.45), $h * 0.78, $h * 0.18, 1.3, 0.2, self::hex('#7dbcf2'), 0.6);
        Doodles::wave($c, (int) ($w * 0.62), $w, $h * 0.78, $h * 0.22, 1.0, 1.0, self::hex('#5aa6f0'), 0.65);
        Doodles::swipes($c, (int) ($w - $h * 0.9), (int) ($h * 0.1), (int) ($h * 0.9), (int) ($h * 0.8), [self::hex('#2f7be0'), self::hex('#6aa8f0')], 4, 41, -38, 1.0);
        Doodles::cradle($c, (int) ($h * 0.18), (int) ($h * 0.98), (int) ($h * 1.15));
        if ($wide) {
            Doodles::graph($c, (int) ($w - $h * 1.55), (int) ($h * 0.18), (int) ($h * 1.0), (int) ($h * 0.64), 'sine', self::hex('#2a64c8'), true, $s);
        }
        Doodles::dots($c, (int) ($w * 0.2), (int) ($h * 0.1), 7, 3, (int) ($h * 0.085), self::hex('#7aa6d6'), 0.45);
    }

    private static function tPeachPhysics(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#fff1da'), self::hex('#fff8ea'), 'horizontal');
        Doodles::blob($c, (int) ($h * 0.35), (int) ($h * 0.4), (int) ($h * 0.7), (int) ($h * 0.6), self::hex('#f48b4a'), 0.75, 16);
        Doodles::blob($c, (int) ($w * 0.9), (int) ($h * 0.95), (int) ($h * 1.8), (int) ($h * 0.6), self::hex('#fbd28c'), 0.75, 17);
        Doodles::blob($c, (int) ($w * 0.98), (int) ($h * 0.3), (int) ($h * 1.0), (int) ($h * 0.5), self::hex('#f9c982'), 0.6, 18);
        Doodles::atom($c, (int) ($h * 0.62), (int) ($h * 0.5), (int) ($h * 0.46), self::hex('#3b3a44'), self::hex('#3b3a44'), 0.02);
        if ($wide) {
            Doodles::bulb($c, (int) ($h * 1.5), (int) ($h * 0.36), (int) ($h * 0.36), self::hex('#3b3a44'), false);
        }
        Doodles::plant($c, (int) ($w - $h * 0.25), (int) ($h * 0.98), (int) ($h * 0.82));
        Doodles::books($c, (int) ($w - $h * 1.28), (int) ($h * 0.98), (int) ($h * 1.0), [[210, 74, 110], [38, 70, 160]], 6);
        Doodles::dots($c, (int) ($h * 2.0), (int) ($h * 0.5), 6, 3, (int) ($h * 0.085), self::hex('#e0a060'), 0.5);
    }

    private static function tMagnetPurple(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#e9dcff'), self::hex('#f6eeff'), 'horizontal');
        Doodles::hexes($c, 0, 0, $w, $h, (int) ($h * 0.36), self::hex('#b9a2ea'), 0.45, self::hex('#c9b6f0'), 19);
        Doodles::blob($c, (int) ($w * 0.5), (int) ($h * 0.5), (int) ($w * 0.34), (int) ($h * 0.7), self::hex('#f7f1ff'), 0.85, 20);
        Doodles::swipes($c, 0, (int) ($h * 0.55), (int) ($h * 0.8), (int) ($h * 0.45), [self::hex('#8f6ee0')], 4, 51, -38, 1.0);
        Doodles::swipes($c, (int) ($w - $h * 1.3), 0, (int) ($h * 1.3), $h, [self::hex('#8f6ee0'), self::hex('#b49af0')], 7, 52, -38, 1.0);
        Doodles::magnet($c, (int) ($h * 0.72), (int) ($h * 0.5), (int) ($h * 0.42), self::hex('#3a2f9a'));
    }

    private static function tGreenGraph(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#bfe8cd'), self::hex('#effaf2'), 'horizontal');
        Doodles::facets($c, 0, 0, (int) ($w * 0.3), $h, (int) ($h * 0.5), self::hex('#2fa86a'), 0.5, 61);
        Doodles::facets($c, (int) ($w * 0.72), 0, (int) ($w * 0.28), $h, (int) ($h * 0.5), self::hex('#2fa86a'), 0.55, 62);
        Doodles::graph($c, (int) ($h * 0.32), (int) ($h * 0.12), (int) ($h * 1.15), (int) ($h * 0.78), 'decay', self::hex('#1f4a30'), true, $s);
        Doodles::pencilCup($c, (int) ($w - $h * 0.5), (int) ($h * 0.98), (int) ($h * 0.38));
        Doodles::books($c, (int) ($w - $h * 1.2), (int) ($h * 0.98), (int) ($h * 0.78), [[244, 240, 232], [60, 100, 200], [250, 190, 50]], 9);
        Doodles::dots($c, (int) ($w * 0.84), (int) ($h * 0.05), 5, 3, (int) ($h * 0.085), self::hex('#ffffff'), 0.7);
    }

    private static function tBulbLeaves(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#ffeed2'), self::hex('#fff7e8'), 'horizontal');
        Doodles::blob($c, (int) ($h * 0.7), (int) ($h * 0.95), (int) ($h * 1.1), (int) ($h * 0.6), self::hex('#f6a064'), 0.7, 22);
        Doodles::blob($c, (int) ($w * 0.96), (int) ($h * 0.7), (int) ($h * 1.3), (int) ($h * 0.7), self::hex('#f7952f'), 0.75, 23);
        Doodles::blob($c, (int) ($w * 0.88), (int) ($h * 0.3), (int) ($h * 0.8), (int) ($h * 0.6), self::hex('#fbd28c'), 0.6, 24);
        Doodles::books($c, 0, (int) ($h * 0.99), (int) ($h * 0.95), [[196, 120, 80], [60, 50, 120]], 5);
        Doodles::sprig($c, (int) ($h * 0.35), (int) ($h * 0.62), (int) ($h * 0.7), 18, self::hex('#2f6b3a'), false, 6, 0.95, 6);
        Doodles::bulb($c, (int) ($h * 1.45), (int) ($h * 0.5), (int) ($h * 0.46), self::hex('#f7c030'), true);
        Doodles::sprig($c, (int) ($w - $h * 0.35), (int) ($h * 1.0), (int) ($h * 0.9), -24, self::hex('#2f6b3a'), false, 7, 0.95, 9);
        Doodles::dots($c, (int) ($h * 2.0), (int) ($h * 0.1), 6, 4, (int) ($h * 0.085), self::hex('#e8a040'), 0.55);
    }

    private static function tAquaAtom(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#d8ecfb'), self::hex('#eef7ff'), 'horizontal');
        Doodles::hexes($c, 0, 0, $w, $h, (int) ($h * 0.34), self::hex('#8cc2ea'), 0.4, self::hex('#8cc2ea'), 71);
        Doodles::blob($c, (int) ($w * 0.5), (int) ($h * 0.5), (int) ($w * 0.34), (int) ($h * 0.7), self::hex('#f4faff'), 0.85, 25);
        Doodles::atom($c, (int) ($w - $h * 0.75), (int) ($h * 0.5), (int) ($h * 0.55), self::hex('#3a8ee0'), self::hex('#e8b030'), 0.03);
        Doodles::dots($c, (int) ($w * 0.88), (int) ($h * 0.82), 9, 2, (int) ($h * 0.085), self::hex('#2a7ad0'), 0.7);
    }

    private static function tPrismRainbow(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#cfd6fa'), self::hex('#eaedff'), 'horizontal');
        Doodles::blob($c, (int) ($w * 0.5), (int) ($h * 0.5), (int) ($w * 0.34), (int) ($h * 0.7), self::hex('#f2f3ff'), 0.7, 26);
        Doodles::wave($c, (int) ($w * 0.6), $w, $h * 0.55, $h * 0.3, 0.9, 0.4, self::hex('#8aa0f0'), 0.65);
        Doodles::wave($c, (int) ($w * 0.72), $w, $h * 0.82, $h * 0.2, 1.0, 1.4, self::hex('#6a80e6'), 0.7);
        Doodles::prism($c, (int) ($h * 0.82), (int) ($h * 0.5), (int) ($h * 0.62), (int) ($h * ($wide ? 1.2 : 0.8)));
        Doodles::books($c, 0, (int) ($h * 0.99), (int) ($h * 0.8), [[52, 72, 160], [244, 238, 224]], 4);
        Doodles::sparkle($c, (int) ($h * 2.4), (int) ($h * 0.22), (int) ($h * 0.05), self::hex('#ffffff'), 0.9);
        Doodles::sparkle($c, (int) ($h * 2.7), (int) ($h * 0.68), (int) ($h * 0.04), self::hex('#ffffff'), 0.8);
        Doodles::dots($c, (int) ($w * 0.84), (int) ($h * 0.1), 8, 4, (int) ($h * 0.085), self::hex('#6a78d0'), 0.45);
    }

    private static function tYellowProjectile(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#fff4d2'), self::hex('#fffaec'), 'horizontal');
        Doodles::swipes($c, 0, 0, (int) ($h * 1.5), $h, [self::hex('#f6b823'), self::hex('#fbd05a')], 6, 81, -38, 1.0);
        Doodles::swipes($c, (int) ($w - $h * 2.4), 0, (int) ($h * 1.3), $h, [self::hex('#f6c13a'), self::hex('#fbd77a')], 4, 82, -38, 1.0);
        Doodles::blob($c, (int) ($w * 0.5), (int) ($h * 0.5), (int) ($w * 0.34), (int) ($h * 0.7), self::hex('#fffcf2'), 0.8, 27);
        if ($wide) {
            Doodles::graph($c, (int) ($w - $h * 2.05), (int) ($h * 0.18), (int) ($h * 1.0), (int) ($h * 0.68), 'parabola', self::hex('#4a3a1c'), false);
        }
        Doodles::sprig($c, (int) ($w - $h * 0.8), (int) ($h * 1.0), (int) ($h * 0.8), 30, self::hex('#2f6b3a'), false, 6, 0.95, 4);
        Doodles::notebook($c, (int) ($w - $h * 1.05), (int) ($h * 0.18), (int) ($h * 0.82), (int) ($h * 0.82), 10, $s, 's=ut+½at²', self::hex('#2a2a70'));
        Doodles::dots($c, (int) ($h * 1.7), (int) ($h * 0.6), 6, 3, (int) ($h * 0.085), self::hex('#d8a020'), 0.55);
    }

    // ============================================================== more styles

    private static function tMintChemistry(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#d4f5ea'), self::hex('#effcf7'), 'horizontal');
        Doodles::hexes($c, 0, 0, $w, $h, (int) ($h * 0.34), self::hex('#6fcfb4'), 0.35, self::hex('#6fcfb4'), 91);
        Doodles::blob($c, (int) ($w * 0.5), (int) ($h * 0.5), (int) ($w * 0.34), (int) ($h * 0.7), self::hex('#f6fffb'), 0.85, 28);
        Doodles::wave($c, 0, $w, $h * 1.0, $h * 0.1, 3.0, 0.4, self::hex('#5cc4a6'), 0.6);
        Props::flask($c, (int) ($h * 0.55), (int) ($h * 0.97), (int) ($h * 0.82), self::hex('#8a5be0'), 'conical');
        Props::molecule($c, (int) ($w - $h * 0.62), (int) ($h * 0.46), (int) ($h * 0.5), self::hex('#2f6fe0'));
        if ($wide) {
            Props::flask($c, (int) ($h * 1.25), (int) ($h * 0.97), (int) ($h * 0.62), self::hex('#f0a030'), 'round');
            Props::testTubes($c, (int) ($w - $h * 1.78), (int) ($h * 0.97), (int) ($h * 0.7), [self::hex('#e85a7a'), self::hex('#3aa0e8'), self::hex('#6cc04a')]);
        }
        Doodles::dots($c, (int) ($w * 0.9), (int) ($h * 0.05), 5, 3, (int) ($h * 0.085), self::hex('#2a9a80'), 0.5);
    }

    private static function tSunriseMaths(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#ffe3cc'), self::hex('#fff4e6'), 'horizontal');
        Doodles::blob($c, (int) ($h * 0.7), (int) ($h * 0.35), (int) ($h * 1.1), (int) ($h * 0.65), self::hex('#fbbf84'), 0.6, 29);
        Doodles::blob($c, (int) ($w * 0.97), (int) ($h * 0.3), (int) ($h * 1.2), (int) ($h * 0.7), self::hex('#f9a86a'), 0.65, 30);
        Doodles::swipes($c, (int) ($w - $h * 0.9), (int) ($h * 0.55), (int) ($h * 0.9), (int) ($h * 0.45), [self::hex('#f6b823')], 4, 95, -38, 1.0);
        Props::geometrySet($c, (int) ($h * 0.1), (int) ($h * 0.98), (int) ($h * 0.74));
        Props::calculator($c, (int) ($w - $h * 0.95), (int) ($h * 0.16), (int) ($h * 0.56), 10);
        if ($wide) {
            $c->text('π', $s, (int) ($h * 0.42), $h * 1.95, $h * 0.5, self::hex('#d9622a'), 6);
            $c->text('a² + b² = c²', $s, (int) ($h * 0.22), $w - $h * 2.0, $h * 0.2, self::hex('#d9622a'), 4);
        }
        Doodles::dots($c, (int) ($h * 0.15), (int) ($h * 0.06), 7, 3, (int) ($h * 0.085), self::hex('#e8944a'), 0.5);
    }

    private static function tForestBiology(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#e2f4d0'), self::hex('#f6fbe8'), 'horizontal');
        Doodles::wave($c, 0, $w, $h * 0.78, $h * 0.14, 1.6, 0.4, self::hex('#a6d870'), 0.6);
        Doodles::wave($c, 0, $w, $h * 0.92, $h * 0.1, 2.0, 1.4, self::hex('#7cc050'), 0.7);
        Props::dna($c, (int) ($h * 0.7), (int) ($h * 0.5), (int) ($h * 1.5), self::hex('#2f9a58'), self::hex('#e0782c'), 68);
        Props::microscope($c, (int) ($w - $h * 0.75), (int) ($h * 0.98), (int) ($h * 0.86));
        if ($wide) {
            Doodles::sprig($c, (int) ($w - $h * 1.6), (int) ($h * 1.0), (int) ($h * 0.8), -20, self::hex('#2b6b2f'), false, 6, 0.95, 12);
            Doodles::sprig($c, (int) ($h * 1.4), (int) ($h * 1.0), (int) ($h * 0.7), 22, self::hex('#2b6b2f'), false, 6, 0.95, 13);
        }
        Doodles::dots($c, (int) ($w * 0.88), (int) ($h * 0.06), 5, 3, (int) ($h * 0.085), self::hex('#4b9a3a'), 0.45);
    }

    private static function tOceanGlobe(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#cdeff2'), self::hex('#ecf9fb'), 'horizontal');
        Doodles::wave($c, 0, $w, $h * 0.7, $h * 0.14, 1.8, 0.2, self::hex('#8fd4dc'), 0.55);
        Doodles::wave($c, 0, $w, $h * 0.86, $h * 0.12, 2.3, 1.2, self::hex('#4fb8c6'), 0.65);
        Doodles::wave($c, 0, $w, $h * 1.0, $h * 0.08, 3.0, 0.6, self::hex('#1e9db4'), 0.8);
        if ($wide) {
            Props::cloud($c, (int) ($h * 1.55), (int) ($h * 0.25), (int) ($h * 0.42));
            Props::cloud($c, (int) ($w - $h * 1.6), (int) ($h * 0.2), (int) ($h * 0.36));
        }
        Props::globe($c, (int) ($h * 0.62), (int) ($h * 0.5), (int) ($h * 0.52));
        Icons::draw($c, 'compass', (int) ($w - $h * 0.6), (int) ($h * 0.55), (int) ($h * 0.5), self::hex('#0f6f86'), 0.05);
        Doodles::dots($c, (int) ($w * 0.9), (int) ($h * 0.7), 5, 3, (int) ($h * 0.085), self::hex('#ffffff'), 0.7);
    }

    private static function tNavySuccess(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#1a2858'), self::hex('#33458f'), 'horizontal');
        Doodles::blob($c, (int) ($w * 0.5), (int) ($h * 0.5), (int) ($w * 0.34), (int) ($h * 0.7), self::hex('#4a5cb0'), 0.3, 31);
        Doodles::ribbon($c, 0, $w, $h * 0.9, $h * 0.1, 1.4, 0.4, $h * 0.04, self::hex('#f6c13a'), 0.6);
        $bits = [self::hex('#f6c13a'), self::hex('#ffffff'), self::hex('#ee6a8a'), self::hex('#6ac0f0')];
        Props::confetti($c, 0, 0, (int) ($h * 0.85), $h, $bits, 8, 5);
        Props::confetti($c, (int) ($w - $h * 1.3), 0, (int) ($h * 0.8), $h, $bits, 8, 6);
        Props::gradCap($c, (int) ($h * 0.6), (int) ($h * 0.55), (int) ($h * 0.4), self::hex('#6a80e0'));
        Props::trophy($c, (int) ($w - $h * 0.55), (int) ($h * 0.98), (int) ($h * 0.72));
        Doodles::sparkle($c, (int) ($h * 1.05), (int) ($h * 0.25), (int) ($h * 0.07), self::hex('#f6c13a'), 0.95);
        Doodles::sparkle($c, (int) ($w - $h * 1.05), (int) ($h * 0.7), (int) ($h * 0.06), self::hex('#ffffff'), 0.9);
    }

    private static function tCreamStationery(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#fff5e0'), self::hex('#fffaf0'), 'horizontal');
        Doodles::blob($c, (int) ($h * 0.7), (int) ($h * 0.95), (int) ($h * 1.2), (int) ($h * 0.55), self::hex('#fbd9a0'), 0.7, 32);
        Doodles::blob($c, (int) ($w * 0.96), (int) ($h * 0.7), (int) ($h * 1.3), (int) ($h * 0.7), self::hex('#f9cf94'), 0.65, 33);
        Doodles::books($c, (int) ($h * 0.1), (int) ($h * 0.99), (int) ($h * 0.8), [[216, 90, 90], [250, 190, 50], [60, 110, 190]], 7);
        if ($wide) {
            Props::clock($c, (int) ($h * 1.45), (int) ($h * 0.3), (int) ($h * 0.3));
            Doodles::pencilCup($c, (int) ($w - $h * 1.45), (int) ($h * 0.98), (int) ($h * 0.42));
        }
        Doodles::notebook($c, (int) ($w - $h * 0.95), (int) ($h * 0.14), (int) ($h * 0.72), (int) ($h * 0.82), 8, $s, '', self::hex('#2a2a80'));
        Doodles::dots($c, (int) ($w * 0.12), (int) ($h * 0.05), 6, 3, (int) ($h * 0.085), self::hex('#d8a050'), 0.5);
    }

    private static function tRoseCelebration(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#ffe1ea'), self::hex('#fff3f6'), 'horizontal');
        Doodles::wave($c, 0, (int) ($w * 0.5), $h * 0.9, $h * 0.14, 1.4, 0.2, self::hex('#f8a8bd'), 0.5);
        Doodles::wave($c, (int) ($w * 0.5), $w, $h * 0.9, $h * 0.14, 1.4, 1.6, self::hex('#f7b8a8'), 0.5);
        $bits = [self::hex('#e8527e'), self::hex('#f6b823'), self::hex('#7a5be0'), self::hex('#3aa8d8')];
        Props::confetti($c, 0, 0, (int) ($h * 0.9), $h, $bits, 9, 7);
        Props::confetti($c, (int) ($w - $h * 1.5), 0, (int) ($h * 0.9), $h, $bits, 9, 8);
        Props::gradCap($c, (int) ($h * 0.55), (int) ($h * 0.55), (int) ($h * 0.4), self::hex('#c03a68'));
        Doodles::sparkle($c, (int) ($w - $h * 0.55), (int) ($h * 0.5), (int) ($h * 0.16), self::hex('#f6b823'), 1.0);
        Doodles::sparkle($c, (int) ($w - $h * 0.95), (int) ($h * 0.22), (int) ($h * 0.07), self::hex('#e8527e'), 0.9);
        Doodles::sparkle($c, (int) ($h * 1.0), (int) ($h * 0.75), (int) ($h * 0.07), self::hex('#f6b823'), 0.9);
    }

    private static function tBlueprintDark(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        $line = self::hex('#bcd4ff');
        Doodles::wash($c, self::hex('#10306a'), self::hex('#1d4694'), 'horizontal');
        Doodles::grid($c, (int) ($h * 0.16), self::hex('#8fb2f2'), 0.16);
        Doodles::blob($c, (int) ($w * 0.5), (int) ($h * 0.5), (int) ($w * 0.34), (int) ($h * 0.7), self::hex('#2a5cb8'), 0.3, 34);
        Doodles::atom($c, (int) ($h * 0.65), (int) ($h * 0.5), (int) ($h * 0.46), $line, self::hex('#ffd24a'), 0.025);
        Doodles::bulb($c, (int) ($w - $h * 0.6), (int) ($h * 0.46), (int) ($h * 0.42), $line, false);
        if ($wide) {
            Doodles::graph($c, (int) ($h * 1.35), (int) ($h * 0.22), (int) ($h * 0.95), (int) ($h * 0.6), 'sine', $line, true, $s);
            $c->text('E = mc²', $s, (int) ($h * 0.26), $w - $h * 1.85, $h * 0.58, $line);
        }
        Doodles::dots($c, (int) ($w * 0.9), (int) ($h * 0.8), 5, 2, (int) ($h * 0.085), self::hex('#ffd24a'), 0.7);
    }

    private static function tSunnyRocket(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#bfe8fb'), self::hex('#eaf8ff'), 'vertical');
        Doodles::wave($c, 0, $w, $h * 1.0, $h * 0.12, 2.2, 0.3, self::hex('#8fd070'), 0.85);
        Doodles::wave($c, 0, $w, $h * 1.08, $h * 0.1, 2.6, 1.3, self::hex('#6cbb4a'), 0.9);
        Props::cloud($c, (int) ($h * 0.5), (int) ($h * 0.3), (int) ($h * 0.44));
        Props::rocket($c, (int) ($w - $h * 0.5), (int) ($h * 0.5), (int) ($h * 0.68), 38);
        Doodles::sparkle($c, (int) ($w - $h * 0.9), (int) ($h * 0.6), (int) ($h * 0.06), self::hex('#f6b823'), 1.0);
        if ($wide) {
            Props::cloud($c, (int) ($h * 1.0), (int) ($h * 0.72), (int) ($h * 0.3));
            Props::cloud($c, (int) ($w - $h * 1.5), (int) ($h * 0.22), (int) ($h * 0.34));
            Doodles::sparkle($c, (int) ($w - $h * 0.3), (int) ($h * 0.15), (int) ($h * 0.06), self::hex('#f6b823'), 1.0);
            Doodles::planet($c, (int) ($w - $h * 1.9), (int) ($h * 0.36), (int) ($h * 0.2));
        }
    }

    private static function tMintGraphPaper(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        $ink = self::hex('#1f5a40');
        Doodles::wash($c, self::hex('#e3f7ea'), self::hex('#f6fcf8'), 'horizontal');
        Doodles::grid($c, (int) ($h * 0.16), self::hex('#6fc49a'), 0.28);
        Doodles::blob($c, (int) ($w * 0.5), (int) ($h * 0.5), (int) ($w * 0.34), (int) ($h * 0.72), self::hex('#fbfffc'), 0.9, 35);
        Doodles::swipes($c, 0, (int) ($h * 0.6), (int) ($h * 0.7), (int) ($h * 0.4), [self::hex('#3cb480')], 4, 96, -38, 1.0);
        Doodles::graph($c, (int) ($h * 0.2), (int) ($h * 0.16), (int) ($h * 0.95), (int) ($h * 0.68), 'sine', $ink, true, $s);
        if ($wide) {
            Doodles::graph($c, (int) ($w - $h * 1.6), (int) ($h * 0.12), (int) ($h * 1.1), (int) ($h * 0.74), 'parabola', $ink, false);
        }
        $c->text('π', $s, (int) ($h * 0.4), $w - $h * 0.5, $h * 0.42, $ink, 6);
        $c->text('Σ', $s, (int) ($h * 0.32), $w - $h * 0.4, $h * 0.82, self::hex('#2ca066'), -4);
    }

    private static function tTealExamClock(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#d0f1ee'), self::hex('#ecfaf9'), 'horizontal');
        Doodles::wave($c, 0, $w, $h * 0.85, $h * 0.14, 1.8, 0.5, self::hex('#7fd3cb'), 0.5);
        Doodles::wave($c, 0, $w, $h * 1.0, $h * 0.1, 2.4, 1.0, self::hex('#3fb8ae'), 0.65);
        Props::clock($c, (int) ($h * 0.7), (int) ($h * 0.5), (int) ($h * 0.5), self::hex('#1aa59a'));
        Icons::draw($c, 'list', (int) ($w - $h * 0.7), (int) ($h * 0.46), (int) ($h * 0.66), self::hex('#0f5f5a'), 0.05);
        Icons::draw($c, 'pencil', (int) ($w - $h * 0.35), (int) ($h * 0.78), (int) ($h * 0.34), self::hex('#e08a2a'), 0.06);
        if ($wide) {
            Icons::draw($c, 'check', (int) ($h * 1.55), (int) ($h * 0.3), (int) ($h * 0.26), self::hex('#1aa59a'), 0.06);
            Icons::draw($c, 'target', (int) ($w - $h * 1.4), (int) ($h * 0.3), (int) ($h * 0.3), self::hex('#0f5f5a'), 0.05);
        }
        Doodles::dots($c, (int) ($w * 0.9), (int) ($h * 0.06), 5, 3, (int) ($h * 0.085), self::hex('#2a9a92'), 0.5);
    }

    private static function tPlumPremium(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#34195a'), self::hex('#58308a'), 'horizontal');
        Doodles::blob($c, (int) ($w * 0.5), (int) ($h * 0.5), (int) ($w * 0.34), (int) ($h * 0.7), self::hex('#6a3ea0'), 0.3, 36);
        Doodles::ribbon($c, 0, (int) ($w * 0.3), $h * 0.85, $h * 0.14, 1.0, 0.4, $h * 0.04, self::hex('#f4c040'), 0.85);
        Doodles::ribbon($c, (int) ($w * 0.7), $w, $h * 0.3, $h * 0.14, 1.0, 2.4, $h * 0.04, self::hex('#f4c040'), 0.85);
        Doodles::books($c, (int) ($h * 0.15), (int) ($h * 0.98), (int) ($h * 0.9), [[244, 192, 64], [236, 232, 246], [122, 78, 186]], 11);
        Props::gradCap($c, (int) ($w - $h * 0.7), (int) ($h * 0.5), (int) ($h * 0.5), self::hex('#9a6ae0'));
        Doodles::sparkle($c, (int) ($h * 1.25), (int) ($h * 0.22), (int) ($h * 0.08), self::hex('#f4c040'), 0.95);
        Doodles::sparkle($c, (int) ($w - $h * 1.25), (int) ($h * 0.75), (int) ($h * 0.07), self::hex('#f4c040'), 0.9);
        Doodles::dots($c, (int) ($w * 0.88), (int) ($h * 0.7), 5, 3, (int) ($h * 0.085), self::hex('#d8b0f0'), 0.5);
    }

    // ======================================================== subject-themed styles

    private static function tAmberChemistry(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#ffe9cf'), self::hex('#fff6e8'), 'horizontal');
        Doodles::hexes($c, 0, 0, $w, $h, (int) ($h * 0.34), self::hex('#f2b072'), 0.4, self::hex('#f2b072'), 97);
        Doodles::blob($c, (int) ($w * 0.5), (int) ($h * 0.5), (int) ($w * 0.34), (int) ($h * 0.7), self::hex('#fffaf0'), 0.9, 40);
        Doodles::wave($c, 0, $w, $h * 1.0, $h * 0.1, 3.0, 1.0, self::hex('#f4a860'), 0.55);
        Props::flask($c, (int) ($h * 0.6), (int) ($h * 0.98), (int) ($h * 0.78), self::hex('#3aa8e0'), 'round');
        Props::molecule($c, (int) ($w - $h * 0.6), (int) ($h * 0.48), (int) ($h * 0.52), self::hex('#e8742c'));
        if ($wide) {
            Props::flask($c, (int) ($h * 1.35), (int) ($h * 0.98), (int) ($h * 0.62), self::hex('#e85a7a'), 'conical');
            Props::testTubes($c, (int) ($w - $h * 1.75), (int) ($h * 0.98), (int) ($h * 0.7), [self::hex('#8a5be0'), self::hex('#f0a030'), self::hex('#3aa8e0')]);
        }
    }

    private static function tChalkCalculus(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        $chalk = self::hex('#e8f2e4');
        Doodles::wash($c, self::hex('#1d4a3a'), self::hex('#2a6650'), 'horizontal');
        Doodles::blob($c, (int) ($w * 0.5), (int) ($h * 0.5), (int) ($w * 0.34), (int) ($h * 0.7), self::hex('#3a7a62'), 0.25, 41);
        $c->text('∫', $s, (int) ($h * 0.95), $h * 0.18, $h * 0.82, $chalk, 6);
        $c->text('dx', $s, (int) ($h * 0.3), $h * 0.78, $h * 0.78, $chalk, 6);
        $c->text('sin θ', $s, (int) ($h * 0.26), $w - $h * 0.52, $h * 0.38, self::hex('#f6c13a'), 5);
        $c->text('x²', $s, (int) ($h * 0.34), $w - $h * 0.45, $h * 0.78, $chalk, -4);
        if ($wide) {
            Doodles::graph($c, (int) ($w - $h * 1.7), (int) ($h * 0.16), (int) ($h * 1.1), (int) ($h * 0.66), 'sine', $chalk, true, $s);
            $c->text('lim', $s, (int) ($h * 0.2), $h * 1.55, $h * 0.3, self::hex('#f6c13a'));
            $c->text('Δy', $s, (int) ($h * 0.3), $h * 1.62, $h * 0.82, $chalk);
        }
        Doodles::dots($c, (int) ($w * 0.9), (int) ($h * 0.06), 5, 3, (int) ($h * 0.085), $chalk, 0.4);
    }

    private static function tVioletAlgebra(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        $ink = self::hex('#5a3fb0');
        Doodles::wash($c, self::hex('#eadfff'), self::hex('#f8f2ff'), 'horizontal');
        Doodles::wave($c, 0, $w, $h * 0.9, $h * 0.14, 2.0, 0.6, self::hex('#c9b1f4'), 0.55);
        Doodles::blob($c, (int) ($w * 0.5), (int) ($h * 0.5), (int) ($w * 0.34), (int) ($h * 0.7), self::hex('#fbf8ff'), 0.9, 42);
        $c->text('x²', $s, (int) ($h * 0.7), $h * 0.14, $h * 0.62, $ink, 5);
        $c->text('+ y', $s, (int) ($h * 0.3), $h * 0.9, $h * 0.46, self::hex('#8f6ee0'), 4);
        $c->text('√', $s, (int) ($h * 0.7), $w - $h * 0.95, $h * 0.72, $ink, -3);
        $c->text('≠', $s, (int) ($h * 0.38), $w - $h * 0.45, $h * 0.42, self::hex('#8f6ee0'), 4);
        if ($wide) {
            $c->text('a + b = c', $s, (int) ($h * 0.24), $h * 1.4, $h * 0.88, self::hex('#8f6ee0'), 3);
            $c->text('∑', $s, (int) ($h * 0.5), $w - $h * 1.62, $h * 0.6, $ink, -4);
        }
        Doodles::dots($c, (int) ($w * 0.14), (int) ($h * 0.05), 6, 3, (int) ($h * 0.085), self::hex('#9b78d6'), 0.45);
    }

    private static function tInkLetters(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        $bold = new Typography(dirname(__DIR__, 3) . '/resources/fonts/DejaVuSans-Bold.ttf');
        Doodles::wash($c, self::hex('#fff3e0'), self::hex('#fffaf0'), 'horizontal');
        Doodles::blob($c, (int) ($h * 0.8), (int) ($h * 0.95), (int) ($h * 1.3), (int) ($h * 0.55), self::hex('#fbd2a4'), 0.65, 43);
        Doodles::blob($c, (int) ($w * 0.95), (int) ($h * 0.8), (int) ($h * 1.3), (int) ($h * 0.6), self::hex('#f9c8b0'), 0.65, 44);
        SubjectProps::blocks($c, (int) ($h * 0.12), (int) ($h * 0.98), (int) ($h * 0.36), $bold, ['A', 'B', 'C']);
        SubjectProps::quill($c, (int) ($w - $h * 0.5), (int) ($h * 0.5), (int) ($h * 0.75), self::hex('#e8586a'));
        if ($wide) {
            SubjectProps::bubble($c, (int) ($w - $h * 1.55), (int) ($h * 0.3), (int) ($h * 0.38), self::hex('#6a8fe0'));
            SubjectProps::bubble($c, (int) ($h * 2.0), (int) ($h * 0.28), (int) ($h * 0.3), self::hex('#f0a030'), [255, 255, 255], true);
        }
        Doodles::dots($c, (int) ($w * 0.9), (int) ($h * 0.04), 5, 3, (int) ($h * 0.085), self::hex('#d8a060'), 0.5);
    }

    private static function tRoseBubbles(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#ffe3ec'), self::hex('#fff4f7'), 'horizontal');
        Doodles::wave($c, 0, $w, $h * 0.95, $h * 0.12, 2.2, 0.4, self::hex('#f8b0c4'), 0.55);
        SubjectProps::bubble($c, (int) ($h * 0.62), (int) ($h * 0.5), (int) ($h * 0.55), self::hex('#e8527e'));
        SubjectProps::bubble($c, (int) ($h * 1.2), (int) ($h * 0.82), (int) ($h * 0.3), self::hex('#7a5be0'), [255, 255, 255], true);
        Doodles::books($c, (int) ($w - $h * 0.95), (int) ($h * 0.99), (int) ($h * 0.8), [[226, 84, 126], [250, 190, 50], [90, 100, 200]], 8);
        if ($wide) {
            SubjectProps::bubble($c, (int) ($w - $h * 1.0), (int) ($h * 0.3), (int) ($h * 0.32), self::hex('#3aa8d8'), [255, 255, 255], true);
        }
        Doodles::dots($c, (int) ($w * 0.5), (int) ($h * 0.05), 8, 3, (int) ($h * 0.085), self::hex('#e07a96'), 0.4);
    }

    private static function tParchmentHistory(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#f6e6c4'), self::hex('#fcf3dc'), 'horizontal');
        Doodles::blob($c, (int) ($w * 0.5), (int) ($h * 0.5), (int) ($w * 0.36), (int) ($h * 0.7), self::hex('#fff8e6'), 0.7, 45);
        Doodles::blob($c, (int) ($h * 0.6), (int) ($h * 1.0), (int) ($h * 1.5), (int) ($h * 0.5), self::hex('#e8cf9c'), 0.7, 46);
        Doodles::wave($c, 0, $w, $h * 1.0, $h * 0.08, 3.0, 0.4, self::hex('#d8b678'), 0.6);
        SubjectProps::fort($c, (int) ($h * 0.1), (int) ($h * 0.98), (int) ($h * 1.2), self::hex('#c4956a'));
        SubjectProps::scroll($c, (int) ($w - $h * 0.85), (int) ($h * 0.5), (int) ($h * 1.0), -8);
        if ($wide) {
            Doodles::sprig($c, (int) ($w - $h * 1.75), (int) ($h * 1.0), (int) ($h * 0.7), 18, self::hex('#8a6a3a'), true, 6, 0.9, 14);
            Doodles::dots($c, (int) ($h * 1.5), (int) ($h * 0.06), 6, 3, (int) ($h * 0.085), self::hex('#b08850'), 0.5);
        }
    }

    private static function tSepiaPillars(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#f2e1c8'), self::hex('#fbf2e2'), 'horizontal');
        Doodles::blob($c, (int) ($w * 0.5), (int) ($h * 0.5), (int) ($w * 0.34), (int) ($h * 0.7), self::hex('#fff9ec'), 0.8, 47);
        SubjectProps::columns($c, (int) ($h * 0.08), (int) ($h * 0.99), (int) ($h * 1.1), (int) ($h * 0.85), self::hex('#c9a77a'));
        SubjectProps::scroll($c, (int) ($w - $h * 0.8), (int) ($h * 0.45), (int) ($h * 0.95), 10);
        Doodles::sparkle($c, (int) ($h * 1.45), (int) ($h * 0.25), (int) ($h * 0.06), self::hex('#d8a040'), 0.9);
        if ($wide) {
            SubjectProps::columns($c, (int) ($w - $h * 1.9), (int) ($h * 0.99), (int) ($h * 0.8), (int) ($h * 0.7), self::hex('#d0b088'));
        }
        Doodles::dots($c, (int) ($w * 0.3), (int) ($h * 0.05), 6, 3, (int) ($h * 0.085), self::hex('#b08850'), 0.45);
    }

    private static function tTerrainExplorer(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#c9ecf2'), self::hex('#eef9fb'), 'vertical');
        Doodles::wave($c, 0, $w, $h * 1.0, $h * 0.1, 2.4, 0.6, self::hex('#7fc7a0'), 0.8);
        SubjectProps::mountains($c, (int) ($h * 0.05), (int) ($h * 1.0), (int) ($h * 1.0), (int) ($h * 0.7), self::hex('#6a9ab8'));
        SubjectProps::mapPin($c, (int) ($w - $h * 0.4), (int) ($h * 0.92), (int) ($h * 0.6), self::hex('#e0502a'));
        if ($wide) {
            Props::cloud($c, (int) ($h * 1.6), (int) ($h * 0.22), (int) ($h * 0.36));
            Props::cloud($c, (int) ($w - $h * 1.4), (int) ($h * 0.18), (int) ($h * 0.3));
            Icons::draw($c, 'compass', (int) ($w - $h * 1.15), (int) ($h * 0.6), (int) ($h * 0.55), self::hex('#1c5a66'), 0.05);
            Props::globe($c, (int) ($w - $h * 1.95), (int) ($h * 0.5), (int) ($h * 0.34));
        }
    }

    private static function tScalesJustice(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#1b2a5c'), self::hex('#34458f'), 'horizontal');
        Doodles::blob($c, (int) ($w * 0.5), (int) ($h * 0.5), (int) ($w * 0.34), (int) ($h * 0.7), self::hex('#4a5cb0'), 0.28, 48);
        SubjectProps::columns($c, (int) ($h * 0.05), (int) ($h * 0.99), (int) ($h * 0.5), (int) ($h * 0.7), self::hex('#7d8fd8'));
        if ($wide) {
            SubjectProps::scales($c, (int) ($h * 1.05), (int) ($h * 0.96), (int) ($h * 0.82), self::hex('#f4c040'));
        }
        SubjectProps::scales($c, (int) ($w - $h * 0.5), (int) ($h * 0.96), (int) ($h * 0.62), self::hex('#f4c040'));
        Doodles::ribbon($c, 0, $w, $h * 0.95, $h * 0.06, 1.2, 0.5, $h * 0.03, self::hex('#f4c040'), 0.55);
        Doodles::sparkle($c, (int) ($w - $h * 1.0), (int) ($h * 0.3), (int) ($h * 0.07), self::hex('#f4c040'), 0.95);
        Doodles::sparkle($c, (int) ($h * 0.95), (int) ($h * 0.2), (int) ($h * 0.06), self::hex('#ffffff'), 0.8);
    }

    private static function tGoldFinance(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#fff1c9'), self::hex('#fffaea'), 'horizontal');
        Doodles::blob($c, (int) ($h * 0.6), (int) ($h * 0.9), (int) ($h * 1.1), (int) ($h * 0.6), self::hex('#fbdc86'), 0.7, 49);
        Doodles::blob($c, (int) ($w * 0.95), (int) ($h * 0.8), (int) ($h * 1.5), (int) ($h * 0.7), self::hex('#f9d070'), 0.65, 50);
        SubjectProps::coins($c, (int) ($h * 0.5), (int) ($h * 0.98), (int) ($h * 0.36), 6);
        SubjectProps::coins($c, (int) ($h * 1.0), (int) ($h * 0.98), (int) ($h * 0.3), 3);
        SubjectProps::barChart($c, (int) ($w - $h * 1.0), (int) ($h * 0.94), (int) ($h * 0.85), (int) ($h * 0.7), self::hex('#2f8f5a'));
        if ($wide) {
            Doodles::dots($c, (int) ($w - $h * 2.0), (int) ($h * 0.15), 6, 3, (int) ($h * 0.085), self::hex('#d8a020'), 0.5);
            SubjectProps::coins($c, (int) ($w - $h * 1.7), (int) ($h * 0.98), (int) ($h * 0.26), 4);
        }
    }

    private static function tGreenLedger(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#dcf4e4'), self::hex('#f3fbf6'), 'horizontal');
        Doodles::grid($c, (int) ($h * 0.16), self::hex('#8fd0aa'), 0.25);
        Doodles::blob($c, (int) ($w * 0.5), (int) ($h * 0.5), (int) ($w * 0.34), (int) ($h * 0.72), self::hex('#fbfffc'), 0.9, 51);
        Doodles::notebook($c, (int) ($h * 0.18), (int) ($h * 0.16), (int) ($h * 0.82), (int) ($h * 0.78), -6, $s, '', self::hex('#2a2a80'));
        SubjectProps::coins($c, (int) ($h * 1.2), (int) ($h * 0.98), (int) ($h * 0.3), 4);
        SubjectProps::barChart($c, (int) ($w - $h * 1.05), (int) ($h * 0.94), (int) ($h * 0.9), (int) ($h * 0.72), self::hex('#2c9a5c'));
        if ($wide) {
            Props::calculator($c, (int) ($w - $h * 1.95), (int) ($h * 0.22), (int) ($h * 0.5), -8);
        }
    }

    private static function tCircuitTech(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        $glow = self::hex('#3ad0b8');
        Doodles::wash($c, self::hex('#0f1f45'), self::hex('#1c3470'), 'horizontal');
        SubjectProps::circuit($c, 0, 0, (int) ($h * 1.6), $h, $glow, 0.6, 5);
        SubjectProps::circuit($c, (int) ($w - $h * 1.6), 0, (int) ($h * 1.6), $h, $glow, 0.6, 9);
        Doodles::blob($c, (int) ($w * 0.5), (int) ($h * 0.5), (int) ($w * 0.34), (int) ($h * 0.7), self::hex('#2a4a9a'), 0.3, 52);
        SubjectProps::chip($c, (int) ($h * 0.65), (int) ($h * 0.5), (int) ($h * 0.46), self::hex('#3a6ae0'));
        SubjectProps::laptop($c, (int) ($w - $h * 0.72), (int) ($h * 0.95), (int) ($h * 0.68));
        Doodles::dots($c, (int) ($w * 0.9), (int) ($h * 0.06), 5, 3, (int) ($h * 0.085), $glow, 0.6);
    }

    private static function tCodeScreen(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        $ink = self::hex('#2f6ad0');
        Doodles::wash($c, self::hex('#dbe9ff'), self::hex('#f1f6ff'), 'horizontal');
        Doodles::wave($c, 0, $w, $h * 0.95, $h * 0.12, 2.4, 0.3, self::hex('#a9c6f6'), 0.55);
        $c->text('{ }', $s, (int) ($h * 0.62), $h * 0.12, $h * 0.66, $ink, 4);
        if ($wide) {
            SubjectProps::laptop($c, (int) ($h * 1.3), (int) ($h * 0.97), (int) ($h * 0.78));
        }
        $c->text('</>', $s, (int) ($h * 0.46), $w - $h * 1.0, $h * 0.5, $ink, -4);
        $c->text('0 1', $s, (int) ($h * 0.3), $w - $h * 0.78, $h * 0.86, self::hex('#7aa6ee'), 3);
        if ($wide) {
            Icons::draw($c, 'target', (int) ($w - $h * 1.9), (int) ($h * 0.35), (int) ($h * 0.3), $ink, 0.05);
        }
        Doodles::dots($c, (int) ($w * 0.5), (int) ($h * 0.05), 8, 3, (int) ($h * 0.085), self::hex('#7aa6ee'), 0.45);
    }

    private static function tBrainCalm(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#ece0ff'), self::hex('#f9f4ff'), 'horizontal');
        Doodles::blob($c, (int) ($h * 0.7), (int) ($h * 0.5), (int) ($h * 1.1), (int) ($h * 0.7), self::hex('#d8c2f6'), 0.7, 53);
        Doodles::wave($c, 0, $w, $h * 0.95, $h * 0.12, 2.0, 0.8, self::hex('#c4a8f0'), 0.5);
        SubjectProps::brain($c, (int) ($h * 0.75), (int) ($h * 0.5), (int) ($h * 0.46), self::hex('#f08aa8'));
        SubjectProps::heart($c, (int) ($w - $h * 0.7), (int) ($h * 0.5), (int) ($h * 0.3), self::hex('#e8527e'));
        if ($wide) {
            Doodles::bulb($c, (int) ($w - $h * 1.35), (int) ($h * 0.36), (int) ($h * 0.32), self::hex('#f7c030'), true);
            SubjectProps::heart($c, (int) ($h * 1.55), (int) ($h * 0.25), (int) ($h * 0.12), self::hex('#f08aa8'));
            SubjectProps::heart($c, (int) ($w - $h * 1.7), (int) ($h * 0.78), (int) ($h * 0.1), self::hex('#8f6ee0'));
        }
        Doodles::dots($c, (int) ($w * 0.5), (int) ($h * 0.05), 8, 3, (int) ($h * 0.085), self::hex('#9b78d6'), 0.4);
    }

    private static function tPaletteSplash(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#fff1f4'), self::hex('#fffaf0'), 'horizontal');
        foreach ([[0.05, 0.3, '#f26a7a', 0.55], [0.1, 0.9, '#f6b823', 0.6], [0.93, 0.25, '#3aa0e8', 0.55], [0.97, 0.85, '#8a5be0', 0.55]] as $i => [$fx, $fy, $hex, $sz]) {
            Doodles::blob($c, (int) ($w * $fx + ($fx < 0.5 ? $h * 0.5 : -$h * 0.5)), (int) ($h * $fy), (int) ($h * $sz * 1.4), (int) ($h * $sz), self::hex($hex), 0.65, 60 + $i);
        }
        SubjectProps::palette($c, (int) ($h * 0.85), (int) ($h * 0.5), (int) ($h * 0.52));
        SubjectProps::brush($c, (int) ($w - $h * 0.75), (int) ($h * 0.5), (int) ($h * 1.1), self::hex('#e8527e'), -55);
        if ($wide) {
            SubjectProps::brush($c, (int) ($w - $h * 1.55), (int) ($h * 0.55), (int) ($h * 0.95), self::hex('#3aa0e8'), -40);
            Doodles::dots($c, (int) ($h * 1.8), (int) ($h * 0.08), 6, 3, (int) ($h * 0.085), self::hex('#e88aa0'), 0.5);
        }
    }

    private static function tCrayonBright(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#fff6dc'), self::hex('#fffbee'), 'horizontal');
        Doodles::swipes($c, 0, 0, (int) ($h * 1.4), $h, [self::hex('#ee5a6a'), self::hex('#f6b823'), self::hex('#3aa0e8')], 8, 71, -38, 1.0);
        Doodles::swipes($c, (int) ($w - $h * 1.4), 0, (int) ($h * 1.4), $h, [self::hex('#6cc04a'), self::hex('#8a5be0'), self::hex('#f6b823')], 8, 72, -38, 1.0);
        Doodles::blob($c, (int) ($w * 0.5), (int) ($h * 0.5), (int) ($w * 0.34), (int) ($h * 0.7), self::hex('#fffdf4'), 0.85, 54);
        Doodles::pencilCup($c, (int) ($h * 0.62), (int) ($h * 0.98), (int) ($h * 0.55));
        SubjectProps::palette($c, (int) ($w - $h * 0.7), (int) ($h * 0.52), (int) ($h * 0.5));
    }

    private static function tPuzzleBulb(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        $bold = new Typography(dirname(__DIR__, 3) . '/resources/fonts/DejaVuSans-Bold.ttf');
        Doodles::wash($c, self::hex('#ffe9c8'), self::hex('#fff6e6'), 'horizontal');
        Doodles::blob($c, (int) ($w * 0.5), (int) ($h * 0.5), (int) ($w * 0.34), (int) ($h * 0.7), self::hex('#fffaf0'), 0.85, 55);
        Doodles::blob($c, (int) ($h * 0.7), (int) ($h * 0.9), (int) ($h * 1.2), (int) ($h * 0.55), self::hex('#fbc888'), 0.7, 56);
        SubjectProps::puzzle($c, (int) ($h * 0.55), (int) ($h * 0.55), (int) ($h * 0.36), self::hex('#6a8fe0'), -10);
        SubjectProps::puzzle($c, (int) ($h * 1.0), (int) ($h * 0.35), (int) ($h * 0.26), self::hex('#e8527e'), 14);
        Doodles::bulb($c, (int) ($w - $h * 0.75), (int) ($h * 0.46), (int) ($h * 0.46), self::hex('#f7c030'), true);
        if ($wide) {
            $c->text('?', $bold, (int) ($h * 0.5), $w - $h * 1.55, $h * 0.55, self::hex('#e8742c'), 8);
            $c->text('?', $bold, (int) ($h * 0.3), $h * 1.55, $h * 0.85, self::hex('#6a8fe0'), -10);
            $c->text('?', $bold, (int) ($h * 0.34), $w - $h * 2.0, $h * 0.35, self::hex('#e8527e'), 12);
        }
    }

    private static function tSportsField(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#bfe8cf'), self::hex('#e9f8ee'), 'vertical');
        Doodles::wave($c, 0, $w, $h * 0.92, $h * 0.1, 2.0, 0.5, self::hex('#6cc088'), 0.8);
        Doodles::wave($c, 0, $w, $h * 1.02, $h * 0.08, 2.6, 1.4, self::hex('#4aa86a'), 0.9);
        Doodles::ribbon($c, 0, $w, $h * 0.74, $h * 0.02, 1.2, 0.3, 3, self::hex('#ffffff'), 0.8);
        SubjectProps::ball($c, (int) ($h * 0.62), (int) ($h * 0.55), (int) ($h * 0.42));
        SubjectProps::medal($c, (int) ($w - $h * 0.8), (int) ($h * 0.44), (int) ($h * 0.4));
        Doodles::sparkle($c, (int) ($h * 1.35), (int) ($h * 0.22), (int) ($h * 0.06), self::hex('#f6b823'), 1.0);
        if ($wide) {
            Props::trophy($c, (int) ($w - $h * 1.75), (int) ($h * 0.96), (int) ($h * 0.7));
            SubjectProps::ball($c, (int) ($h * 1.5), (int) ($h * 0.8), (int) ($h * 0.2));
        }
    }

    private static function tDefenceShield(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#26365a'), self::hex('#3e5280'), 'horizontal');
        Doodles::blob($c, (int) ($w * 0.5), (int) ($h * 0.5), (int) ($w * 0.34), (int) ($h * 0.7), self::hex('#586ea0'), 0.28, 57);
        Doodles::swipes($c, 0, (int) ($h * 0.5), (int) ($h * 0.9), (int) ($h * 0.5), [self::hex('#7a8a52')], 5, 73, -38, 1.0);
        Doodles::swipes($c, (int) ($w - $h * 0.5), (int) ($h * 0.55), (int) ($h * 0.5), (int) ($h * 0.45), [self::hex('#7a8a52')], 4, 74, -38, 1.0);
        SubjectProps::shield($c, (int) ($h * 0.72), (int) ($h * 0.5), (int) ($h * 0.6), self::hex('#2a4a9a'));
        SubjectProps::medal($c, (int) ($w - $h * 0.7), (int) ($h * 0.48), (int) ($h * 0.42));
        Doodles::sparkle($c, (int) ($h * 1.5), (int) ($h * 0.22), (int) ($h * 0.06), self::hex('#f4c040'), 0.95);
        Doodles::sparkle($c, (int) ($w - $h * 1.4), (int) ($h * 0.72), (int) ($h * 0.06), self::hex('#ffffff'), 0.85);
    }

    private static function tWaterGreen(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#c9f0ee'), self::hex('#ecfaf8'), 'vertical');
        Doodles::wave($c, 0, $w, $h * 0.8, $h * 0.12, 2.0, 0.4, self::hex('#8fdcd4'), 0.6);
        Doodles::wave($c, 0, $w, $h * 0.95, $h * 0.1, 2.6, 1.4, self::hex('#4cc0b4'), 0.75);
        SubjectProps::waterDrop($c, (int) ($h * 0.6), (int) ($h * 0.88), (int) ($h * 0.7), self::hex('#2f9ae0'));
        SubjectProps::sprout($c, (int) ($w - $h * 0.5), (int) ($h * 0.97), (int) ($h * 0.66));
        if ($wide) {
            SubjectProps::waterDrop($c, (int) ($h * 1.15), (int) ($h * 0.7), (int) ($h * 0.3), self::hex('#6ac0f0'));
            SubjectProps::cycle($c, (int) ($w - $h * 1.55), (int) ($h * 0.32), (int) ($h * 0.22), self::hex('#2aa38a'));
            Props::cloud($c, (int) ($h * 1.85), (int) ($h * 0.22), (int) ($h * 0.3));
        }
    }

    private static function tEarthClean(Canvas $c, int $w, int $h, Typography $s, bool $wide): void
    {
        Doodles::wash($c, self::hex('#d6f1de'), self::hex('#f1fbf4'), 'horizontal');
        Doodles::wave($c, 0, $w, $h * 0.85, $h * 0.14, 1.8, 0.2, self::hex('#a6dcb8'), 0.6);
        Doodles::wave($c, 0, $w, $h * 1.0, $h * 0.1, 2.4, 1.0, self::hex('#6cc08a'), 0.8);
        Props::globe($c, (int) ($h * 0.6), (int) ($h * 0.5), (int) ($h * 0.46));
        SubjectProps::cycle($c, (int) ($w - $h * 0.5), (int) ($h * 0.48), (int) ($h * 0.4), self::hex('#2f9a5a'));
        if ($wide) {
            Props::cloud($c, (int) ($w - $h * 1.5), (int) ($h * 0.2), (int) ($h * 0.3));
            Doodles::sprig($c, (int) ($h * 1.7), (int) ($h * 1.0), (int) ($h * 0.7), 22, self::hex('#2b6b2f'), false, 6, 0.95, 15);
            SubjectProps::sprout($c, (int) ($w - $h * 1.7), (int) ($h * 0.97), (int) ($h * 0.6));
        }
    }
}
