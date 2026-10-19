<?php

/**
 * 1200x200 advertisement strips from the original, uncropped cover photos.
 *
 *   php examples/strip.php [output-dir]                      # designed backgrounds only (no network)
 *   AIHORDE_API_KEY=your-key php examples/strip.php [dir]     # also AI art backgrounds (AI Horde)
 *
 * Layout: whole cover in a card with a seal, a two-line stacked title, two info lines, a plaque and an arrow button.
 */

require __DIR__ . '/../vendor/autoload.php';

use Packitifotech\Laragdads\Ads\AdBuilder;
use Packitifotech\Laragdads\Ads\AdSize;

$out = $argv[1] ?? __DIR__ . '/output';
@mkdir($out, 0775, true);

$ssc = __DIR__ . '/assets/TZMbt8W59NljPG8KbKu1MnpA5Yi3hktXovs2iwqz.webp';
$physics = __DIR__ . '/assets/lHZj2P6ls9N8WVqGXbHKUbutNz6EVdQLXOPtVKTp.webp';

$sscAd = fn () => AdBuilder::make()->size(AdSize::STRIP)->images($ssc)->cutout(false)
    ->hero('SSC 54', 'QUESTION PAPERS')
    ->details([['Model Papers for **SSC Board Exam**', 1.25], ['Latest **Maharashtra Board** pattern **2025-26**', 1.0]])
    ->seal(['INCLUDES', '2025', 'BOARD PAPERS'])->tag('Std. X', 'ENGLISH MEDIUM')
    ->cta('Get the book')->ctaStyle('arrow');

$physicsAd = fn () => AdBuilder::make()->size(AdSize::STRIP)->images($physics)->cutout(false)
    ->hero('MHT-CET', 'PHYSICS')
    ->details([['**28 Years** Previous Solved Papers', 1.25], ['From **1999 to 2026**  •  **4100+ MCQs**', 1.0]])
    ->seal(['WITH', '4100+', 'MCQs'])->tag('28 Years', '1999 - 2026')
    ->cta('Get the book')->ctaStyle('arrow');

$jobs = ['design-ssc' => $sscAd(), 'design-physics' => $physicsAd()];

if (getenv('AIHORDE_API_KEY')) {
    // AI art behind the same layout: education-themed art, not photos (the layout draws all text).
    $jobs['ai-ssc-blueprint'] = $sscAd()->aiBackground('blueprint-science');
    $jobs['ai-ssc-chalk'] = $sscAd()->aiBackground('chalkboard-doodle');
    $jobs['ai-physics-blueprint'] = $physicsAd()->aiBackground('blueprint-science');
    $jobs['ai-physics-fluid'] = $physicsAd()->aiBackground('fluid-gradient');
}

foreach ($jobs as $name => $job) {
    $t = microtime(true);
    $job->save("$out/strip-$name.png");
    $ai = $job->lastAi();
    printf("%-22s %4.0fs  %s\n", $name, microtime(true) - $t, $ai ? 'AI: ' . $ai['style'] : ($job->lastAiError() ? 'FALLBACK: ' . $job->lastAiError() : 'designed background'));
}
