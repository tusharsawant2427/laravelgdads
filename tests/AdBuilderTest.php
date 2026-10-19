<?php

namespace Packitifotech\Laragdads\Tests;

use GdImage;
use InvalidArgumentException;
use Packitifotech\Laragdads\Ads\Ai\AiBackground;
use Packitifotech\Laragdads\Ads\Ai\AiStyles;
use Packitifotech\Laragdads\Ads\AdBuilder;
use Packitifotech\Laragdads\Ads\AdSize;
use Packitifotech\Laragdads\Ads\Backgrounds;
use Packitifotech\Laragdads\Ads\Support\Canvas;
use Packitifotech\Laragdads\Ads\Support\Color;
use Packitifotech\Laragdads\Ads\Support\Cutout;
use Packitifotech\Laragdads\Ads\Support\ImageLoader;
use Packitifotech\Laragdads\Ads\Support\RichText;
use Packitifotech\Laragdads\Ads\Support\Typography;
use Packitifotech\Laragdads\Ads\Theme;
use PHPUnit\Framework\TestCase;

final class AdBuilderTest extends TestCase
{
    private string $dir;
    private string $font;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/laragdads-' . uniqid();
        mkdir($this->dir);
        AiBackground::useCacheDir($this->dir . '/ai-cache');
        $this->font = dirname(__DIR__) . '/resources/fonts/DejaVuSans-Bold.ttf';
    }

    protected function tearDown(): void
    {
        AiBackground::useGenerator(null);
        AiBackground::useCacheDir(null);
        foreach (glob($this->dir . '/ai-cache/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->dir . '/ai-cache');
        array_map('unlink', array_filter(glob($this->dir . '/*') ?: [], 'is_file'));
        @rmdir($this->dir);
    }

    /** Portrait book-like packshot on a white background with a grey shadow, white footer inside the book. */
    private function packshot(string $name = 'book.png'): string
    {
        $c = Canvas::create(200, 280, [255, 255, 255]);
        $c->shadow(20, 16, 160, 240, 2, 14, 0.5, 8);
        $c->rect(20, 16, 160, 240, [140, 30, 150]);
        $c->rect(20, 206, 160, 50, [255, 255, 255]);
        $path = $this->dir . '/' . $name;
        imagepng($c->image(), $path);

        return $path;
    }

    private function decode(string $binary): GdImage
    {
        $image = imagecreatefromstring($binary);
        $this->assertInstanceOf(GdImage::class, $image);

        return $image;
    }

    public function test_every_size_and_layout_renders_at_the_requested_dimensions(): void
    {
        $book = $this->packshot();
        foreach (AdSize::cases() as $size) {
            foreach (['auto', 'books', 'split', 'stacked', 'hero', 'showcase'] as $layout) {
                $png = AdBuilder::make()->size($size)->layout($layout)->images($book, $book)
                    ->headline('Triumph Chemistry')->subheadline('Notes and MCQs')->features(['6400+ MCQs', 'PYQs'])
                    ->cta('Buy now')->badge('-30%')->toPng();

                $img = $this->decode($png);
                $this->assertSame([$size->width(), $size->height()], [imagesx($img), imagesy($img)], "{$size->value}/{$layout}");
            }
        }
    }

    public function test_headline_only_ad_without_images_renders(): void
    {
        $img = $this->decode(AdBuilder::make()->size(AdSize::SQUARE)->headline('Sale')->cta('Go')->toPng());
        $this->assertSame(1080, imagesx($img));
    }

    public function test_an_empty_ad_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        AdBuilder::make()->render();
    }

    public function test_unknown_layout_and_format_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        AdBuilder::make()->headline('x')->layout('nope')->render();
    }

    public function test_formats_produce_the_right_file_signatures(): void
    {
        $ad = fn () => AdBuilder::make()->size(300, 250)->headline('Hello world');
        $this->assertStringStartsWith("\x89PNG", $ad()->toPng());
        $this->assertStringStartsWith("\xFF\xD8", $ad()->toJpg());
        $this->assertSame('RIFF', substr($ad()->toWebp(), 0, 4));
        $this->assertStringStartsWith('data:image/png;base64,', $ad()->toDataUri());
    }

    public function test_save_writes_a_file_and_creates_directories(): void
    {
        $path = AdBuilder::make()->size(300, 250)->headline('Hi')->save($this->dir . '/nested/ad.png');
        $this->assertFileExists($path);
        unlink($path);
        rmdir(dirname($path));
    }

    public function test_render_sizes_returns_one_ad_per_placement_without_mutating_the_builder(): void
    {
        $builder = AdBuilder::make()->headline('Hello')->size(AdSize::SQUARE);
        $out = $builder->renderSizes([AdSize::LEADERBOARD, [300, 250]]);

        $this->assertSame(['728x90', '300x250'], array_keys($out));
        $this->assertSame(1080, imagesx($this->decode($builder->toPng())));
    }

    public function test_cta_button_uses_the_accent_colour(): void
    {
        $theme = Theme::fromColors('#1e3a8a', null, '#facc15');
        $img = $this->decode(AdBuilder::make()->size(600, 400)->theme($theme)->layout('stacked')->headline('Big sale')->cta('Buy now')->toPng());

        $found = false;
        for ($y = 0; $y < 400 && !$found; $y += 2) {
            for ($x = 0; $x < 600; $x += 2) {
                if (Color::toHex($this->rgb($img, $x, $y)) === $this->hexOf($theme->accent)) {
                    $found = true;
                    break;
                }
            }
        }
        $this->assertTrue($found, 'accent coloured CTA pixels expected');
    }

    private function rgb(GdImage $img, int $x, int $y): array
    {
        $c = imagecolorat($img, $x, $y);

        return [($c >> 16) & 255, ($c >> 8) & 255, $c & 255];
    }

    private function hexOf(array $c): string
    {
        return Color::toHex($c);
    }

    public function test_cutout_removes_white_background_but_keeps_white_inside_the_product(): void
    {
        $src = imagecreatefrompng($this->packshot());
        $this->assertTrue(Cutout::hasLightBackground($src));

        $cut = Cutout::apply($src);
        $alpha = fn (int $x, int $y) => (imagecolorat($cut, $x, $y) >> 24) & 0x7F;

        $this->assertSame(127, $alpha(0, 0), 'corner is transparent');
        $this->assertLessThan(imagesx($src), imagesx($cut), 'cropped to content');

        // The white footer sits inside the book: it must stay opaque white.
        $found = false;
        for ($y = imagesy($cut) - 8; $y > imagesy($cut) - 30 && !$found; $y--) {
            for ($x = 10; $x < imagesx($cut) - 10; $x += 5) {
                $c = imagecolorat($cut, $x, $y);
                if ((($c >> 24) & 0x7F) === 0 && ($c & 0xFFFFFF) === 0xFFFFFF) {
                    $found = true;
                    break;
                }
            }
        }
        $this->assertTrue($found, 'white footer survives the cutout');
    }

    public function test_photo_without_light_border_is_not_cut_out(): void
    {
        $c = Canvas::create(100, 100, [20, 60, 120]);
        $this->assertFalse(Cutout::hasLightBackground($c->image()));
    }

    public function test_theme_prefers_the_dominant_brand_hue_and_keeps_text_readable(): void
    {
        $theme = Theme::fromImage($this->packshot());
        $this->assertGreaterThanOrEqual(4.5, Color::contrast($theme->backgroundFrom, $theme->text));
        $this->assertGreaterThanOrEqual(4.5, Color::contrast($theme->backgroundTo, $theme->text));
        $this->assertGreaterThanOrEqual(3.0, Color::contrast($theme->accent, $theme->backgroundFrom));
        $this->assertLessThan(40, Color::hueDistance($theme->primary, [140, 30, 150]));
    }

    public function test_light_brand_colour_gets_dark_text(): void
    {
        $theme = Theme::fromColors('#fde68a');
        $this->assertSame([17, 24, 39], $theme->text);
        $this->assertGreaterThanOrEqual(4.5, Color::contrast($theme->backgroundFrom, $theme->text));
    }

    public function test_color_helpers(): void
    {
        $this->assertSame([255, 0, 128], Color::fromHex('#ff0080'));
        $this->assertSame([255, 255, 255], Color::fromHex('fff'));
        $this->assertEqualsWithDelta(21.0, Color::contrast([0, 0, 0], [255, 255, 255]), 0.01);
        [$h, $s, $l] = Color::toHsl([255, 0, 0]);
        $this->assertSame([255, 0, 0], Color::fromHsl($h, $s, $l));
        $this->expectException(InvalidArgumentException::class);
        Color::fromHex('nope');
    }

    public function test_typography_fits_text_inside_its_box(): void
    {
        $type = new Typography($this->font);
        $fit = $type->fit('A rather long headline that has to wrap onto several lines', 300, 120, 80, 10, 1.2);

        $this->assertLessThanOrEqual(120, $fit['height']);
        $this->assertLessThanOrEqual(300, $fit['width']);
        $this->assertGreaterThan(1, count($fit['lines']));
    }

    public function test_typography_ellipsises_when_nothing_fits(): void
    {
        $type = new Typography($this->font);
        $fit = $type->fit(str_repeat('word ', 80), 120, 20, 40, 12, 1.2);

        $this->assertCount(1, $fit['lines']);
        $this->assertStringEndsWith('…', $fit['lines'][0]);
    }

    public function test_missing_font_and_image_fail_loudly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        AdBuilder::make()->headline('x')->font('/no/such/font.ttf')->render();
    }

    public function test_custom_background_painter_is_used(): void
    {
        Backgrounds::register('red', fn (Canvas $c) => $c->rect(0, 0, $c->width(), $c->height(), [255, 0, 0]));
        $img = $this->decode(AdBuilder::make()->size(100, 100)->background('red')->layout('stacked')->headline('.')->toPng());

        $this->assertSame([255, 0, 0], $this->rgb($img, 1, 1));
        $this->assertContains('red', Backgrounds::names());
    }

    public function test_legacy_background_styles_still_render(): void
    {
        $book = $this->packshot();
        foreach (['wave1', 'stripes', 'radial', 'spotlight', 'burst', 'medical'] as $style) {
            $img = $this->decode(AdBuilder::make()->size(400, 300)->images($book)->background($style)->layout('split')->headline('Hi')->toPng());
            $this->assertSame(400, imagesx($img), $style);
        }
    }

    public function test_pattern_backgrounds_render_and_differ_from_plain_mesh(): void
    {
        $book = $this->packshot();
        $render = fn (string $bg) => AdBuilder::make()->size(AdSize::STRIP)->images($book, $book)->background($bg)->headline('Hi')->toPng();

        $mesh = $render('mesh');
        foreach (['studio', 'waves', 'lines', 'bokeh', 'hex'] as $style) {
            $this->assertSame(1200, imagesx($this->decode($render($style))), $style);
            $this->assertNotSame($mesh, $render($style), $style);
        }
        $this->assertSame($render('lines'), $render('lines'), 'patterns are deterministic');
    }

    public function test_default_background_is_the_rich_studio_style(): void
    {
        $book = $this->packshot();
        $ad = fn (?string $bg) => ($bg ? AdBuilder::make()->background($bg) : AdBuilder::make())
            ->size(AdSize::LANDSCAPE)->images($book)->headline('Hi')->toPng();

        $this->assertSame($ad('studio'), $ad(null));
        $this->assertNotSame($ad('mesh'), $ad(null));
    }

    public function test_title_with_pipe_is_split_into_headline_and_subheadline(): void
    {
        $split = AdBuilder::make()->size(AdSize::STRIP)->headline('Maths Vol 2 | As per latest NTA syllabus')->toPng();
        $explicit = AdBuilder::make()->size(AdSize::STRIP)->headline('Maths Vol 2')->subheadline('As per latest NTA syllabus')->toPng();

        $this->assertSame($explicit, $split);
    }

    public function test_theme_from_front_and_back_cover_follows_the_front_cover(): void
    {
        $front = $this->packshot('front.png');
        $c = Canvas::create(200, 280, [255, 255, 255]);
        $c->rect(20, 16, 160, 240, [20, 150, 110]);
        imagepng($c->image(), $this->dir . '/back.png');

        $theme = Theme::fromImages([$front, $this->dir . '/back.png']);
        $this->assertLessThan(40, Color::hueDistance($theme->primary, [140, 30, 150]));
    }

    public function test_promo_layout_renders_wide_and_falls_back_on_narrow_sizes(): void
    {
        $book = $this->packshot();
        $ad = fn (AdSize $size) => AdBuilder::make()->size($size)->images($book)->cutout(false)->book3d()
            ->hero('SSC 54', 'QUESTION PAPERS', 'Activity Sheets With Solutions')
            ->details([['Model Papers for **SSC Board Exam**', 1.3], '---', 'History | Geography', '• Mock tests  • Solutions'])
            ->seal(['INCLUDES', '2025', 'BOARD QUESTION PAPERS'])->tag('Std. X', 'ENGLISH MEDIUM')
            ->cta('Ace your exam')->ctaStyle('arrow')->toPng();

        foreach ([AdSize::WEBSITE_BANNER, AdSize::SQUARE, AdSize::STORY, AdSize::LEADERBOARD] as $size) {
            $img = $this->decode($ad($size));
            $this->assertSame([$size->width(), $size->height()], [imagesx($img), imagesy($img)], $size->value);
        }
    }

    public function test_rich_text_wraps_and_marks_bold_words(): void
    {
        $rich = new RichText(new Typography($this->font), new Typography($this->font));
        $lines = $rich->wrap('Model Papers for **SSC Board Exam** today', 22, 260);

        $this->assertGreaterThan(1, count($lines));
        $bold = array_filter(array_merge(...$lines), fn ($w) => $w[1]);
        $this->assertSame(['SSC', 'Board', 'Exam'], array_values(array_map(fn ($w) => $w[0], $bold)));
        foreach ($lines as $line) {
            $this->assertLessThanOrEqual(260, $rich->width($line, 22));
        }
    }

    public function test_crop_removes_edges_and_rejects_empty_results(): void
    {
        $cropped = ImageLoader::crop($this->packshot(), top: 10, left: 20, right: 30, bottom: 40);
        $this->assertSame([150, 230], [imagesx($cropped), imagesy($cropped)]);

        $this->expectException(InvalidArgumentException::class);
        ImageLoader::crop($this->packshot(), top: 280);
    }

    public function test_product_running_off_the_image_edge_is_not_cut_out(): void
    {
        $c = Canvas::create(200, 280, [255, 255, 255]);
        $c->rect(30, 20, 140, 260, [30, 90, 160]);           // runs off the bottom edge
        $this->assertFalse(Cutout::hasLightBackground($c->image()));
    }

    public function test_book_mockup_adds_spine_and_pages(): void
    {
        $book = \Packitifotech\Laragdads\Ads\Support\BookMockup::make($this->packshot());
        $this->assertGreaterThan(200, imagesx($book));
        $this->assertGreaterThan(280, imagesy($book));
    }

    public function test_theme_follows_the_largest_colour_area_not_the_most_saturated_banner(): void
    {
        $c = Canvas::create(200, 280, [255, 255, 255]);
        $c->rect(20, 16, 160, 240, [140, 40, 130]);            // big purple cover
        $c->rect(60, 40, 90, 22, [235, 90, 20]);                // small, very saturated orange banner
        $path = $this->dir . '/cover.png';
        imagepng($c->image(), $path);

        $theme = Theme::fromImage($path);
        $this->assertLessThan(30, Color::hueDistance($theme->primary, [140, 40, 130]));
        $this->assertLessThan(40, Color::hueDistance($theme->accent, [235, 90, 20]), 'the banner colour becomes the accent');
    }

    private function brightImage(int $w, int $h, array $rgb = [250, 250, 245]): GdImage
    {
        return Canvas::create($w, $h, $rgb)->image();
    }

    public function test_ai_background_is_requested_once_cached_and_painted_with_a_readable_scrim(): void
    {
        $calls = [];
        AiBackground::useGenerator(function (string $prompt, int $w, int $h, array $o) use (&$calls) {
            $calls[] = [$prompt, $w, $h];

            return $this->brightImage($w, $h);     // a very bright model result: text would be unreadable on it
        });

        $ad = fn () => AdBuilder::make()->size(AdSize::LANDSCAPE)->headline('Hello world')->theme('#4c1d95')
            ->aiBackground('watercolor', prompt: 'unit-test prompt ' . $this->dir)->textPanel(false);
        $img = $this->decode($ad()->toPng());
        $ad()->toPng();

        $this->assertCount(1, $calls, 'second render uses the disk cache');
        $this->assertSame([768, 448], [$calls[0][1], $calls[0][2]], 'landscape source size');

        [$r, $g, $b] = $this->rgb($img, 5, 5);
        $this->assertLessThan(150, ($r + $g + $b) / 3, 'scrim darkens a bright AI image so white text stays readable');
    }

    public function test_ai_failure_falls_back_unless_strict(): void
    {
        AiBackground::useGenerator(fn () => throw new \RuntimeException('horde down'));
        $builder = AdBuilder::make()->size(300, 250)->headline('Hi')->aiBackground('random', prompt: 'fail ' . $this->dir);

        $this->assertStringStartsWith("\x89PNG", $builder->toPng());
        $this->assertNull($builder->lastAi());
        $this->assertSame('horde down', $builder->lastAiError());

        $this->expectExceptionMessage('horde down');
        AdBuilder::make()->size(300, 250)->headline('Hi')->aiBackground('random', prompt: 'fail2 ' . $this->dir, strict: true)->render();
    }

    public function test_ai_styles_are_random_but_reproducible_and_prompts_ask_for_no_text(): void
    {
        $this->assertSame(AiStyles::resolve('random', 3), AiStyles::resolve('random', 3));
        $this->assertNotSame(AiStyles::resolve('random', 0), AiStyles::resolve('random', 1));
        foreach (AiStyles::names() as $style) {
            $prompt = AiStyles::prompt($style, ['deep purple', 'vivid orange']);
            $this->assertStringContainsString('deep purple', $prompt);
            $this->assertStringContainsString('no text', $prompt);
        }
        $this->assertSame([1152, 192], AiBackground::dimensions(6.0));
        $this->assertSame([1024, 256], AiBackground::dimensions(4.0));
        $this->assertSame([960, 320], AiBackground::dimensions(3.03));
        $this->assertSame([768, 448], AiBackground::dimensions(1.9));
        $this->assertSame([448, 768], AiBackground::dimensions(0.56));
        $this->assertSame([640, 640], AiBackground::dimensions(1.0));
        $this->assertSame('deep purple', Color::name([70, 20, 110]));
    }

    public function test_horde_sizes_are_snapped_to_multiples_of_64(): void
    {
        $this->assertSame(512, \Packitifotech\Laragdads\Ads\Ai\HordeClient::snap(500));
        $this->assertSame(64, \Packitifotech\Laragdads\Ads\Ai\HordeClient::snap(10));
    }

    public function test_education_scenes_follow_the_ad_topic(): void
    {
        $this->assertContains(AiStyles::forTopic('MHT-CET Physics: 28 years', 0), ['physics-concept', 'science-lab']);
        $this->assertContains(AiStyles::forTopic('Chemistry notes', 1), ['chemistry-lab', 'science-lab']);
        $this->assertContains(AiStyles::forTopic('SSC 54 QUESTION PAPERS', 2), ['exam-hall', 'classroom', 'study-desk']);
        $this->assertContains(AiStyles::forTopic('something unrelated', 3), AiStyles::educationNames());
        $this->assertContains(AiStyles::resolve('education', 0, 'Biology'), ['biology-lab', 'library']);
        $this->assertContains(AiStyles::resolve('random', 4), AiStyles::educationNames());
        $this->assertContains(AiStyles::resolve('abstract', 4), AiStyles::artNames());
        $this->assertSame('watercolor', AiStyles::resolve('watercolor'));
        $this->assertStringContainsString('no people', AiStyles::prompt('classroom', ['deep blue']));
        foreach (AiStyles::educationNames() as $scene) {
            $wide = AiStyles::prompt($scene, ['deep purple'], true);
            $this->assertStringContainsString('wide panoramic', $wide, $scene);
            $this->assertStringContainsString('no text', $wide, $scene);
        }
    }

    public function test_builder_derives_the_scene_from_headline_and_hero_not_the_detail_list(): void
    {
        $seen = [];
        AiBackground::useGenerator(function (string $prompt, int $w, int $h) use (&$seen) {
            $seen[] = $prompt;

            return $this->brightImage($w, $h, [60, 40, 90]);
        });

        AdBuilder::make()->size(AdSize::LANDSCAPE)->theme('#4c1d95')->hero('SSC 54', 'QUESTION PAPERS')
            ->details(['Mathematics Part - I & II', 'Physics and Chemistry'])
            ->aiBackground('education', seed: 0, topic: null)->toPng();

        $this->assertMatchesRegularExpression('/exam|classroom|desk/', $seen[0]);
        $this->assertDoesNotMatchRegularExpression('/geometry|compass|laboratory/', $seen[0]);
    }

    public function test_variants_change_the_arrangement_and_are_reproducible(): void
    {
        $book = $this->packshot();
        $render = fn (int $v) => AdBuilder::make()->size(AdSize::LANDSCAPE)->layout('books')->images($book)
            ->headline('MHT-CET Physics')->subheadline('Previous solved papers')->cta('Buy now')->variant($v)->toPng();

        $this->assertSame($render(0), $render(0));
        $this->assertNotSame($render(0), $render(1), 'product side differs');
        $this->assertNotSame($render(0), $render(2), 'text alignment differs');

        $portrait = fn (int $v) => AdBuilder::make()->size(AdSize::SQUARE)->layout('books')->images($book)
            ->headline('MHT-CET Physics')->cta('Buy now')->variant($v)->toPng();
        $this->assertNotSame($portrait(0), $portrait(1), 'product on top vs bottom');
    }

    public function test_text_panel_is_automatic_with_ai_backgrounds_and_can_be_turned_off(): void
    {
        AiBackground::useGenerator(fn (string $p, int $w, int $h) => $this->brightImage($w, $h, [90, 120, 80]));
        $ad = fn (?bool $panel) => AdBuilder::make()->size(AdSize::LANDSCAPE)->layout('books')->images($this->packshot())
            ->headline('Hello world')->cta('Go')->aiBackground('classroom', prompt: 'panel test ' . $this->dir)->textPanel($panel)->toPng();

        $this->assertNotSame($ad(true), $ad(false));
        $this->assertSame($ad(null), $ad(true), 'null means automatic: on with AI backgrounds');
    }

    public function test_strip_uses_the_whole_uncropped_cover_and_puts_the_button_beside_the_text(): void
    {
        AiBackground::useGenerator(fn (string $p, int $w, int $h) => $this->brightImage($w, $h, [60, 80, 120]));
        $cover = $this->packshot();
        $ad = fn (bool $cta) => AdBuilder::make()->size(AdSize::STRIP)->images($cover)->imageFit('contain')->cutout(false)
            ->headline('SSC Board Exam Model Papers')->subheadline('Latest paper pattern 2025-26')
            ->aiBackground('exam-hall', prompt: 'strip test ' . $this->dir);

        $img = $this->decode($ad(true)->cta('Buy now')->toPng());
        $this->assertSame([1200, 200], [imagesx($img), imagesy($img)]);

        // The cover card sits at the left edge, the button (accent colour) at the right edge, text between.
        $accent = Theme::fromImage($cover)->accent;
        $hasAccentAtRight = false;
        for ($x = 1000; $x < 1190 && !$hasAccentAtRight; $x += 3) {
            for ($y = 60; $y < 140; $y += 3) {
                $hasAccentAtRight = $hasAccentAtRight || Color::toHex($this->rgb($img, $x, $y)) === Color::toHex($accent);
            }
        }
        $this->assertTrue($hasAccentAtRight, 'CTA pill on the right');
        $this->assertNotSame($ad(true)->cta('Buy now')->toPng(), $ad(true)->cta(null)->toPng());
    }
}
