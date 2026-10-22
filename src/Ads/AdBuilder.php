<?php

namespace Packitifotech\Laragdads\Ads;

use GdImage;
use InvalidArgumentException;
use Packitifotech\Laragdads\Ads\Ai\AiBackground;
use Packitifotech\Laragdads\Ads\Layouts\CompanionLayout;
use Packitifotech\Laragdads\Ads\Layouts\HeroLayout;
use Packitifotech\Laragdads\Ads\Layouts\Layout;
use Packitifotech\Laragdads\Ads\Layouts\ShowcaseLayout;
use Packitifotech\Laragdads\Ads\Layouts\TemplateLayout;
use Packitifotech\Laragdads\Ads\Templates\Subjects;
use Packitifotech\Laragdads\Ads\Templates\TemplateLibrary;
use Packitifotech\Laragdads\Ads\Layouts\StackedLayout;
use Packitifotech\Laragdads\Ads\Support\Canvas;
use Packitifotech\Laragdads\Ads\Support\Typography;

/**
 * Fluent ad generator.
 *
 *   AdBuilder::make()
 *       ->size(AdSize::LANDSCAPE)
 *       ->images('product.jpg')
 *       ->headline('Summer sale')->subheadline('Up to 50% off everything')
 *       ->cta('Shop now')->badge('-50%')
 *       ->toPng();
 */
final class AdBuilder
{
    /** @var array<string, Layout|class-string<Layout>> */
    private static array $layouts = [
        'companion' => CompanionLayout::class,
        'stacked' => StackedLayout::class,
        'hero' => HeroLayout::class,
        'showcase' => ShowcaseLayout::class,
    ];

    private AdSpec $spec;

    /** @var array{style:string,prompt:string}|null */
    private ?array $lastAi = null;

    private ?string $lastAiError = null;

    private function __construct()
    {
        $this->spec = new AdSpec();
        $this->spec->fontPath = self::defaultFont();
    }

    public static function make(): self
    {
        return new self();
    }

    /** Register a custom layout under a name usable in ->layout($name). */
    public static function registerLayout(string $name, Layout|string $layout): void
    {
        self::$layouts[$name] = $layout;
    }

    // ------------------------------------------------------------------ input

    public function size(AdSize|int $width, ?int $height = null): self
    {
        if ($width instanceof AdSize) {
            [$this->spec->width, $this->spec->height] = $width->dimensions();

            return $this;
        }
        if ($height === null || $width < 16 || $height < 16) {
            throw new InvalidArgumentException('size() needs an AdSize or a width and height of at least 16px.');
        }
        [$this->spec->width, $this->spec->height] = [$width, $height];

        return $this;
    }

    /** One or more product/hero images (paths or GdImage). Layouts use up to two. */
    public function images(GdImage|string ...$images): self
    {
        $this->spec->images = array_values($images);

        return $this;
    }

    public function logo(GdImage|string|null $logo): self
    {
        $this->spec->logo = $logo;

        return $this;
    }

    public function headline(?string $text): self
    {
        $this->spec->headline = $text;

        return $this;
    }

    public function subheadline(?string $text): self
    {
        $this->spec->subheadline = $text;

        return $this;
    }

    public function cta(?string $label): self
    {
        $this->spec->cta = $label;

        return $this;
    }

    /** Round sticker text such as "-30%" or "NEW". */
    public function badge(?string $text): self
    {
        $this->spec->badge = $text;

        return $this;
    }

    /**
     * Short selling points shown as a tick list (up to 5) when the format has room.
     *
     * @param string[] $features
     */
    public function features(array $features): self
    {
        $this->spec->features = array_values(array_filter(array_map('trim', $features), 'strlen'));

        return $this;
    }

    /** Force background removal on or off for packshots. Default: automatic for white backgrounds. */
    public function cutout(?bool $enabled = true): self
    {
        $this->spec->cutout = $enabled;

        return $this;
    }

    /**
     * Stacked title, each line sized to fill the title column (promo layout): first line in the accent
     * colour, second white, third a light tint. e.g. hero('SSC 54', 'QUESTION PAPERS', 'Activity Sheets With Solutions').
     */
    public function hero(string ...$lines): self
    {
        $this->spec->hero = array_slice(array_values($lines), 0, 3);

        return $this;
    }


    /**
     * Use one of the subject templates (see Templates\TemplateLibrary::names()): finished artwork with the product cover
     * drawn over its book and the headline / sub-headline (tagline) in its copy zone. Logo, benefit icons and the button
     * are part of the artwork. Render at the template's own proportions: TemplateLibrary::dimensions($name, 1200).
     */
    public function template(?string $name): self
    {
        if ($name !== null) {
            TemplateLibrary::spec($name);   // validates the name
        }
        $this->spec->template = $name;

        return $this;
    }

    /**
     * Pick a template that suits the subject: "Physics", "PCM", "Computer Science - I", "Eng/Mar/His", "Alegbra" (typos and
     * short forms are understood, see Templates\Subjects). $variant rotates through the templates of that subject, so a
     * book series can use a different look per title; the same variant always gives the same template.
     * Pass the product title as $title too and the look follows what the book is about: a colouring or activity book gets the
     * EVS / General Knowledge template even when it is filed under "English", and an unknown subject falls back to the title's keywords.
     * Call ->template($name) afterwards to override the choice.
     */
    public function subject(string $label, int $variant = 0, ?string $title = null): self
    {
        $this->spec->subject = $label;
        $this->spec->template = TemplateLibrary::pickFor($label, $variant, $title);

        return $this;
    }

    /** Templates: recolour the artwork's background to the cover's main colour (the default); false keeps the colours as designed. */
    public function matchCoverColour(bool $match = true): self
    {
        $this->spec->matchCoverColour = $match;

        return $this;
    }

    /** The template in use (chosen by subject() or set with template()), or null. Useful to log or store per book. */
    public function templateName(): ?string
    {
        return $this->spec->template;
    }

    /** Handwritten line on a brush stroke above the title (companion layout). */
    public function tagline(?string $text): self
    {
        $this->spec->tagline = $text;

        return $this;
    }

    /**
     * Feature cards with a round icon (companion layout). Each item: [icon, title, subtitle] or [title, subtitle]
     * (icons are then chosen in turn). Icons: see Support\Icons::names().
     *
     * @param array<int, array<int, string>> $items
     */
    public function highlights(array $items): self
    {
        $auto = ['book', 'doc', 'list', 'bulb', 'target', 'star'];
        $this->spec->highlights = [];
        foreach (array_values($items) as $i => $item) {
            $this->spec->highlights[] = count($item) >= 3
                ? [$item[0], $item[1], $item[2]]
                : [$auto[$i % count($auto)], $item[0], $item[1] ?? ''];
        }

        return $this;
    }

    /** Handwritten slogan on a brush blob, one word per line (companion layout). */
    public function slogan(string ...$words): self
    {
        $this->spec->slogan = array_values($words);

        return $this;
    }

    /** A picture on a white background that melts into the bottom-right of the page (e.g. an AI-made study-desk illustration). */
    public function artwork(GdImage|string|null $image): self
    {
        $this->spec->artwork = $image;

        return $this;
    }

    /** Italic / handwritten font for the tagline and slogan (default: bundled Liberation Serif Bold Italic). */
    public function scriptFont(?string $fontPath): self
    {
        $this->spec->scriptFontPath = $fontPath;

        return $this;
    }



    public function footerLogo(GdImage|string|null $logo): self
    {
        $this->spec->footerLogo = $logo;

        return $this;
    }



    /** Heading font; $bodyFont (optional) is used for the subheadline. */
    public function font(string $fontPath, ?string $bodyFont = null): self
    {
        $this->spec->fontPath = $fontPath;
        $this->spec->bodyFontPath = $bodyFont;

        return $this;
    }

    /** auto | stacked | hero | showcase | companion | any registered layout. */
    public function layout(string $name): self
    {
        $this->spec->layout = $name;

        return $this;
    }

    /** See Backgrounds::names(). */
    public function background(string $style): self
    {
        $this->spec->background = $style;

        return $this;
    }

    /**
     * Background art generated by the AI Horde (Stable Horde). The product, text, seal and buttons are still
     * drawn by the layout, so the model never has to spell anything.
     *
     * @param string      $style  'education' (default: a scene that fits the ad copy), 'random' (any education scene),
     *                            'abstract' (random art) or one of Ai\AiStyles::names()
     * @param string|null $prompt your own prompt instead of the style's
     * @param string|null $key    Horde API key; default: AIHORDE_API_KEY env var, then the anonymous key
     * @param int|null    $seed   fixes the random style and the image
     * @param bool        $strict throw if the Horde fails, instead of falling back to the built-in background
     * @param bool        $hires  bigger source image (slower, sharper)
     * @param string|null $topic  words that decide the education scene; default: the ad's own headline, title and details
     */
    public function aiBackground(string $style = 'education', ?string $prompt = null, ?string $key = null, ?int $seed = null, bool $strict = false, bool $hires = false, ?string $topic = null): self
    {
        $this->spec->ai = ['style' => $style, 'prompt' => $prompt, 'key' => $key, 'seed' => $seed, 'strict' => $strict, 'hires' => $hires, 'topic' => $topic];

        return $this;
    }

    /** Why the last render() fell back from the AI background, or null. */
    public function lastAiError(): ?string
    {
        return $this->lastAiError;
    }

    /** The AI style and prompt used by the last render() of this builder (null when AI was not used). */
    public function lastAi(): ?array
    {
        return $this->lastAi;
    }


    /** Text alignment: 'left', 'center' or 'right'. Default depends on the layout. */
    public function textAlign(?string $align): self
    {
        $this->spec->textAlign = in_array($align, ['left', 'center', 'right'], true) ? $align : null;

        return $this;
    }

    /** Soft translucent panel behind the text so it reads on photos. Automatic (on) with AI backgrounds. */
    public function textPanel(?bool $enabled = true): self
    {
        $this->spec->textPanel = $enabled;

        return $this;
    }


    /** 'cover' (fill and crop, default) or 'contain' (show the whole image). */
    public function imageFit(string $fit): self
    {
        $this->spec->imageFit = $fit === 'contain' ? 'contain' : 'cover';

        return $this;
    }

    public function shadows(bool $enabled = true): self
    {
        $this->spec->shadows = $enabled;

        return $this;
    }

    /** Colours from an explicit Theme, a hex/RGB brand colour, or an image to sample. */
    public function theme(Theme|array|string $theme): self
    {
        if ($theme instanceof Theme) {
            $this->spec->theme = $theme;
        } elseif (is_array($theme) || preg_match('/^#?[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $theme)) {
            $this->spec->theme = Theme::fromColors($theme);
        } else {
            $this->spec->themeSource = $theme;
            $this->spec->theme = null;
        }

        return $this;
    }

    /** Output format used by toBinary()/save(): png (default), jpg, webp. */
    public function format(string $format, int $quality = 90): self
    {
        $format = strtolower($format === 'jpeg' ? 'jpg' : $format);
        if (!in_array($format, ['png', 'jpg', 'webp'], true)) {
            throw new InvalidArgumentException("Unsupported format [{$format}]. Use png, jpg or webp.");
        }
        $this->spec->format = $format;
        $this->spec->quality = max(1, min(100, $quality));

        return $this;
    }

    // ----------------------------------------------------------------- output

    public function render(): GdImage
    {
        $spec = $this->spec;
        if ($spec->headline === null && !$spec->hasImages() && $spec->hero === []) {
            throw new InvalidArgumentException('An ad needs at least a headline or an image.');
        }

        $spec = $this->splitTitle(clone $spec);
        $theme = $spec->theme ?? $this->resolveTheme();
        $heading = new Typography($spec->fontPath);
        $bodyPath = $spec->bodyFontPath;
        if ($bodyPath === null && realpath($spec->fontPath) === realpath(self::bundledFont('Bold'))) {
            $bodyPath = self::bundledFont('');   // bold headlines over regular-weight body copy
        }
        $body = $bodyPath && $bodyPath !== $spec->fontPath ? new Typography($bodyPath) : $heading;

        $canvas = Canvas::create($spec->width, $spec->height, $theme->backgroundFrom);
        $layout = $this->resolveLayout();

        // Full-bleed hero layouts paint their own backdrop; everything else sits on the background.
        $this->lastAi = null;
        $this->lastAiError = null;
        $aiImage = null;
        if ($spec->ai !== null && ($spec->ai['topic'] ?? null) === null) {
            // Title words decide the scene; details and bullets list many subjects and would confuse it.
            $spec->ai['topic'] = implode(' ', array_filter([$spec->headline, $spec->subheadline, ...$spec->hero]));
        }
        if ($spec->ai !== null && $spec->template === null && !($layout instanceof HeroLayout && $spec->hasImages())) {
            try {
                $result = AiBackground::fetch($spec->ai, $theme, $spec->width, $spec->height);
                $aiImage = $result['image'];
                $this->lastAi = ['style' => $result['style'], 'prompt' => $result['prompt']];
            } catch (\Throwable $e) {
                if ($spec->ai['strict']) {
                    throw $e;
                }
                // Horde down, queue too long or key rejected: fall back to the built-in background.
                $this->lastAiError = $e->getMessage();
            }
        }

        // A template layout paints its own artwork; the others sit on a background.
        if ($spec->template === null) {
            if ($aiImage !== null) {
                $spec->textPanel ??= $spec->aspectRatio() < 3.2;   // strips use a blurred, tinted band instead
                // With a panel behind the text the photo can stay brighter and more visible.
                AiBackground::paint($canvas, $aiImage, $theme, (bool) $spec->textPanel);
                $spec->textShadow = $spec->aspectRatio() >= 3.2;   // photo strips: light overlay + text shadow keep the scene visible
            } elseif (!($layout instanceof HeroLayout && $spec->hasImages())) {
                $style = $spec->background === 'auto' ? match (true) {
                    $layout instanceof CompanionLayout => 'paper',
                    default => 'auto',
                } : $spec->background;
                Backgrounds::paint($style, $canvas, $this->specWithTheme($theme), $theme);
            }
        }

        $layout->render($canvas, $spec, $theme, $heading, $body);

        return $canvas->image();
    }

    /** Raw encoded bytes in the configured format. */
    public function toBinary(): string
    {
        $image = $this->render();
        ob_start();
        match ($this->spec->format) {
            'jpg' => imagejpeg($this->flatten($image), null, $this->spec->quality),
            'webp' => imagewebp($image, null, $this->spec->quality),
            default => imagepng($image, null, 6),
        };
        $data = (string) ob_get_clean();
        imagedestroy($image);

        return $data;
    }

    public function toPng(): string
    {
        return $this->format('png')->toBinary();
    }

    public function toJpg(int $quality = 90): string
    {
        return $this->format('jpg', $quality)->toBinary();
    }

    public function toWebp(int $quality = 90): string
    {
        return $this->format('webp', $quality)->toBinary();
    }

    public function toDataUri(): string
    {
        $mime = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'webp' => 'image/webp'][$this->spec->format];

        return 'data:' . $mime . ';base64,' . base64_encode($this->toBinary());
    }

    /** Write to a local absolute path. Returns the path. */
    public function save(string $path): string
    {
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException("Could not create directory [{$dir}].");
        }
        file_put_contents($path, $this->toBinary());

        return $path;
    }

    /** Write to a Laravel filesystem disk. Returns the storage path. */
    public function saveTo(string $storagePath, ?string $disk = null): string
    {
        if (!class_exists(\Illuminate\Support\Facades\Storage::class)) {
            throw new \RuntimeException('saveTo() needs illuminate/support. Use save() for a local path.');
        }
        \Illuminate\Support\Facades\Storage::disk($disk ?? self::config('default_disk', 'public'))
            ->put($storagePath, $this->toBinary());

        return $storagePath;
    }

    /**
     * Render the same creative at several sizes: one ad per placement.
     *
     * @param  array<AdSize|array{0:int,1:int}> $sizes
     * @return array<string, string> label ("728x90") => encoded bytes
     */
    public function renderSizes(array $sizes): array
    {
        $out = [];
        foreach ($sizes as $size) {
            $copy = clone $this;
            $copy->spec = clone $this->spec;
            $size instanceof AdSize ? $copy->size($size) : $copy->size($size[0], $size[1]);
            $out[$copy->spec->width . 'x' . $copy->spec->height] = $copy->toBinary();
        }

        return $out;
    }

    // --------------------------------------------------------------- internals

    private function resolveTheme(): Theme
    {
        if ($this->spec->themeSource !== null) {
            return Theme::fromImage($this->spec->themeSource);
        }
        $sources = array_slice($this->spec->images, 0, 2) ?: array_filter([$this->spec->logo]);

        return $sources !== [] ? Theme::fromImages($sources) : Theme::fromColors(Theme::FALLBACK);
    }

    /** "Book title | tagline" in one string becomes headline + subheadline when no subheadline is set. */
    private function splitTitle(AdSpec $spec): AdSpec
    {
        if ($spec->subheadline === null && $spec->headline !== null && str_contains($spec->headline, '|')) {
            [$head, $sub] = array_map('trim', explode('|', $spec->headline, 2));
            if ($head !== '' && $sub !== '') {
                [$spec->headline, $spec->subheadline] = [$head, $sub];
            }
        }

        return $spec;
    }

    private function specWithTheme(Theme $theme): AdSpec
    {
        $spec = clone $this->spec;
        $spec->theme = $theme;

        return $spec;
    }

    private function resolveLayout(): Layout
    {
        if ($this->spec->template !== null) {
            $ratio = TemplateLibrary::ratio($this->spec->template);
            if (abs($this->spec->aspectRatio() / $ratio - 1) > 0.2) {
                [$w, $h] = TemplateLibrary::dimensions($this->spec->template, $this->spec->width);
                throw new InvalidArgumentException("Template [{$this->spec->template}] is " . round($ratio, 2) . ":1, not {$this->spec->width}x{$this->spec->height}. Use TemplateLibrary::dimensions('{$this->spec->template}') (e.g. {$w}x{$h}).");
            }

            return new TemplateLayout($this->spec->template);
        }

        $name = $this->spec->layout;
        if ($name === 'auto' && $this->spec->hero !== []) {
            $name = 'companion';
        }
        if ($name === 'auto') {
            $name = $this->spec->aspectRatio() >= 2.2 ? 'showcase' : 'stacked';
        }

        $layout = self::$layouts[$name] ?? throw new InvalidArgumentException(
            "Unknown layout [{$name}]. Available: auto, " . implode(', ', array_keys(self::$layouts)) . '.'
        );

        return $layout instanceof Layout ? $layout : new $layout();
    }


    private function flatten(GdImage $image): GdImage
    {
        // JPEG has no alpha: composite onto white so transparent areas are not black.
        $flat = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
        imagecopy($flat, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        return $flat;
    }

    private static function defaultFont(): string
    {
        $configured = self::config('default_font');

        return is_string($configured) && $configured !== '' ? $configured : self::bundledFont('Bold');
    }

    /** Bundled DejaVu Sans: '' for regular, 'Bold' for bold. */
    private static function bundledFont(string $weight): string
    {
        return dirname(__DIR__, 2) . '/resources/fonts/DejaVuSans' . ($weight !== '' ? '-' . $weight : '') . '.ttf';
    }

    /** Laravel config when a container is booted, otherwise the supplied default. */
    private static function config(string $key, mixed $default = null): mixed
    {
        if (function_exists('app') && function_exists('config')) {
            try {
                if (app()->bound('config')) {
                    return config("laragdads.{$key}", $default);
                }
            } catch (\Throwable) {
            }
        }

        return $default;
    }
}
