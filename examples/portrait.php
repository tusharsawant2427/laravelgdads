<?php

/**
 * The illustrated templates as a 336x453 portrait ad.
 *
 *   php examples/portrait.php [output-dir]
 *
 * Writes every template with the Physics cover.
 */

require __DIR__ . '/../vendor/autoload.php';

use Packitifotech\Laragdads\Ads\AdBuilder;
use Packitifotech\Laragdads\Ads\Templates\TemplateLibrary;

$out = $argv[1] ?? __DIR__ . '/output';
@mkdir($out, 0775, true);
$cover = __DIR__ . '/assets/lHZj2P6ls9N8WVqGXbHKUbutNz6EVdQLXOPtVKTp.webp';

foreach (TemplateLibrary::names() as $i => $name) {
    AdBuilder::make()->size(336, 453)->images($cover)->template($name)
        ->headline('Physics Notes')
        ->subheadline('Chapter-wise notes and practice papers')
        ->cta('Buy now')
        ->save(sprintf('%s/portrait-%02d-%s.png', $out, $i + 1, $name));
}
echo "done\n";
