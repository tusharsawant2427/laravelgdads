<?php

/**
 * Ads with AI-generated educational backgrounds (AI Horde / Stable Horde), different scene and arrangement for each:
 *
 *   AIHORDE_API_KEY=your-key php examples/ai.php [output-dir]
 *
 * The cover, title, seal and buttons are drawn by the layout; only the background art comes from the AI,
 * so nothing has to be spelled by the model. Without a key the anonymous key is used (slow queue).
 * Without network access every ad falls back to the built-in background.
 */

require __DIR__ . '/../vendor/autoload.php';

use Packitifotech\Laragdads\Ads\Ai\AiStyles;
use Packitifotech\Laragdads\Ads\AdBuilder;
use Packitifotech\Laragdads\Ads\AdSize;
use Packitifotech\Laragdads\Ads\Support\ImageLoader;

$out = $argv[1] ?? __DIR__ . '/output';
@mkdir($out, 0775, true);

$ssc = ImageLoader::crop(__DIR__ . '/assets/TZMbt8W59NljPG8KbKu1MnpA5Yi3hktXovs2iwqz.webp', top: 132, left: 42, right: 52);
$physics = __DIR__ . '/assets/lHZj2P6ls9N8WVqGXbHKUbutNz6EVdQLXOPtVKTp.webp';
$freeBold = '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf';
$freeRegular = '/usr/share/fonts/truetype/freefont/FreeSans.ttf';

$sscPromo = fn () => AdBuilder::make()->size(AdSize::WEBSITE_BANNER)->images($ssc)->cutout(false)->book3d()
    ->hero('SSC 54', 'QUESTION PAPERS', 'Activity Sheets With Solutions')
    ->details([
        ['Model Papers for **SSC Board Exam**', 1.35],
        ['Based on the latest **Maharashtra Board Paper Pattern** for Year **2025-26**', 1.2],
        '---',
        'History and Political Science  |  Geography',
        'Science and Technology Part - 1 & 2  |  Mathematics Part - I & II',
        '---',
        '• Get the real exam experience  • Detailed insights',
    ])
    ->seal(['INCLUDES', '2025', 'BOARD QUESTION PAPERS'])->tag('Std. X', 'ENGLISH MEDIUM')
    ->cta("Ace your exam with SSC Exam Topper's Answer Sheet")->ctaStyle('arrow');

$physicsAd = fn () => AdBuilder::make()->images($physics)->book3d()
    ->headline('MHT-CET Physics: 28 Years Previous Solved Papers')
    ->subheadline('Chapter-wise and topic-wise papers from 1999 to 2026')
    ->features(['4100+ MCQs', 'Quick Review', 'Important Formulae'])
    ->cta('Buy now')->badge('4100+ MCQs');

// Topic-aware scenes: the AI picks an educational backdrop from the ad's own wording (physics -> physics concept /
// science lab, SSC board papers -> exam hall / classroom ...), and variant() changes the arrangement and alignment.
$jobs = [
    'ssc-promo' => $sscPromo()->aiBackground('education', seed: 0),
    'ssc-promo-2' => $sscPromo()->aiBackground('education', seed: 1),
    'physics-landscape-a' => $physicsAd()->size(AdSize::LANDSCAPE)->layout('books')->variant(0)->aiBackground('education', seed: 0),
    'physics-landscape-b' => $physicsAd()->size(AdSize::LANDSCAPE)->layout('books')->variant(1)->aiBackground('education', seed: 1),
    'physics-landscape-c' => $physicsAd()->size(AdSize::LANDSCAPE)->layout('books')->variant(2)->aiBackground('education', seed: 2),
    'physics-square-top' => $physicsAd()->size(AdSize::SQUARE)->layout('books')->variant(0)->aiBackground('education', seed: 3),
    'physics-square-bottom' => $physicsAd()->size(AdSize::SQUARE)->layout('books')->variant(1)->aiBackground('education', seed: 4),
    'physics-story' => $physicsAd()->size(AdSize::STORY)->layout('books')->variant(2)->aiBackground('education', seed: 5),
];

if (is_file($freeBold) && is_file($freeRegular)) {
    foreach (['ssc-promo', 'ssc-promo-2'] as $name) {
        $jobs[$name]->font($freeBold, $freeRegular);
    }
}

echo 'Education scenes: ' . implode(', ', AiStyles::educationNames()) . "\n";
foreach ($jobs as $name => $job) {
    $t = microtime(true);
    $job->save("$out/ai-$name.png");
    $ai = $job->lastAi();
    printf("%-26s %5.0fs  %s\n", $name, microtime(true) - $t, $ai ? 'AI style: ' . $ai['style'] : 'FALLBACK: ' . $job->lastAiError());
}
