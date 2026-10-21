<?php

/**
 * Illustrated banner templates: 20 light, hand-drawn style backgrounds with the product, headline and button in the middle.
 *
 *   php examples/templates.php [output-dir]
 *
 * Writes every template as a 1200x200 strip with the Physics cover, plus the bare backgrounds.
 */

require __DIR__ . '/../vendor/autoload.php';

use Packitifotech\Laragdads\Ads\AdBuilder;
use Packitifotech\Laragdads\Ads\AdSize;
use Packitifotech\Laragdads\Ads\Support\Canvas;
use Packitifotech\Laragdads\Ads\Templates\TemplateLibrary;

$out = $argv[1] ?? __DIR__ . '/output';
@mkdir("$out/backgrounds", 0775, true);
$cover = __DIR__ . '/assets/lHZj2P6ls9N8WVqGXbHKUbutNz6EVdQLXOPtVKTp.webp';

foreach (TemplateLibrary::names() as $i => $name) {
    $n = sprintf('%02d-%s', $i + 1, $name);

    // The bare background at 2000x256 (use it as a plain image behind your own layout).
    $c = Canvas::create(2000, 256, [255, 255, 255]);
    TemplateLibrary::paint($name, $c);
    imagepng($c->image(), "$out/backgrounds/$n.png");

    // A complete ad.
    AdBuilder::make()->size(AdSize::STRIP)->images($cover)->template($name)
        ->headline('MHT-CET Physics: 28 Years Previous Solved Papers')
        ->subheadline('Chapter-wise papers from 1999 to 2026')
        ->cta('Buy now')
        ->save("$out/ad-$n.png");
    echo "$n\n";
}
