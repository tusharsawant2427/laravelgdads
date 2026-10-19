<?php

/**
 * Renders a gallery of sample ads into the given directory (default: examples/output).
 *
 *   php examples/generate.php [output-dir]
 *
 * The book covers are synthesised with GD (portrait packshot, white studio background, baked drop
 * shadow, like a publisher's catalogue image) so the example needs no image assets.
 */

require __DIR__ . '/../vendor/autoload.php';

use Packitifotech\Laragdads\Ads\AdBuilder;
use Packitifotech\Laragdads\Ads\AdSize;
use Packitifotech\Laragdads\Ads\Support\Canvas;
use Packitifotech\Laragdads\Ads\Support\Typography;

$out = $argv[1] ?? __DIR__ . '/output';
@mkdir("$out/assets", 0775, true);
$font = dirname(__DIR__) . '/resources/fonts/DejaVuSans-Bold.ttf';

/** A fake exam-prep book cover on a white background with a soft drop shadow. */
function book(string $path, string $font, array $top, array $bottom, string $title, string $subject, bool $back = false): void
{
    $type = new Typography($font);
    $c = Canvas::create(500, 700, [255, 255, 255]);
    $bx = 46; $by = 34; $bw = 408; $bh = 620;

    $c->shadow($bx, $by, $bw, $bh, 4, 34, 0.45, 18);
    $c->gradient($top, $bottom, 'diagonal', [$bx, $by, $bw, $bh]);
    $c->rect($bx, $by + $bh - 130, $bw, 130, [255, 255, 255]);                       // white footer, like the real covers

    if ($back) {
        $lines = ['Ordinary Books Help You Study.', 'THESE BOOKS HELP YOU WIN.'];
        $c->lines([$lines[0]], $type, 17, $bx + 20, $by + 24, $bw - 40, [255, 255, 255], 'center');
        $c->lines([$lines[1]], $type, 24, $bx + 20, $by + 52, $bw - 40, [255, 255, 255], 'center');
        foreach ([0, 1, 2, 3] as $i) {
            $c->rect($bx + 24 + $i * 92, $by + 120, 84, 118, [255, 255, 255], 0.9, 4);
            $c->rect($bx + 24 + $i * 92, $by + 120, 84, 30, [[255, 120, 0], [30, 160, 90], [20, 120, 200], [220, 40, 80]][$i], 1.0, 4);
        }
        $c->rect($bx + 24, $by + 290, $bw - 48, 110, [255, 244, 160], 1.0, 6);
    } else {
        $c->rect($bx + 28, $by + 26, $bw - 56, 74, [250, 230, 40], 1.0, 6);
        $c->lines([$title], $type, 52, $bx + 28, $by + 36, $bw - 56, [40, 30, 90], 'center');
        $c->rect($bx, $by + 128, $bw, 100, [20, 150, 110]);
        $c->lines([$subject], $type, 54, $bx + 20, $by + 150, $bw - 40, [255, 255, 255], 'center');
        foreach (range(0, 5) as $i) {
            $c->rect($bx + 24, $by + 262 + $i * 34, 230, 24, [255, 255, 255], 0.22, 4);
        }
        $c->circle($bx + 300, $by + 330, 120, [255, 255, 255], 0.2);
        $c->circle($bx + 330, $by + 300, 70, [255, 180, 230], 0.5);
        $c->rect($bx + $bw - 150, $by + 258, 130, 54, [30, 170, 120], 1.0, 24);
    }

    $c->lines(['Target Publications'], $type, 26, $bx + 20, $by + $bh - 90, $bw - 40, [200, 20, 40], 'center');
    imagepng($c->image(), $path);
}

book("$out/assets/chemistry.png", $font, [150, 40, 150], [90, 20, 110], 'MHT-CET', 'CHEMISTRY');
book("$out/assets/back.png", $font, [130, 40, 140], [80, 20, 100], '', '', true);
book("$out/assets/physics.png", $font, [40, 90, 170], [20, 40, 110], 'MHT-CET', 'PHYSICS');

$ad = fn () => AdBuilder::make()
    ->font($font)
    ->headline('MHT-CET Triumph Chemistry')
    ->subheadline('Chapter-wise notes and MCQs for Engineering & Pharmacy entrance')
    ->features(['6400+ chapter-wise MCQs', 'Previous years\' questions (PYQs)', 'Model question papers', 'Solutions via QR codes'])
    ->cta('Buy now')
    ->badge('6400+ MCQs')
    ->images("$out/assets/chemistry.png");

$jobs = [
    'landscape-front' => $ad()->size(AdSize::LANDSCAPE),
    'landscape-front-back' => $ad()->size(AdSize::LANDSCAPE)->images("$out/assets/chemistry.png", "$out/assets/back.png"),
    'strip-lines' => AdBuilder::make()->font($font)->size(AdSize::STRIP)->images("$out/assets/chemistry.png", "$out/assets/back.png")->background('lines')
        ->headline('JEE Main Mathematics Absolute Vol 2 Notes with MCQs | As per latest syllabus issued by NTA'),
    'landscape-right' => $ad()->size(AdSize::LANDSCAPE)->imageSide('right')->images("$out/assets/physics.png", "$out/assets/chemistry.png"),
    'square' => $ad()->size(AdSize::SQUARE)->images("$out/assets/chemistry.png", "$out/assets/back.png"),
    'story' => $ad()->size(AdSize::STORY)->images("$out/assets/chemistry.png", "$out/assets/back.png"),
    'portrait-card' => $ad()->size(AdSize::PORTRAIT)->layout('stacked')->imageFit('contain'),
    'billboard' => $ad()->size(AdSize::BILLBOARD)->images("$out/assets/chemistry.png", "$out/assets/physics.png")->subheadline(null)->badge('NEW'),
    'leaderboard' => $ad()->size(AdSize::LEADERBOARD)->images("$out/assets/chemistry.png", "$out/assets/physics.png")->subheadline(null)->badge(null),
    'medium-rectangle' => $ad()->size(AdSize::MEDIUM_RECTANGLE),
    'half-page' => $ad()->size(AdSize::HALF_PAGE),
    'mobile-banner' => $ad()->size(AdSize::MOBILE_BANNER)->subheadline(null)->cta(null),
    'brand-colour' => $ad()->size(AdSize::LANDSCAPE)->theme('#0f766e'),
    'blur-bg' => $ad()->size(AdSize::LANDSCAPE)->background('blur'),
    'no-image' => AdBuilder::make()->font($font)->size(AdSize::LANDSCAPE)->headline('MHT-CET 2026 exam prep')->subheadline('Mock tests, PYQs and solutions in one place')->features(['Full mock tests', 'Chapter-wise MCQs'])->cta('Start practising')->badge('-30%'),
];

foreach ($jobs as $name => $job) {
    $t = microtime(true);
    $job->save("$out/$name.png");
    printf("%-22s %5.0f ms\n", $name, (microtime(true) - $t) * 1000);
}
