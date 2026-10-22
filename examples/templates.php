<?php

/**
 * The subject banner templates: one banner per template, with a product cover and title.
 *
 *   php examples/templates.php [output-dir] [font.ttf]
 */

if (is_file(__DIR__ . '/../vendor/autoload.php')) { require __DIR__ . '/../vendor/autoload.php'; }

use Packitifotech\Laragdads\Ads\AdBuilder;
use Packitifotech\Laragdads\Ads\Templates\TemplateLibrary;

$out = $argv[1] ?? __DIR__ . '/output';
@mkdir($out, 0775, true);
$covers = glob(__DIR__ . '/assets/*.webp');

$titles = [
    'mathematics' => ['Std. X Mathematics Part 1 Notes', 'Algebra and Geometry for the Maharashtra Board'],
    'science' => ['Std. X Science and Technology Part 1', null],
    'english' => ['Std. IX English Kumarbharati Workbook with Grammar and Writing Practice', null],
    'hindi' => ['Std. VIII Hindi Sulabhbharati', null],
    'marathi' => ['Std. X Marathi Kumarbharati', null],
    'social-science' => ['Std. X History and Political Science Part 1', null],
    'physics' => ['Std. XII Physics Part 1', 'Chapter-wise notes and numericals'],
    'chemistry' => ['MHT-CET Triumph Chemistry: Chapter-wise Notes and 6400+ MCQs', null],
    'biology' => ['Std. XII Biology Part 1', null],
    'evs-gk' => ['EVS and General Knowledge Activity Book for Kids', null],
    'general' => ['Std. XI Computer Science', 'As per the latest syllabus'],
];

foreach (TemplateLibrary::names() as $i => $name) {
    [$w, $h] = TemplateLibrary::dimensions($name, 1200);
    [$title, $tagline] = $titles[$name];
    AdBuilder::make()->size($w, $h)->images($covers[$i % count($covers)])->template($name)
        ->headline($title)->subheadline($tagline)
        ->font($argv[2] ?? __DIR__ . '/../resources/fonts/DejaVuSans-Bold.ttf')
        ->save("$out/$name.png");
    echo "$name {$w}x{$h}\n";
}
