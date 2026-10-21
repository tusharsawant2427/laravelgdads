<?php

namespace Packitifotech\Laragdads\Tests;

use GdImage;
use InvalidArgumentException;
use Packitifotech\Laragdads\Ads\Ai\AiBackground;
use Packitifotech\Laragdads\Ads\Ai\AiStyles;
use Packitifotech\Laragdads\Ads\AdBuilder;
use Packitifotech\Laragdads\Ads\AdSpec;
use Packitifotech\Laragdads\Ads\AdSize;
use Packitifotech\Laragdads\Ads\Backgrounds;
use Packitifotech\Laragdads\Ads\Support\Canvas;
use Packitifotech\Laragdads\Ads\Support\Color;
use Packitifotech\Laragdads\Ads\Support\Cutout;
use Packitifotech\Laragdads\Ads\Support\ImageLoader;
use Packitifotech\Laragdads\Ads\Support\Icons;
use Packitifotech\Laragdads\Ads\Layouts\TemplateLayout;
use Packitifotech\Laragdads\Ads\Templates\Subjects;
use Packitifotech\Laragdads\Ads\Templates\TemplateLibrary;
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
            foreach (['auto', 'stacked', 'hero', 'showcase', 'companion'] as $layout) {
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
            $img = $this->decode(AdBuilder::make()->size(400, 300)->images($book)->background($style)->layout('stacked')->headline('Hi')->toPng());
            $this->assertSame(400, imagesx($img), $style);
        }
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
        $this->assertSame([704, 384], [$calls[0][1], $calls[0][2]], 'landscape source size');

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
        $this->assertSame([704, 384], AiBackground::dimensions(1.9));
        $this->assertSame([384, 704], AiBackground::dimensions(0.56));
        $this->assertSame([576, 576], AiBackground::dimensions(1.0));
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
            ->features(['Mathematics Part - I & II', 'Physics and Chemistry'])
            ->aiBackground('education', seed: 0, topic: null)->toPng();

        $this->assertMatchesRegularExpression('/exam|classroom|desk/', $seen[0]);
        $this->assertDoesNotMatchRegularExpression('/geometry|compass|laboratory/', $seen[0]);
    }


    public function test_text_panel_is_automatic_with_ai_backgrounds_and_can_be_turned_off(): void
    {
        AiBackground::useGenerator(fn (string $p, int $w, int $h) => $this->brightImage($w, $h, [90, 120, 80]));
        $ad = fn (?bool $panel) => AdBuilder::make()->size(AdSize::LANDSCAPE)->layout('stacked')->images($this->packshot())
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




    private function companion(AdSize $size): AdBuilder
    {
        return AdBuilder::make()->size($size)->images($this->packshot())->logo($this->packshot('logo.png'))
            ->tagline('Your Complete Practice Companion for')->hero('MHT-CET', 'PHYSICS')
            ->highlights([['book', '28 YEARS', 'Previous Solved Papers'], ['Chapter-wise', 'Organisation'], ['4100+', 'MCQs'], ['bulb', 'Quick Review', 'Last-minute prep']])
            ->slogan('Concepts', 'Practice', 'Confidence');
    }

    public function test_companion_layout_renders_wide_strip_and_falls_back_when_narrow(): void
    {
        foreach ([AdSize::WEBSITE_BANNER, AdSize::STRIP, AdSize::SQUARE, AdSize::LEADERBOARD] as $size) {
            $img = $this->decode($this->companion($size)->toPng());
            $this->assertSame([$size->width(), $size->height()], [imagesx($img), imagesy($img)], $size->value);
        }
    }

    public function test_companion_elements_are_all_drawn_on_a_light_page(): void
    {
        $base = $this->companion(AdSize::WEBSITE_BANNER)->toPng();
        $this->assertNotSame($base, $this->companion(AdSize::WEBSITE_BANNER)->tagline(null)->toPng());
        $this->assertNotSame($base, $this->companion(AdSize::WEBSITE_BANNER)->highlights([])->toPng());
        $this->assertNotSame($base, $this->companion(AdSize::WEBSITE_BANNER)->slogan()->toPng());

        $img = $this->decode($base);
        [$r, $g, $b] = $this->rgb($img, 1100, 640);   // the page itself is light paper
        $this->assertGreaterThan(220, min($r, $g, $b));
        $this->assertSame($base, $this->companion(AdSize::WEBSITE_BANNER)->toPng(), 'deterministic');
    }

    public function test_every_icon_draws_something_and_unknown_icons_are_rejected(): void
    {
        foreach (Icons::names() as $name) {
            $c = Canvas::create(100, 100, [255, 255, 255]);
            Icons::draw($c, $name, 50, 50, 80, [0, 0, 0]);
            $dark = 0;
            for ($y = 0; $y < 100; $y += 2) {
                for ($x = 0; $x < 100; $x += 2) {
                    $dark += ($this->rgb($c->image(), $x, $y)[0] < 200) ? 1 : 0;
                }
            }
            $this->assertGreaterThan(15, $dark, $name);
        }
        $this->expectException(InvalidArgumentException::class);
        Icons::draw(Canvas::create(10, 10), 'nope', 5, 5, 8, [0, 0, 0]);
    }

    public function test_multiply_blends_white_backgrounds_into_the_page(): void
    {
        $page = Canvas::create(100, 100, [240, 230, 250]);
        $art = Canvas::create(100, 100, [255, 255, 255]);
        $art->rect(40, 40, 20, 20, [100, 100, 100]);
        $page->multiply($art->image(), 0, 0, 100, 100, 0);

        $this->assertSame([240, 230, 250], $this->rgb($page->image(), 5, 5), 'white leaves the page unchanged');
        $this->assertLessThan(120, $this->rgb($page->image(), 50, 50)[0], 'dark artwork shows through');
    }

    public function test_theme_extraction_does_not_silently_fall_back_to_the_default_colour(): void
    {
        $c = Canvas::create(200, 280, [255, 255, 255]);
        $c->rect(20, 16, 160, 240, [200, 120, 40]);
        imagepng($c->image(), $this->dir . '/orange.png');

        $theme = Theme::fromImage($this->dir . '/orange.png');
        $this->assertNotSame(Theme::FALLBACK, $theme->primary);
        $this->assertLessThan(25, Color::hueDistance($theme->primary, [200, 120, 40]));
    }

    public function test_highlight_colour_stays_warm_even_when_the_cover_accent_is_pink_or_green(): void
    {
        foreach (['#f7cfdd', '#22c55e', '#3b82f6'] as $accent) {
            $gold = Theme::fromColors('#7c3aed', null, $accent)->gold();
            [$h] = Color::toHsl($gold);
            $this->assertTrue($h >= 10 && $h <= 70, "{$accent} -> " . Color::toHex($gold));
        }
        $orange = Theme::fromColors('#7c3aed', null, '#f08a3c')->gold();
        $this->assertLessThan(15, Color::hueDistance($orange, [240, 138, 60]));
    }

    public function test_every_template_paints_a_distinct_background_with_readable_ink(): void
    {
        $names = TemplateLibrary::names();
        $this->assertCount(53, $names);

        $seen = [];
        foreach ($names as $name) {
            $c = Canvas::create(1200, 200, [255, 255, 255]);
            TemplateLibrary::paint($name, $c, null, 260);
            $ink = TemplateLibrary::palette($name)['ink'];
            [$x0, $x1] = TemplateLibrary::zone($name, 1200, 200, 260);
            $from = (int) ($x0 + 160);
            foreach ([0.1, 0.5, 0.9] as $fx) {
                foreach ([60, 100, 150] as $py) {
                    $px = (int) ($from + ($x1 - $from) * $fx);
                    $this->assertGreaterThanOrEqual(4.5, Color::contrast($ink, $this->rgb($c->image(), $px, $py)), "{$name} at {$px},{$py}: ink is readable in the text area");
                }
            }

            ob_start();
            imagepng($c->image());
            $seen[md5((string) ob_get_clean())] = true;
        }
        $this->assertCount(53, $seen, 'every template looks different');
    }

    public function test_declared_zones_clear_the_artwork_and_leave_room_for_text(): void
    {
        foreach (TemplateLibrary::names() as $name) {
            foreach ([[1200, 200], [2000, 256], [2800, 256]] as [$w, $h]) {
                $spec = new AdSpec();
                $spec->width = $w;
                $spec->height = $h;
                $spec->cta = 'Buy now';
                $reserve = (new TemplateLayout($name))->reserveRight($spec, new Typography(dirname(__DIR__) . '/resources/fonts/DejaVuSans-Bold.ttf'));

                $art = TemplateLibrary::artwork($name, $w, $h, $reserve);
                [$x0, $x1] = TemplateLibrary::zone($name, $w, $h, $reserve);
                $this->assertGreaterThanOrEqual($art['left'] * $h - 2, $x0, "{$name} {$w}x{$h}: zone starts after the left artwork");
                $this->assertLessThanOrEqual(($w - $reserve) - $art['right'] * $h + 2, $x1, "{$name} {$w}x{$h}: zone ends before the right artwork");
                $this->assertGreaterThanOrEqual(1.0 * $h, $x1 - $x0 - 0.84 * $h, "{$name} {$w}x{$h}: room for the text column");
            }
        }
    }

    public function test_template_ads_render_in_strip_and_wide_sizes_with_zone_and_palette(): void
    {
        $cover = $this->packshot();
        foreach (['sky-atom', 'doodle-grid', 'prism-rainbow', 'mint-chemistry', 'navy-success', 'sunny-rocket'] as $name) {
            foreach ([[1200, 200], [2000, 256]] as [$sw, $sh]) {
                $img = $this->decode(AdBuilder::make()->size($sw, $sh)->images($cover)->template($name)
                    ->headline('MHT-CET Physics')->subheadline('Solved papers')->cta('Buy now')->badge('4100+')->toPng());
                $this->assertSame([$sw, $sh], [imagesx($img), imagesy($img)], "{$name} {$sw}x{$sh}");
            }
            ['ink' => $ink, 'accent' => $accent, 'zone' => $zone] = TemplateLibrary::palette($name);
            $this->assertCount(3, $ink);
            $this->assertCount(3, $accent);
            [$x0, $x1] = TemplateLibrary::zone($name, 1200, 200);
            $this->assertGreaterThan($x0 + 300, $x1, 'the free zone is wide enough for content');
        }
    }

    public function test_templates_reject_unknown_names_and_narrow_canvases(): void
    {
        try {
            AdBuilder::make()->template('nope');
            $this->fail('unknown template accepted');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Unknown template', $e->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        AdBuilder::make()->size(AdSize::SQUARE)->headline('Hi')->template('sky-atom')->render();
    }

    public function test_template_text_uses_the_template_ink_and_button_uses_its_accent(): void
    {
        $img = $this->decode(AdBuilder::make()->size(AdSize::STRIP)->template('aqua-hex')->headline('Hello')->cta('Buy now')->toPng());
        $accent = TemplateLibrary::palette('aqua-hex')['accent'];

        $found = false;
        for ($x = 900; $x < 1190 && !$found; $x += 3) {
            for ($y = 70; $y < 130; $y += 3) {
                $found = $found || Color::toHex($this->rgb($img, $x, $y)) === Color::toHex($accent);
            }
        }
        $this->assertTrue($found, 'the template accent colours the button');
    }

    public function test_subject_labels_are_normalised_typos_and_short_forms_included(): void
    {
        $this->assertSame(['maths-advanced'], Subjects::groups('Alegbra'));
        $this->assertSame(['maths-advanced'], Subjects::groups('Trignometry'));
        $this->assertSame(['maths-advanced'], Subjects::groups('Vectors & 3D Geometry'));
        $this->assertSame(['reasoning'], Subjects::groups('General Knowlegde'));
        $this->assertSame(['art'], Subjects::groups('Drawing and Colouring'));
        $this->assertSame(['computer'], Subjects::groups('Applied  Electronics'));
        $this->assertSame(['computer'], Subjects::groups('Computer Science - II'));
        $this->assertSame(['computer'], Subjects::groups('Information Communication & Technology'));
        $this->assertSame(['commerce'], Subjects::groups('  book KEEPING and Accountancy '));
        $this->assertSame(['maths'], Subjects::groups('Applied Mathematics'));
        $this->assertSame(['languages', 'history'], Subjects::groups('Eng/Mar/His'));
        $this->assertSame(['physics', 'chemistry', 'maths'], Subjects::groups('PCM'));
        $this->assertSame(['physics', 'chemistry', 'maths', 'biology'], Subjects::groups('Physics + Chemistry + Maths + Biology'));
        $this->assertSame(['general'], Subjects::groups('Something nobody listed'));
        $this->assertSame(['something nobody listed'], Subjects::unknown('Something nobody listed'));
    }

    public function test_every_subject_in_the_supplied_list_is_recognised_and_has_templates(): void
    {
        $list = [
            'EVS', 'Computer', 'Vocabulary', 'Grammar', 'Hindi', 'History', 'Science', 'English', 'Maths', 'Geography', 'Marathi',
            'Eng/Hin/Mar/His/Geo/Math/Sci', 'Sanskrit', 'Lokvani', 'Eng/Mar/His', 'Social Science', 'History/Geography', 'Chemistry',
            'Physics', 'Biology', 'Political Science', 'Sociology', 'SP', 'Economics', 'OC', 'Accounts', 'Psychology',
            'Applied  Electronics', 'Digital Electronics', 'Information Technology', 'Computer Science - I', 'Computer Science - II',
            'HRM', 'Marketing', 'EMS', 'General Test', 'Book Keeping and Accountancy', 'Applied Mathematics',
            'Business Studies and Entrepreneurship', 'PCM', 'PCB', 'Business Studies', 'PCMB', 'Water Security', 'Financial Literacy',
            'Art and Craft', 'English + Hindi + Marathi + History + Geography + Maths + Science', 'English + Maths + Science',
            'English + Marathi + History + Geography', 'History + Geography', 'Information Communication & Technology',
            'Physics + Chemistry + Biology', 'Physics + Chemistry + Maths + Biology', 'Physics + Chemistry + Maths', 'Alegbra',
            'Trignometry', 'Vectors & 3D Geometry', 'Coordinate Geometry', 'Calculus', 'General Knowlegde', 'Drawing and Colouring',
            'Reasoning', 'Defence Studies', 'Value Education', 'Physical Education',
        ];
        foreach ($list as $label) {
            $this->assertSame([], Subjects::unknown($label), "{$label} is recognised");
            $this->assertNotEmpty(TemplateLibrary::forSubject($label), "{$label} has templates");
        }
    }

    public function test_every_group_has_at_least_two_templates_and_every_template_is_tagged(): void
    {
        $byGroup = TemplateLibrary::byGroup();
        $this->assertSame(Subjects::GROUPS, array_keys($byGroup));
        foreach ($byGroup as $group => $templates) {
            $this->assertGreaterThanOrEqual(2, count($templates), $group);
        }
        $tagged = array_unique(array_merge(...array_values($byGroup)));
        $this->assertEqualsCanonicalizing(TemplateLibrary::names(), $tagged, 'no template without a theme group');
    }

    public function test_subject_picks_templates_that_suit_it_and_variants_rotate(): void
    {
        $this->assertContains(TemplateLibrary::pickFor('Physics'), TemplateLibrary::forGroup('physics'));
        $this->assertContains(TemplateLibrary::pickFor('Calculus', 1), TemplateLibrary::forGroup('maths-advanced'));
        $this->assertSame(TemplateLibrary::pickFor('Chemistry', 2), TemplateLibrary::pickFor('Chemistry', 2));
        $this->assertCount(3, array_unique(array_map(fn ($v) => TemplateLibrary::pickFor('Chemistry', $v), [0, 1, 2])));
        $this->assertEqualsCanonicalizing(
            array_unique(array_merge(TemplateLibrary::forGroup('history'), TemplateLibrary::forGroup('geography'), TemplateLibrary::forGroup('civics'))),
            TemplateLibrary::forSubject('Social Science'),
            'a related-subject label draws on all of their themes'
        );
        $this->assertEqualsCanonicalizing(TemplateLibrary::forGroup('general'), TemplateLibrary::forSubject('English + Hindi + Marathi + History + Geography + Maths + Science'));

        $this->expectException(InvalidArgumentException::class);
        TemplateLibrary::forGroup('nope');
    }

    public function test_default_pick_is_a_template_made_for_the_subject_not_a_borrowed_one(): void
    {
        $expect = ['Physics' => 'physics', 'Alegbra' => 'maths-advanced', 'Water Security' => 'environment', 'Computer Science - II' => 'computer',
            'Accounts' => 'commerce', 'Calculus' => 'maths-advanced', 'Value Education' => 'psychology', 'Drawing and Colouring' => 'art',
            'General Knowlegde' => 'reasoning', 'Defence Studies' => 'sports', 'Marathi' => 'languages', 'Sociology' => 'civics'];
        foreach ($expect as $label => $group) {
            $first = TemplateLibrary::pickFor($label, 0);
            $this->assertSame($group, $this->primaryGroup($first), "{$label} -> {$first}");
        }
    }

    private function primaryGroup(string $template): string
    {
        $tags = (new \ReflectionClass(TemplateLibrary::class))->getReflectionConstant('TAGS')->getValue();

        return $tags[$template][0];
    }

    public function test_builder_subject_chooses_the_template_and_renders(): void
    {
        $ad = fn (string $label, int $v = 0) => AdBuilder::make()->size(AdSize::STRIP)->images($this->packshot())->subject($label, $v)
            ->headline('Notes')->cta('Buy now');

        $this->assertContains($ad('Calculus')->templateName(), TemplateLibrary::forGroup('maths-advanced'));
        $this->assertNull(AdBuilder::make()->templateName());
        $physics = $ad('Physics')->toPng();
        $this->assertSame([1200, 200], [imagesx($this->decode($physics)), imagesy($this->decode($physics))]);
        $this->assertNotSame($physics, $ad('Physics', 1)->toPng(), 'a different variant is a different template');
        $this->assertSame($physics, $ad('Physics')->toPng());
        $this->assertNotSame($physics, $ad('Accounts')->toPng());
        $this->assertSame($ad('Physics')->template('prism-rainbow')->toPng(), AdBuilder::make()->size(AdSize::STRIP)->images($this->packshot())->template('prism-rainbow')->headline('Notes')->cta('Buy now')->toPng(), 'an explicit template overrides the subject');
    }

    public function test_subject_templates_render_at_both_strip_sizes(): void
    {
        foreach (TemplateLibrary::names() as $name) {
            foreach ([[1200, 200], [2000, 256], [2800, 256]] as [$w, $h]) {
                $img = $this->decode(AdBuilder::make()->size($w, $h)->images($this->packshot())->template($name)->headline('Notes')->cta('Buy')->toPng());
                $this->assertSame([$w, $h], [imagesx($img), imagesy($img)], "{$name} {$w}x{$h}");
            }
        }
    }

    public function test_the_button_is_in_the_same_place_on_every_template(): void
    {
        $cover = $this->packshot();
        $boxes = [];
        foreach (TemplateLibrary::names() as $name) {
            $theme = TemplateLibrary::palette($name);
            $img = $this->decode(AdBuilder::make()->size(1200, 200)->images($cover)->template($name)->headline('Notes')->cta('Buy now')->toPng());

            // Follow the accent-coloured pill leftwards from the right edge, on a row above the label text. (Artwork can use the
            // same colour elsewhere, so only the contiguous run that ends at the button counts.)
            $accent = Color::toHex($theme['accent']);
            $maxX = -1;
            for ($x = 1199; $x >= 700; $x--) {
                if (Color::toHex($this->rgb($img, $x, 70)) === $accent) {
                    $maxX = $x;
                    break;
                }
            }
            $minX = $maxX;
            while ($minX > 700 && Color::toHex($this->rgb($img, $minX - 1, 70)) === $accent) {
                $minX--;
            }
            $this->assertGreaterThan(0, $maxX, "{$name}: the button is drawn");
            $boxes[$name] = [$minX, $maxX];
        }

        $rights = array_unique(array_column($boxes, 1));
        $lefts = array_unique(array_column($boxes, 0));
        $this->assertCount(1, $rights, 'the button always ends at the same x: ' . json_encode($boxes));
        $this->assertCount(1, $lefts, 'and starts at the same x');
        $this->assertLessThanOrEqual(1200 - 18, reset($rights), 'flush to the right edge with a margin');
    }

    public function test_removed_styles_are_gone_and_the_plain_layout_serves_the_remaining_sizes(): void
    {
        foreach (['seal', 'tag', 'details', 'ctaStyle', 'variant', 'imageSide', 'book3d'] as $method) {
            $this->assertFalse(method_exists(AdBuilder::class, $method), "{$method}() was removed");
        }
        foreach (['arcs', 'waves', 'lines', 'bokeh', 'hex'] as $background) {
            $this->assertNotContains($background, Backgrounds::names());
        }
        foreach (['promo', 'books', 'split'] as $layout) {
            try {
                AdBuilder::make()->headline('x')->layout($layout)->render();
                $this->fail("layout {$layout} still exists");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Unknown layout', $e->getMessage());
            }
        }

        $book = $this->packshot();
        foreach ([AdSize::STORY, AdSize::SQUARE, AdSize::PORTRAIT, AdSize::MEDIUM_RECTANGLE, AdSize::LANDSCAPE, AdSize::HALF_PAGE] as $size) {
            $img = $this->decode(AdBuilder::make()->size($size)->images($book, $book)->headline('MHT-CET Physics')
                ->subheadline('Previous solved papers')->features(['4100+ MCQs', 'Quick Review'])->cta('Buy now')->badge('-30%')->toPng());
            $this->assertSame([$size->width(), $size->height()], [imagesx($img), imagesy($img)], $size->value);
        }
    }
}
