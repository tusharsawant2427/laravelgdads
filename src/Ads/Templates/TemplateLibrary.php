<?php

namespace Packitifotech\Laragdads\Ads\Templates;

use GdImage;
use InvalidArgumentException;
use Packitifotech\Laragdads\Ads\Support\Canvas;
use Packitifotech\Laragdads\Ads\Support\Color;
use Packitifotech\Laragdads\Ads\Support\Recolor;

/**
 * The banner templates: one finished piece of artwork per subject (Mathematics, Science, English, Hindi, Marathi, Social
 * Science, Physics, Chemistry, Biology, EVS), per topic (entrance exam, pre-primary, kids activity, primary, foundation,
 * multi-subject, early learning, kindergarten, handwriting, general knowledge for kids) and the generic one in ten colours
 * (`general`, `general-orange` ...), of which the one nearest the cover's colour is used. Each plate in
 * resources/templates keeps the logo, benefit icons and "Shop Now" button; the label, the headline + tagline and the
 * placeholder book are removed. A banner is made by painting the plate and then drawing the product cover over its book
 * position and the product title in its copy zone (see Layouts\TemplateLayout).
 *
 * manifest.php (written by tools/prepare_templates.py) holds, per template, the plate size, the copy zone and the book
 * rectangle as fractions of the plate, and the headline and tagline ink colours.
 */
final class TemplateLibrary
{
    /** Template for subjects that have none of their own (commerce, computer ...). It comes in ten colours: see resolve(). */
    public const GENERAL = 'general';

    /** Which templates suit a theme group of Subjects. Languages are refined by language, see languages(). */
    private const GROUP_TEMPLATES = [
        'physics' => ['physics'], 'chemistry' => ['chemistry'], 'biology' => ['biology'],
        'maths' => ['mathematics'], 'maths-advanced' => ['mathematics'],
        'history' => ['social-science'], 'geography' => ['social-science'], 'civics' => ['social-science'],
        'environment' => ['evs-gk'], 'reasoning' => ['evs-gk'], 'kids' => ['kids-activity'],
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
     * With $match (a cover's main colour as [r, g, b]) the background colour of the artwork is moved to that hue; a colourless
     * cover (white, grey, black) leaves the artwork as designed.
     *
     * @return array{scale:float,x:float,y:float,width:float,height:float}
     */
    public static function paint(string $name, Canvas $canvas, ?array $match = null): array
    {
        $name = self::resolve($name, $match);
        $spec = self::spec($name);
        $w = $canvas->width();
        $h = $canvas->height();
        $scale = max($w / $spec['width'], $h / $spec['height']);
        $pw = $spec['width'] * $scale;
        $ph = $spec['height'] * $scale;
        $canvas->picture(self::matched($name, $match), 0, 0, $w, $h, 'cover');

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

    /** The ten colour variants of the generic template. @return string[] */
    public static function variants(): array
    {
        return array_values(array_filter(self::names(), fn (string $name) => self::isGeneral($name)));
    }

    public static function isGeneral(string $name): bool
    {
        return $name === self::GENERAL || str_starts_with($name, self::GENERAL . '-');
    }

    /**
     * The template to draw: the generic one becomes the colour variant nearest the cover's main colour (a dark cover prefers
     * the dark variant); every other name, and a cover without a real colour, stays as asked.
     */
    public static function resolve(string $name, ?array $match): string
    {
        if (!self::isGeneral($name) || $match === null) {
            return $name;
        }
        [$hue, $saturation, $lightness] = Color::toHsl($match);
        if ($saturation < 0.18) {
            return $name;
        }

        $best = $name;
        $bestScore = INF;
        foreach (self::variants() as $variant) {
            $spec = self::spec($variant);
            $distance = abs(fmod($hue - $spec['hue'] + 540, 360) - 180);
            $score = $distance + (($spec['lightness'] < 0.45) === ($lightness < 0.35) ? 0 : 45);
            if ($score < $bestScore) {
                [$best, $bestScore] = [$variant, $score];
            }
        }

        return $best;
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
        $title = $title === null ? '' : trim($title);
        $subjects = Subjects::subjects($label);

        // Titles that say what the book is: children's books, handwriting, "all subjects" ...
        if (($kids = self::childrensBook(trim($label . ' ' . $title))) !== null) {
            return [$kids];
        }
        if (preg_match('/\b(all subjects|all-in-one|combo)\b/iu', $title) || (count($subjects) > 1 && Subjects::pool($label) === ['general'])) {
            return ['multi-subject'];
        }
        if (preg_match('/\bcuet\b|entrance exam/iu', $title)) {
            return ['entrance-exam'];
        }
        if ($subjects === ['science']) {
            return ['science'];
        }
        if (in_array('general knowledge', $subjects, true) || preg_match('/general knowledge|\bgk\b/iu', $title)) {
            return ['gk-kids'];
        }

        $pool = Subjects::pool($label, $title);
        if ($pool === ['kids'] && self::languageBook($label, $title)) {
            return self::languages($label, $title);   // "English Grammar Book for 8+ Years' Old Kids" is still an English book
        }
        if ($pool === ['general'] && preg_match('/\bscience\b/iu', $title) && !preg_match('/\bsocial\b/iu', $title)) {
            return ['science'];
        }

        $found = [];
        foreach ($pool as $group) {
            foreach ($group === 'languages' ? self::languages($label, $title) : (self::GROUP_TEMPLATES[$group] ?? [self::GENERAL]) as $template) {
                $found[$template] = true;
            }
        }
        $found = array_keys($found) ?: [self::GENERAL];

        // Books without a subject template of their own: the topic says more than "general".
        if ($found === [self::GENERAL]) {
            if (preg_match('/\b(neet|jee|cet)\b/iu', $title)) {
                return ['entrance-exam'];
            }
            if (preg_match('/\bfoundation\b/iu', $title)) {
                return ['foundation'];
            }
            if (preg_match('/\b(std|class)\.?\s*[1-5]\b|\bprimary\b/iu', $title)) {
                return ['primary'];
            }
            if (preg_match('/learning skills|life skills|logical|brain/iu', $title)) {
                return ['early-learning'];
            }
        }

        return $found;
    }

    /**
     * The children's templates, from words in the title; null for older students' books and everything else. Std 6 and up never
     * count as children's books ("Std 8 Science Activity Book"), except for handwriting.
     */
    private static function childrensBook(string $title): ?string
    {
        if ($title === '') {
            return null;
        }
        if (preg_match('/hand-?writing|writing practice|cursive|calligraphy/iu', $title)) {
            return 'handwriting';
        }
        if (preg_match('/\b(std|class)\.?\s*(?:[6-9]|1[0-2])\b/iu', $title)) {
            return null;
        }
        if (preg_match('/\b(lkg|ukg|kg|kindergarten)\b/iu', $title)) {
            return 'kindergarten';
        }
        if (preg_match('/pre-?primary|pre-?school|nursery|rhymes?|alphabets?\b|\babc\b/iu', $title)) {
            return 'pre-primary';
        }
        if (preg_match('/colou?ring|drawing|\bactivity (book|kit)s?\b|craft|stickers?\b/iu', $title)) {
            return 'kids-activity';
        }

        return null;
    }

    /** The template for a subject; $seed rotates through the candidates (the same seed always gives the same one). */
    public static function pickFor(string $label, int $seed = 0, ?string $title = null): string
    {
        $candidates = self::forSubject($label, $title);

        return $candidates[abs($seed) % count($candidates)];
    }

    /** A language book for children: the subject (or the title, when it is unfiled) is a language, so it keeps that language's template. */
    private static function languageBook(string $label, ?string $title): bool
    {
        $groups = Subjects::groups($label);

        // An unfiled book is recognised by its title, but only by the Indian languages: "English Medium" is in half the titles.
        return in_array('languages', $groups, true)
            || ($groups === ['general'] && $title !== null && preg_match('/\b(hindi|marathi|sanskrit)\b/iu', $title));
    }

    /**
     * The language template: the subject label decides ("Sanskrit ... for Marathi Medium" is a Hindi-style Devanagari
     * book, not a Marathi one); the title is only consulted when the label names no language.
     *
     * @return string[]
     */
    private static function languages(string $label, ?string $title): array
    {
        foreach ([mb_strtolower($label), mb_strtolower((string) $title)] as $text) {
            if (preg_match('/marathi|lokvani/u', $text)) {
                return ['marathi'];
            }
            if (preg_match('/hindi|sanskrit/u', $text)) {
                return ['hindi'];
            }
            if (preg_match('/english|grammar|vocabulary/u', $text)) {
                return ['english'];
            }
        }

        return ['english'];
    }

    // --------------------------------------------------------------- loading

    /** @return array<string, array<string, mixed>> */
    private static function manifest(): array
    {
        return self::$manifest ??= require dirname(__DIR__, 3) . '/resources/templates/manifest.php';
    }

    /** The artwork, with its background recoloured to $match when that is a real colour. */
    private static function matched(string $name, ?array $match): GdImage
    {
        $plate = self::plate($name);
        if ($match === null || self::isGeneral($name)) {
            return $plate;   // the generic artwork has a colour variant instead
        }
        [$hue, $saturation] = Color::toHsl($match);
        if ($saturation < 0.18) {
            return $plate;   // a white, grey or black cover has no colour to match
        }
        $spec = self::spec($name);

        return Recolor::hue($plate, $spec['hue'], $spec['saturation'], $hue, max(0.15, min(0.6, $saturation * 0.5)));
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
