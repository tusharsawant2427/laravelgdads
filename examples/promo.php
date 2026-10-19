<?php

/**
 * Website-hero style promo built from a catalogue photo.
 *
 *   php examples/promo.php [output-dir]
 *
 * Uses examples/assets/*.webp (a marketplace image with a title banner on top: it is cropped off first).
 * Pass your own cover with the SSC_COVER environment variable.
 */

require __DIR__ . '/../vendor/autoload.php';

use Packitifotech\Laragdads\Ads\AdBuilder;
use Packitifotech\Laragdads\Ads\AdSize;
use Packitifotech\Laragdads\Ads\Support\ImageLoader;

$out = $argv[1] ?? __DIR__ . '/output';
@mkdir($out, 0775, true);

$src = getenv('SSC_COVER') ?: (glob(__DIR__ . '/assets/*.webp')[0] ?? null);
if ($src === null) {
    fwrite(STDERR, "Put a cover image in examples/assets/ or set SSC_COVER.\n");
    exit(1);
}

// Cut the marketing banner (top 132px) and the white margins off the catalogue photo.
$cover = ImageLoader::crop($src, top: 132, left: 42, right: 52);

// Latin + Devanagari: use any font that has the glyphs you need (Noto Sans Devanagari, FreeSans, ...).
$bold = '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf';
$regular = '/usr/share/fonts/truetype/freefont/FreeSans.ttf';

$ad = AdBuilder::make()
    ->images($cover)->cutout(false)->book3d()
    ->hero('SSC 54', 'QUESTION PAPERS', 'Activity Sheets With Solutions')
    ->details([
        ['Model Papers for **SSC Board Exam**', 1.35],
        ['Based on the latest **Maharashtra Board Paper Pattern** for Year **2025-26**', 1.2],
        '---',
        'English Kumarbharati  |  हिंदी लोकभारती  |  मराठी अक्षरभारती',
        'History and Political Science  |  Geography',
        'Science and Technology Part - 1 & 2  |  Mathematics Part - I & II',
        '---',
        '• Get the real exam experience  • Detailed insights',
        '• Mark Booster features  • Separate Booklet for Solutions',
    ])
    ->seal(['INCLUDES', '2025', 'BOARD QUESTION PAPERS'])
    ->tag('Std. X', 'ENGLISH MEDIUM')
    ->cta("Ace your exam with SSC Exam Topper's Answer Sheet")->ctaStyle('arrow');

if (is_file($bold) && is_file($regular)) {
    $ad->font($bold, $regular);
}

// ->logo($brandLogo)->footerLogo($publisherLogo) add the brand marks (transparent PNGs work best).
foreach (['promo' => AdSize::WEBSITE_BANNER, 'promo-square' => AdSize::SQUARE, 'promo-story' => AdSize::STORY] as $name => $size) {
    $t = microtime(true);
    (clone $ad)->size($size)->save("$out/$name.png");
    printf("%-14s %4.0f ms\n", $name, (microtime(true) - $t) * 1000);
}
