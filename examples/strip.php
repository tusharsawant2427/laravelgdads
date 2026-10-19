<?php

/**
 * Horizontal 1200x200 strips from the original, uncropped cover photos, on educational AI backgrounds.
 *
 *   AIHORDE_API_KEY=your-key php examples/strip.php [output-dir]
 *
 * The cover photos are used exactly as supplied (no crop, no cut-out): each one is fitted whole inside a card.
 */

require __DIR__ . '/../vendor/autoload.php';

use Packitifotech\Laragdads\Ads\AdBuilder;
use Packitifotech\Laragdads\Ads\AdSize;

$out = $argv[1] ?? __DIR__ . '/output';
@mkdir($out, 0775, true);

$ssc = __DIR__ . '/assets/TZMbt8W59NljPG8KbKu1MnpA5Yi3hktXovs2iwqz.webp';
$physics = __DIR__ . '/assets/lHZj2P6ls9N8WVqGXbHKUbutNz6EVdQLXOPtVKTp.webp';

$strip = fn (string $cover) => AdBuilder::make()
    ->size(AdSize::STRIP)                 // 1200 x 200
    ->images($cover)->imageFit('contain')->cutout(false);

$jobs = [];
foreach ([0, 1, 2] as $seed) {
    $jobs["ssc-$seed"] = $strip($ssc)->headline('SSC Board Exam Model Papers')
        ->subheadline('Latest Maharashtra Board paper pattern 2025-26')->cta('Buy now')
        ->aiBackground('education', seed: $seed);
    $jobs["physics-$seed"] = $strip($physics)->headline('MHT-CET Physics: 28 Years Previous Solved Papers')
        ->subheadline('Chapter-wise papers from 1999 to 2026')->cta('Buy now')
        ->aiBackground('education', seed: $seed);
}

foreach ($jobs as $name => $job) {
    $t = microtime(true);
    $job->save("$out/strip-$name.png");
    $ai = $job->lastAi();
    printf("%-12s %4.0fs  %s\n", $name, microtime(true) - $t, $ai ? 'scene: ' . $ai['style'] : 'FALLBACK: ' . $job->lastAiError());
}
