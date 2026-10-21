<?php

/**
 * One ad per subject group, using the templates that suit each, as a printable overview.
 *
 *   php examples/subjects.php [output-dir]
 *
 * Writes ad-<group>-<template>.png for every template of every group, plus subjects.md with the full subject table.
 */

require __DIR__ . '/../vendor/autoload.php';

use Packitifotech\Laragdads\Ads\AdBuilder;
use Packitifotech\Laragdads\Ads\AdSize;
use Packitifotech\Laragdads\Ads\Templates\Subjects;
use Packitifotech\Laragdads\Ads\Templates\TemplateLibrary;

$out = $argv[1] ?? __DIR__ . '/output';
@mkdir($out, 0775, true);
$cover = __DIR__ . '/assets/lHZj2P6ls9N8WVqGXbHKUbutNz6EVdQLXOPtVKTp.webp';

$titles = [
    'physics' => 'Physics', 'chemistry' => 'Chemistry', 'biology' => 'Biology', 'maths' => 'Mathematics',
    'maths-advanced' => 'Algebra, Trigonometry, Calculus', 'languages' => 'English, Hindi, Marathi', 'history' => 'History',
    'geography' => 'Geography', 'civics' => 'Political Science and Sociology', 'commerce' => 'Accounts and Economics',
    'computer' => 'Computer Science', 'psychology' => 'Psychology and Value Education', 'art' => 'Art and Craft',
    'reasoning' => 'General Knowledge and Reasoning', 'sports' => 'Physical Education and Defence Studies',
    'environment' => 'EVS and Water Security', 'general' => 'All Subjects',
];

foreach (TemplateLibrary::byGroup() as $group => $templates) {
    foreach ($templates as $name) {
        AdBuilder::make()->size(AdSize::STRIP)->images($cover)->template($name)
            ->headline($titles[$group] . ' Notes')->subheadline('Chapter-wise notes and practice papers')->cta('Buy now')
            ->save("$out/ad-$group-$name.png");
    }
    printf("%-15s %d templates\n", $group, count($templates));
}

// Markdown table: every subject label -> group -> templates.
$md = "| Subject | Group | Templates |\n| --- | --- | --- |\n";
foreach (Subjects::catalogue() as $subject => $groups) {
    $md .= sprintf("| %s | %s | %s |\n", $subject, implode(', ', $groups), implode(', ', TemplateLibrary::forSubject($subject)));
}
file_put_contents("$out/subjects.md", $md);
