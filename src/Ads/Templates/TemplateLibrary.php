<?php

namespace Packitifotech\Laragdads\Ads\Templates;

use GdImage;
use InvalidArgumentException;
use Packitifotech\Laragdads\Ads\Support\Canvas;

/**
 * The subject banner templates: one finished piece of artwork per subject (Mathematics, Science, English, Hindi, Marathi,
 * Social Science, Physics, Chemistry, Biology, EVS / General Knowledge) plus a generic one. Each plate in
 * resources/templates keeps the logo, benefit icons and "Shop Now" button; the label, the headline + tagline and the
 * placeholder book are removed. A banner is made by painting the plate and then drawing the product cover over its book
 * position and the product title in its copy zone (see Layouts\TemplateLayout).
 *
 * manifest.php (written by tools/prepare_templates.py) holds, per template, the plate size, the copy zone and the book
 * rectangle as fractions of the plate, and the headline and tagline ink colours.
 */
final class TemplateLibrary
{
    /** Template for subjects that have none of their own (commerce, computer, mixed lists ...). */
    public const GENERAL = 'general';

    /** Which templates suit a theme group of Subjects. Languages are refined by language, see languages(). */
    private const GROUP_TEMPLATES = [
        'physics' => ['physics'], 'chemistry' => ['chemistry'], 'biology' => ['biology'],
        'maths' => ['mathematics'], 'maths-advanced' => ['mathematics'],
        'history' => ['social-science'], 'geography' => ['social-science'], 'civics' => ['social-science'],
        'environment' => ['evs-gk'], 'reasoning' => ['evs-gk'], 'kids' => ['evs-gk'],
    ];

    /** @var array<string, array<string, mixed>>|null */
    private static ?array $manifest = null;

    /** @var array<string, GdImage> */
    private static array $plates = [];

    /** @return string[] template names */
    public static function names(): array
    {
        return array_keys(self::manifest());
    }

    public static function has(string $name): bool
    {
        return isset(self::manifest()[$name]);
    }

    /**
     * @return array{file:string,width:int,height:int,zone:float[],book:float[],ink:int[],tagline:int[]}
     */
    public static function spec(string $name): array
    {
        return self::manifest()[$name] ?? throw new InvalidArgumentException(
            "Unknown template [{$name}]. Available: " . implode(', ', self::names()) . '.'
        );
    }

    /** Headline and tagline ink colours of a template, as [r, g, b]. */
    public static function palette(string $name): array
    {
        $spec = self::spec($name);

        return ['ink' => $spec['ink'], 'tagline' => $spec['tagline']];
    }

    /** Width / height of the artwork. */
    public static function ratio(string $name): float
    {
        $spec = self::spec($name);

        return $spec['width'] / $spec['height'];
    }

    /**
     * The size to render a template at without cropping it: the given width and the matching height. The templates do not
     * share one aspect ratio, so ask each for its own size.
     *
     * @return array{0:int,1:int}
     */
    public static function dimensions(string $name, int $width = 1200): array
    {
        return [$width, (int) round($width / self::ratio($name))];
    }

    /**
     * Paint the artwork over the whole canvas (scaled to cover it, centred) and return how plate fractions map to canvas pixels.
     *
     * @return array{scale:float,x:float,y:float,width:float,height:float}
     */
    public static function paint(string $name, Canvas $canvas): array
    {
        $spec = self::spec($name);
        $w = $canvas->width();
        $h = $canvas->height();
        $scale = max($w / $spec['width'], $h / $spec['height']);
        $pw = $spec['width'] * $scale;
        $ph = $spec['height'] * $scale;
        $canvas->picture(self::plate($name), 0, 0, $w, $h, 'cover');

        return ['scale' => $scale, 'x' => ($w - $pw) / 2, 'y' => ($h - $ph) / 2, 'width' => $pw, 'height' => $ph];
    }

    /**
     * Turn a rectangle given as plate fractions [x0, y0, x1, y1] into canvas pixels [x, y, width, height].
     *
     * @param array{scale:float,x:float,y:float,width:float,height:float} $map from paint()
     * @param float[]                                                     $fractions
     * @return int[]
     */
    public static function rect(array $fractions, array $map): array
    {
        $x = $map['x'] + $fractions[0] * $map['width'];
        $y = $map['y'] + $fractions[1] * $map['height'];

        return [
            (int) round($x),
            (int) round($y),
            (int) round(($fractions[2] - $fractions[0]) * $map['width']),
            (int) round(($fractions[3] - $fractions[1]) * $map['height']),
        ];
    }

    // ------------------------------------------------------------- choosing

    /**
     * Templates that suit a subject label ("Physics", "PCM", "Eng/Mar/His", "Alegbra" ...), see Subjects for the spellings
     * understood. A related set of sciences gives each of their templates; unrelated mixes and subjects without a template
     * of their own give the generic one.
     *
     * @return string[]
     */
    public static function forSubject(string $label, ?string $title = null): array
    {
        if (Subjects::subjects($label) === ['science']) {
            return ['science'];
        }

        $found = [];
        $pool = Subjects::pool($label, $title);
        foreach ($pool as $group) {
            foreach ($group === 'languages' ? self::languages($label, $title) : (self::GROUP_TEMPLATES[$group] ?? [self::GENERAL]) as $template) {
                $found[$template] = true;
            }
        }
        if ($pool === ['general'] && $title !== null && preg_match('/\bscience\b/iu', $title) && !preg_match('/\bsocial\b/iu', $title)) {
            return ['science'];
        }

        return array_keys($found) ?: [self::GENERAL];
    }

    /** The template for a subject; $seed rotates through the candidates (the same seed always gives the same one). */
    public static function pickFor(string $label, int $seed = 0, ?string $title = null): string
    {
        $candidates = self::forSubject($label, $title);

        return $candidates[abs($seed) % count($candidates)];
    }

    /** @return string[] */
    private static function languages(string $label, ?string $title): array
    {
        $text = mb_strtolower($label . ' ' . ($title ?? ''));
        if (preg_match('/marathi|lokvani/u', $text)) {
            return ['marathi'];
        }
        if (preg_match('/hindi|sanskrit/u', $text)) {
            return ['hindi'];
        }

        return ['english'];
    }

    // --------------------------------------------------------------- loading

    /** @return array<string, array<string, mixed>> */
    private static function manifest(): array
    {
        return self::$manifest ??= require dirname(__DIR__, 3) . '/resources/templates/manifest.php';
    }

    private static function plate(string $name): GdImage
    {
        if (!isset(self::$plates[$name])) {
            if (count(self::$plates) >= 4) {
                array_shift(self::$plates);   // plates are ~2 MB each decoded: keep a few for a batch, not all
            }
            $path = dirname(__DIR__, 3) . '/resources/templates/' . self::spec($name)['file'];
            $image = @imagecreatefromjpeg($path);
            if ($image === false) {
                throw new InvalidArgumentException("Cannot read template artwork [{$path}].");
            }
            self::$plates[$name] = $image;
        }

        return self::$plates[$name];
    }
}
