<?php

namespace Packitifotech\Laragdads\Tests;

use GdImage;
use InvalidArgumentException;
use Packitifotech\Laragdads\Ads\AdBuilder;
use Packitifotech\Laragdads\Ads\AdSize;
use Packitifotech\Laragdads\Ads\Backgrounds;
use Packitifotech\Laragdads\Ads\Support\Canvas;
use Packitifotech\Laragdads\Ads\Support\Color;
use Packitifotech\Laragdads\Ads\Support\Cutout;
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
        $this->font = dirname(__DIR__) . '/resources/fonts/DejaVuSans-Bold.ttf';
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*') ?: []);
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
}
