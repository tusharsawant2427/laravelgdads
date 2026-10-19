<?php

/**
 * Light, illustrated "companion" ads (handwritten tagline on a brush stroke, sticker title, icon cards, slogan blob).
 *
 *   php examples/companion.php [output-dir]
 */

require __DIR__ . '/../vendor/autoload.php';

use Packitifotech\Laragdads\Ads\AdBuilder;
use Packitifotech\Laragdads\Ads\AdSize;
use Packitifotech\Laragdads\Ads\Support\ImageLoader;

$out = $argv[1] ?? __DIR__ . '/output';
@mkdir($out, 0775, true);

$cover = __DIR__ . '/assets/lHZj2P6ls9N8WVqGXbHKUbutNz6EVdQLXOPtVKTp.webp';
// Stand-in for a real publisher logo file (use a transparent PNG): the wordmark cropped from the cover footer.
$logo = ImageLoader::crop($cover, top: 548, left: 225, right: 60, bottom: 115);

$ad = fn () => AdBuilder::make()->images($cover)->logo($logo)
    ->tagline('Your Complete Practice Companion for')
    ->hero('MHT-CET', 'PHYSICS')                                   // sticker title + dark subject pill
    ->highlights([                                                 // [icon, title, subtitle]
        ['book', '28 YEARS', 'Previous Solved Papers'],
        ['doc', 'Chapter-wise & Topic-wise', 'Organisation'],
        ['list', '4100+', 'MCQs'],
        ['bulb', 'Quick Review, Important Formulae & Smart Keys', 'for Last-Minute Prep'],
    ])
    ->slogan('Concepts', 'Practice', 'Confidence');                // handwritten, on a blob, with subject doodles

// ->artwork($pngOnWhite) melts a picture (e.g. an AI-made study desk) into the bottom right corner.
foreach (['companion' => AdSize::WEBSITE_BANNER, 'companion-strip' => AdSize::STRIP] as $name => $size) {
    $t = microtime(true);
    $ad()->size($size)->save("$out/$name.png");
    printf("%-16s %4.1fs\n", $name, microtime(true) - $t);
}
