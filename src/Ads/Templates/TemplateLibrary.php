<?php

namespace Packitifotech\Laragdads\Ads\Templates;

use Packitifotech\Laragdads\Ads\Support\Canvas;
use Packitifotech\Laragdads\Ads\Support\Color;
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
        'sky-atom' => ['ink' => '#1e3a6e', 'accent' => '#1d5fd8', 'zone' => [1.45, 0.9]],
        'peach-leaves' => ['ink' => '#4a2e1c', 'accent' => '#e8742c', 'zone' => [1.55, 1.5]],
        'lavender-bubbles' => ['ink' => '#3b2470', 'accent' => '#7c4dd8', 'zone' => [1.3, 1.2]],
        'aqua-hex' => ['ink' => '#0f4a66', 'accent' => '#0e9ac0', 'zone' => [0.5, 0.5]],
        'yellow-brush' => ['ink' => '#3d3020', 'accent' => '#e9a800', 'zone' => [1.5, 1.5]],
        'lavender-books' => ['ink' => '#2f2a70', 'accent' => '#5b4fd8', 'zone' => [0.5, 1.2]],
        'doodle-grid' => ['ink' => '#2b2a6b', 'accent' => '#2f6ce0', 'zone' => [2.15, 2.45], 'compact' => [1.1, 1.5]],
        'coral-waves' => ['ink' => '#5a2a3a', 'accent' => '#e8566f', 'zone' => [0.5, 0.9]],
        'green-leaves' => ['ink' => '#1f4a28', 'accent' => '#3f9a3e', 'zone' => [1.3, 1.3]],
        'blue-waves-atom' => ['ink' => '#173a72', 'accent' => '#2a74e0', 'zone' => [0.5, 1.4]],
        'books-emc2' => ['ink' => '#2b2a6b', 'accent' => '#e9a000', 'zone' => [1.15, 2.0], 'compact' => [1.15, 1.15]],
        'lavender-notebook' => ['ink' => '#33276e', 'accent' => '#7a4fd6', 'zone' => [0.5, 2.3], 'compact' => [0.5, 1.35]],
        'cradle-blue' => ['ink' => '#1c3a6a', 'accent' => '#2f7be0', 'zone' => [1.5, 1.6], 'compact' => [1.5, 1.0]],
        'peach-physics' => ['ink' => '#46301c', 'accent' => '#e57a2a', 'zone' => [2.0, 1.4], 'compact' => [1.2, 1.4]],
        'magnet-purple' => ['ink' => '#37236e', 'accent' => '#7d4bd6', 'zone' => [1.75, 1.3]],
        'green-graph' => ['ink' => '#1c4a30', 'accent' => '#2c9a5c', 'zone' => [1.55, 1.45]],
        'bulb-leaves' => ['ink' => '#4a2e1a', 'accent' => '#e7791f', 'zone' => [2.0, 1.0]],
        'aqua-atom' => ['ink' => '#123c6c', 'accent' => '#2c86d8', 'zone' => [0.5, 1.4]],
        'prism-rainbow' => ['ink' => '#2b2f78', 'accent' => '#5a5fe0', 'zone' => [2.3, 1.0], 'compact' => [1.95, 1.0]],
        'yellow-projectile' => ['ink' => '#3d3018', 'accent' => '#e8a200', 'zone' => [1.5, 2.2], 'compact' => [1.5, 1.15]],
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
    public static function zone(string $name, int $w, int $h): array
    {
        $p = self::PALETTE[$name] ?? throw new \InvalidArgumentException("Unknown template [{$name}].");
        [$l, $r] = ($w / max(1, $h) < self::WIDE && isset($p['compact'])) ? $p['compact'] : $p['zone'];

        return [(int) round($h * $l), $w - (int) round($h * $r)];
    }

    /** @return array{ink:array,accent:array,zone:array{0:float,1:float}} */
    public static function palette(string $name): array
    {
        $p = self::PALETTE[$name] ?? throw new \InvalidArgumentException("Unknown template [{$name}]. Available: " . implode(', ', self::names()) . '.');

        return ['ink' => Color::fromHex($p['ink']), 'accent' => Color::fromHex($p['accent']), 'zone' => $p['zone']];
    }

    public static function paint(string $name, Canvas $c, ?Typography $script = null): void
    {
        self::palette($name);   // validates the name
        $w = $c->width();
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
        Doodles::wave($c, 0, (int) ($w * 0.5), $h * 0.5, $h * 0.28, 1.0, 0.3, self::hex('#b190ee'), 0.65, true, $h * 0.04);
        Doodles::wave($c, 0, (int) ($w * 0.36), $h * 0.78, $h * 0.18, 1.3, 1.0, self::hex('#8f7be8'), 0.7);
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
        Doodles::magnet($c, (int) ($h * 0.85), (int) ($h * 0.5), (int) ($h * 0.52), self::hex('#3a2f9a'));
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
}
