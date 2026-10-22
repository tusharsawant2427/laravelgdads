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
    'neet' => ['NEET', null],
    'mht-cet' => ['MHT-CET', null],
    'cuet' => ['CUET', null],
    'entrance-exams' => ['Entrance Exams', null],
    'category' => ['Physics', 'Notes, MCQs and previous year papers'],
    'category-orange' => ['Std. X Maths', 'Chapter-wise practice for board exams'],
    'pre-primary' => ['Blossom Nursery Rhymes and Alphabet Book', null],
    'kids-activity' => ['Drawing and Colouring Books for Kids | Level 5', null],
    'primary' => ['Std. 3 Computers Workbook', null],
    'foundation' => ['Maths Foundation Series Book 2', null],
    'multi-subject' => ['Std. 6 All Subjects Perfect Notes', 'Maharashtra Board 2026-2027'],
    'early-learning' => ['Logical Thinking and Life Skills Book', null],
    'kindergarten' => ['Senior KG Fun Book', null],
    'handwriting' => ['Std. 2 English Handwriting Book', null],
    'gk-kids' => ['General Knowledge Book 5', null],
    'general' => ['Std. XI Computer Science', 'As per the latest syllabus'],
];

foreach (TemplateLibrary::names() as $i => $name) {
    if (!in_array($name, ['general', 'category', 'category-orange'], true) && TemplateLibrary::familyOf($name) !== null) {
        continue;   // the colour variants are chosen by the cover's colour; 'general' stands for all ten
    }
    [$w, $h] = TemplateLibrary::dimensions($name, 1200);
    [$title, $tagline] = $titles[$name];
    AdBuilder::make()->size($w, $h)->images($covers[$i % count($covers)])->template($name)
        ->headline($title)->subheadline($tagline)
        ->font($argv[2] ?? __DIR__ . '/../resources/fonts/DejaVuSans-Bold.ttf')
        ->save("$out/$name.png");
    echo "$name {$w}x{$h}\n";
}
