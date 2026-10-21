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

$out = $argv[1] ?? __DIR__ . '/output';
@mkdir($out, 0775, true);

$physics = __DIR__ . '/assets/lHZj2P6ls9N8WVqGXbHKUbutNz6EVdQLXOPtVKTp.webp';

$physicsAd = fn () => AdBuilder::make()->images($physics)
    ->headline('MHT-CET Physics: 28 Years Previous Solved Papers')
    ->subheadline('Chapter-wise and topic-wise papers from 1999 to 2026')
    ->features(['4100+ MCQs', 'Quick Review', 'Important Formulae'])
    ->cta('Buy now')->badge('4100+ MCQs');

// Topic-aware scenes: the AI picks an educational backdrop from the ad's own wording (physics -> physics concept /
// science lab ...). Seeds give different scenes; the layout and all text are drawn by the library.
$jobs = [
    'physics-square-a' => $physicsAd()->size(AdSize::SQUARE)->aiBackground('education', seed: 0),
    'physics-square-b' => $physicsAd()->size(AdSize::SQUARE)->aiBackground('education', seed: 1),
    'physics-story' => $physicsAd()->size(AdSize::STORY)->aiBackground('education', seed: 5),
    'physics-landscape' => $physicsAd()->size(AdSize::LANDSCAPE)->aiBackground('education', seed: 2),
    'physics-strip' => $physicsAd()->size(AdSize::STRIP)->subheadline(null)->features([])->badge(null)->aiBackground('education', seed: 3),
];

echo 'Education scenes: ' . implode(', ', AiStyles::educationNames()) . "\n";
foreach ($jobs as $name => $job) {
    $t = microtime(true);
    $job->save("$out/ai-$name.png");
    $ai = $job->lastAi();
    printf("%-26s %5.0fs  %s\n", $name, microtime(true) - $t, $ai ? 'AI style: ' . $ai['style'] : 'FALLBACK: ' . $job->lastAiError());
}
