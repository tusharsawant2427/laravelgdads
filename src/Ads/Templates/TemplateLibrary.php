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

    /** The "[SUBJECT NAME] PREPARATION BOOKS" banner for product categories; in two colours. */
    public const CATEGORY = 'category';

    /** Which templates suit a theme group of Subjects. Languages are refined by language, see languages(). */
    private const GROUP_TEMPLATES = [
        'physics' => ['physics'], 'chemistry' => ['chemistry'], 'biology' => ['biology'],
        'maths' => ['mathematics'], 'maths-advanced' => ['mathematics'],
        'history' => ['social-science'], 'geography' => ['social-science'], 'civics' => ['social-science'],
        'environment' => ['evs-gk'], 'reasoning' => ['evs-gk'], 'kids' => ['kids-activity'],
    ];

    /**
     * The feature labels (icon + two words) each template shows until ->features() says otherwise; the artwork's own row is wiped.
     * Hindi and Marathi use English labels: the display font has no Devanagari.
     */
    private const FEATURES = [
        'mathematics' => ['Concept Clarity', 'Step-by-Step Solutions', 'Practice Questions'],
        'science' => ['Clear Concepts', 'Illustrations', 'Practice Questions'],
        'english' => ['Reading Skills', 'Grammar Practice', 'Exam Preparation'],
        'hindi' => ['Easy Explanations', 'Practice Questions', 'Exam Focused'],
        'marathi' => ['Easy Explanations', 'Practice Questions', 'Exam Focused'],
        'social-science' => ['Easy Explanations', 'Maps & Diagrams', 'Exam Focused'],
        'physics' => ['Concept Clarity', 'Numerical Practice', 'Real-Life Applications'],
        'chemistry' => ['Clear Concepts', 'Solved Examples', 'Practice Questions'],
        'biology' => ['Detailed Explanations', 'Illustrations', 'Practice Questions'],
        'evs-gk' => ['Real-World Examples', 'Engaging Content', 'Exam Ready'],
        'pre-primary' => ['Bright Illustrations', 'Fun Activities', 'Early Learning', 'Builds Foundation'],
        'kids-activity' => ['Colourful Activities', 'Skill Development', 'Creativity Boost', 'Engaging Content'],
        'primary' => ['Easy Explanations', 'Illustrations', 'Practice Questions', 'Concept Clarity'],
        'foundation' => ['Concept Building', 'Step-by-Step Learning', 'Engaging Examples', 'Practice Worksheets'],
        'multi-subject' => ['Complete Coverage', 'Simple Explanations', 'Practice Questions', 'Exam Ready'],
        'early-learning' => ['Observation', 'Logical Thinking', 'Problem Solving', 'Activity Based'],
        'kindergarten' => ['Alphabet & Numbers', 'Fun Activities', 'Colourful Illustrations', 'Early Concepts'],
        'handwriting' => ['Guided Practice', 'Step-by-Step Strokes', 'Improve Writing Skills', 'Age Appropriate'],
        'gk-kids' => ['Interesting Facts', 'Visual Learning', 'Current Affairs', 'Builds Awareness'],
        'general' => ['Easy Explanations', 'Solved Examples', 'Practice Questions'],
        'general-orange' => ['Concept Clarity', 'Step-by-Step Solutions', 'Exam-Oriented Practice'],
        'general-purple' => ['Clear Concepts', 'Exam Focus', 'Score Higher'],
        'general-green' => ['Visual Learning', 'Diagrams & Illustrations', 'Practice Questions'],
        'general-navy' => ['Build Concepts', 'Real-Life Applications', 'Exam Ready'],
        'general-pink' => ['Easy Learning', 'Memory Friendly', 'Better Results'],
        'general-yellow' => ['Simplified Learning', 'Key Topics', 'Practice & Revision'],
        'general-teal' => ['Modern Approach', 'Practical Learning', 'Skill Development'],
        'general-cream' => ['Detailed Content', 'Exam-Oriented', 'Trusted by Students'],
        'general-lavender' => ['Engaging Content', 'Activity-Based Learning', 'For Bright Futures'],
        'neet' => ['Chapter-wise Theory', 'Solved Examples', 'Practice Questions', "Previous Years' Papers"],
        'mht-cet' => ['Simplified Theory', 'Solved Examples', 'Practice Questions', "Previous Years' Papers"],
        'cuet' => ['Subject-wise Theory', 'Solved Examples', 'Practice Questions', "Previous Years' Papers"],
        'entrance-exams' => ['Comprehensive Study Material', 'Practice Questions', "Previous Years' Papers"],
        'category' => ['Chapter-wise Theory', 'Solved Examples', 'Practice Questions', "Previous Years' Papers"],
        'category-orange' => ['Chapter-wise Theory', 'Solved Examples', 'Practice Questions', "Previous Years' Papers"],
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
     * @return array<string, mixed> file, width, height, zone and book (null when the artwork keeps its own headline / has no placeholder book),
     *                              ink, tagline, hue, saturation, lightness, button, logo, logoDark, fixed, and for the category layouts taglineZone and featureZones
     */
    public static function spec(string $name): array
    {
        return self::manifest()[$name] ?? throw new InvalidArgumentException(
            "Unknown template [{$name}]. Available: " . implode(', ', self::names()) . '.'
        );
    }

    /** The feature labels a template shows by default (see ->features() to change them); the artwork's own icons are removed. @return string[] */
    public static function defaultFeatures(string $name): array
    {
        return self::FEATURES[$name] ?? [];
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
     * cover (white, grey, black) leaves the artwork as designed. $accent (a button colour as [r, g, b]) repaints the red "Shop Now"
     * button in that colour.
     *
     * @return array{scale:float,x:float,y:float,width:float,height:float}
     */
    public static function paint(string $name, Canvas $canvas, ?array $match = null, ?array $accent = null): array
    {
        $name = self::resolve($name, $match);
        $spec = self::spec($name);
        $w = $canvas->width();
        $h = $canvas->height();
        $scale = max($w / $spec['width'], $h / $spec['height']);
        $pw = $spec['width'] * $scale;
        $ph = $spec['height'] * $scale;
        $canvas->picture(self::matched($name, $match, $accent), 0, 0, $w, $h, 'cover');

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

    /** The colour variants of a template family: the ten generic ones by default, or the two 'category' ones. @return string[] */
    public static function variants(string $family = self::GENERAL): array
    {
        return array_values(array_filter(self::names(), fn (string $name) => self::familyOf($name) === $family));
    }

    public static function isGeneral(string $name): bool
    {
        return self::familyOf($name) === self::GENERAL;
    }

    /** 'general' or 'category' for the templates that come in several colours, else null. */
    public static function familyOf(string $name): ?string
    {
        foreach ([self::GENERAL, self::CATEGORY] as $family) {
            if ($name === $family || str_starts_with($name, $family . '-')) {
                return $family;
            }
        }

        return null;
    }

    /**
     * The template to draw: the generic and the category template become the colour variant nearest the cover's main colour (a dark
     * cover prefers the dark variant); every other name, and a cover without a real colour, stays as asked.
     */
    public static function resolve(string $name, ?array $match): string
    {
        $family = self::familyOf($name);
        if ($family === null || $match === null) {
            return $name;
        }
        [$hue, $saturation, $lightness] = Color::toHsl($match);
        if ($saturation < 0.18) {
            return $name;
        }

        $best = $name;
        $bestScore = INF;
        foreach (self::variants($family) as $variant) {
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

        // Entrance-exam books have their own banners; then titles that say what the book is: children's books, handwriting, "all subjects" ...
        if (($exam = self::entranceExam($label . ' ' . $title)) !== null) {
            return [$exam];
        }
        if (($kids = self::childrensBook(trim($label . ' ' . $title))) !== null) {
            return [$kids];
        }
        if (preg_match('/\b(all subjects|all-in-one|combo)\b/iu', $title) || (count($subjects) > 1 && Subjects::pool($label) === ['general'])) {
            return ['multi-subject'];
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
     * The entrance-exam banner for a label or title: NEET, MHT-CET or CUET have their own; two or more exams, JEE, or just "entrance
     * exam" use the banner that shows all three. Null when no entrance exam is mentioned.
     */
    private static function entranceExam(string $text): ?string
    {
        $found = [];
        foreach (['neet' => '/\bneet\b/iu', 'mht-cet' => '/\bm\.?h\.?t\.?[\s-]*cet\b|\bmht\b|\bcet\b/iu', 'cuet' => '/\bcuet\b/iu'] as $template => $pattern) {
            if (preg_match($pattern, $text)) {
                $found[] = $template;
            }
        }
        $other = preg_match('/\bjee\b/iu', $text) === 1;

        return match (true) {
            count($found) === 1 && !$other => $found[0],
            $found !== [] || $other || preg_match('/entrance exam/iu', $text) === 1 => 'entrance-exams',
            default => null,
        };
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

    /** The artwork, with its background recoloured to $match when that is a real colour, and its button repainted in $accent. */
    private static function matched(string $name, ?array $match, ?array $accent): GdImage
    {
        $plate = self::plate($name);
        if ($match === null || self::spec($name)['fixed']) {
            return $plate;   // no cover colour to follow, or artwork tied to one exam (NEET is green)
        }
        [$hue, $saturation] = Color::toHsl($match);
        if ($saturation < 0.18) {
            return $plate;   // a white, grey or black cover has no colour to match
        }
        $spec = self::spec($name);

        // The generic artwork has a colour variant instead of a recolouring.
        $out = self::familyOf($name) !== null ? $plate : Recolor::hue($plate, $spec['hue'], $spec['saturation'], $hue, max(0.15, min(0.6, $saturation * 0.5)));
        if ($accent !== null) {
            if ($out === $plate) {
                $out = imagecreatetruecolor(imagesx($plate), imagesy($plate));
                imagecopy($out, $plate, 0, 0, 0, 0, imagesx($plate), imagesy($plate));
            }
            $width = imagesx($out);
            $height = imagesy($out);
            [$x0, $y0, $x1, $y1] = $spec['button'];
            Recolor::button($out, [(int) floor($x0 * $width), (int) floor($y0 * $height), (int) ceil(($x1 - $x0) * $width), (int) ceil(($y1 - $y0) * $height)], $accent);
        }

        return $out;
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
