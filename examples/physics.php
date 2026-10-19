<?php

/**
 * Ads for the MHT-CET Physics cover in examples/assets:  php examples/physics.php [output-dir]
 */

require __DIR__ . '/../vendor/autoload.php';
use Packitifotech\Laragdads\Ads\{AdBuilder,AdSize};
$out = $argv[1] ?? __DIR__ . '/output';
@mkdir($out, 0775, true);
$cover = getenv('PHYSICS_COVER') ?: __DIR__ . '/assets/lHZj2P6ls9N8WVqGXbHKUbutNz6EVdQLXOPtVKTp.webp';
$ad = fn () => AdBuilder::make()->images($cover)->book3d()
    ->hero('MHT-CET', 'PHYSICS', '28 Years Solved Papers')
    ->headline('MHT-CET Physics: 28 Years Previous Solved Papers')
    ->subheadline('Chapter-wise and topic-wise papers from 1999 to 2026')
    ->features(['4100+ MCQs', 'Quick Review', 'Important Formulae', 'Smart Keys and PSP Lens'])
    ->details([
        ['**Previous Solved Papers** from **1999 to 2026**', 1.3],
        ['Chapter-wise and Topic-wise', 1.1],
        '---',
        '• Quick Review  • Important Formulae',
        '• Smart Keys  • PSP Lens',
        '---',
        ['**4100+ MCQs** with solutions', 1.1],
    ])
    ->seal(['WITH', '4100+', 'MCQs'])->tag('28 Years', '1999 - 2026')
    ->cta('Buy now')->badge('4100+ MCQs');
$jobs = [
  'promo' => $ad()->size(AdSize::WEBSITE_BANNER)->cta('Practise with 4100+ MCQs')->ctaStyle('arrow'),
  'landscape' => $ad()->size(AdSize::LANDSCAPE)->layout('books'),
  'square' => $ad()->size(AdSize::SQUARE)->layout('books'),
  'story' => $ad()->size(AdSize::STORY)->layout('books'),
  'strip' => $ad()->size(AdSize::STRIP)->layout('showcase')->subheadline(null)->features([])->badge(null),
  'leaderboard' => $ad()->size(AdSize::LEADERBOARD)->layout('showcase')->subheadline(null)->features([])->badge(null),
  'medium-rectangle' => $ad()->size(AdSize::MEDIUM_RECTANGLE)->layout('books'),
];
foreach ($jobs as $n => $b) { $t=microtime(true); $b->save("$out/physics-$n.png"); printf("%-18s %4.0f ms\n",$n,(microtime(true)-$t)*1000); }
