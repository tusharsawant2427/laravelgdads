<?php

namespace Packitifotech\Laragdads\Ads\Templates;

/**
 * Maps a subject label ("Physics", "PCM", "Computer Science - I", "Eng/Mar/His", "Alegbra" ...) to the theme groups whose
 * templates suit it. Labels are normalised first: case, spacing, "&" / "and", common typos and abbreviations, so the same
 * subject written in different ways lands in the same place. Combined labels ("History + Geography", "Eng/Hin/Mar")
 * are split and resolved subject by subject.
 *
 * Groups: physics, chemistry, biology, maths, maths-advanced, languages, history, geography, civics, commerce, computer,
 * psychology, art, reasoning, sports, environment, kids (colouring, activity, rhymes, stories, alphabets ...) and general (the
 * fallback and the pool for multi-subject books). Titles work too: when a label is not a known subject, the words in it
 * ("Colouring Book for 5+ Year Old Kids", "MHT-CET Physics Notes") are matched against keywords.
 */
final class Subjects
{
    /** @var string[] */
    public const GROUPS = [
        'physics', 'chemistry', 'biology', 'maths', 'maths-advanced', 'languages', 'history', 'geography', 'civics',
        'commerce', 'computer', 'psychology', 'art', 'reasoning', 'sports', 'environment', 'kids', 'general',
    ];

    /**
     * Words that identify a subject inside a longer text such as a product title. Checked in order of appearance in the text.
     *
     * @var array<string, string[]>
     */
    private const KEYWORDS = [
        'coloring' => ['kids', 'art'], 'activity' => ['kids'], 'kids' => ['kids'], 'children' => ['kids'], 'nursery' => ['kids'],
        'rhymes' => ['kids'], 'alphabet' => ['kids'], 'preschool' => ['kids'], 'drawing' => ['art'],
        'physics' => ['physics'], 'chemistry' => ['chemistry'], 'biology' => ['biology'], 'maths' => ['maths'],
        'mathematics' => ['maths'], 'algebra' => ['maths-advanced'], 'calculus' => ['maths-advanced'],
        'trigonometry' => ['maths-advanced'], 'english' => ['languages'], 'hindi' => ['languages'], 'marathi' => ['languages'],
        'sanskrit' => ['languages'], 'grammar' => ['languages'], 'history' => ['history'], 'geography' => ['geography'],
        'civics' => ['civics'], 'accounts' => ['commerce'], 'accountancy' => ['commerce'], 'economics' => ['commerce'],
        'computer' => ['computer'], 'psychology' => ['psychology'], 'reasoning' => ['reasoning'], 'evs' => ['environment'],
    ];

    /** Short forms used inside combined labels such as "Eng/Hin/Mar/His/Geo/Math/Sci". */
    private const ABBREVIATIONS = [
        'eng' => 'english', 'hin' => 'hindi', 'mar' => 'marathi', 'his' => 'history', 'geo' => 'geography',
        'math' => 'maths', 'mathematics' => 'maths', 'sci' => 'science', 'san' => 'sanskrit', 'eco' => 'economics',
        'chem' => 'chemistry', 'phy' => 'physics', 'bio' => 'biology', 'comp' => 'computer',
    ];

    /** Spelling variants seen in real lists. */
    private const TYPOS = [
        'alegbra' => 'algebra', 'algebr' => 'algebra', 'trignometry' => 'trigonometry', 'trigonometri' => 'trigonometry',
        'knowlegde' => 'knowledge', 'knowldge' => 'knowledge', 'colouring' => 'coloring',
    ];

    /**
     * Canonical subject => groups. Order matters: the first group is the subject's main theme.
     *
     * @var array<string, string[]>
     */
    private const MAP = [
        // sciences
        'physics' => ['physics'], 'chemistry' => ['chemistry'], 'biology' => ['biology'],
        'science' => ['physics', 'chemistry', 'biology'],
        'pcm' => ['physics', 'chemistry', 'maths'], 'pcb' => ['physics', 'chemistry', 'biology'],
        'pcmb' => ['physics', 'chemistry', 'maths', 'biology'],
        // maths
        'maths' => ['maths'], 'applied maths' => ['maths'],
        'algebra' => ['maths-advanced'], 'trigonometry' => ['maths-advanced'], 'vectors & 3d geometry' => ['maths-advanced'],
        'coordinate geometry' => ['maths-advanced'], 'calculus' => ['maths-advanced'],
        // languages
        'english' => ['languages'], 'hindi' => ['languages'], 'marathi' => ['languages'], 'sanskrit' => ['languages'],
        'lokvani' => ['languages'], 'vocabulary' => ['languages'], 'grammar' => ['languages'],
        // social studies
        'history' => ['history'], 'geography' => ['geography'], 'social science' => ['history', 'geography', 'civics'],
        'political science' => ['civics'], 'sociology' => ['civics'],
        // commerce
        'accounts' => ['commerce'], 'book keeping and accountancy' => ['commerce'], 'economics' => ['commerce'],
        'oc' => ['commerce'], 'sp' => ['commerce'], 'hrm' => ['commerce'], 'marketing' => ['commerce'],
        'business studies' => ['commerce'], 'business studies and entrepreneurship' => ['commerce'],
        'financial literacy' => ['commerce'],
        // technology
        'computer' => ['computer'], 'computer science - i' => ['computer'], 'computer science - ii' => ['computer'],
        'information technology' => ['computer'], 'information communication and technology' => ['computer'],
        'applied electronics' => ['computer'], 'digital electronics' => ['computer'],
        // others
        'psychology' => ['psychology'], 'value education' => ['psychology'],
        'art and craft' => ['art'], 'drawing and coloring' => ['art'], 'drawing' => ['art'],
        // children's books
        'kids' => ['kids'], 'children' => ['kids'], 'coloring' => ['kids', 'art'], 'coloring book' => ['kids', 'art'],
        'activity' => ['kids'], 'activity book' => ['kids'], 'nursery' => ['kids'], 'rhymes' => ['kids'],
        'stories' => ['kids'], 'alphabet' => ['kids'], 'abc' => ['kids'], 'preschool' => ['kids'], 'early learning' => ['kids'],
        'general test' => ['reasoning'], 'general knowledge' => ['reasoning'], 'reasoning' => ['reasoning'],
        'physical education' => ['sports'], 'defence studies' => ['sports'],
        'evs' => ['environment'], 'water security' => ['environment'],
        'ems' => ['general'],
    ];

    /** Normalised form of a single subject name. */
    public static function normalize(string $label): string
    {
        $s = mb_strtolower(trim($label));
        $s = preg_replace('/\s*&\s*/u', ' and ', $s);
        $s = preg_replace('/[.,()]+/u', ' ', $s);
        $s = preg_replace('/\s+/u', ' ', $s);
        $s = trim($s);

        $s = str_replace(['vectors and 3d geometry'], ['vectors & 3d geometry'], $s);
        $s = preg_replace('/\bcomputer science\s*-?\s*(i{1,2}|1|2)\b/u', 'computer science - $1', $s);
        $s = preg_replace_callback('/computer science - (1|2)$/u', fn ($m) => 'computer science - ' . str_repeat('i', (int) $m[1]), $s);
        $s = preg_replace('/\b(applied)\s+mathematics\b/u', 'applied maths', $s);

        $words = [];
        foreach (explode(' ', $s) as $w) {
            $words[] = self::TYPOS[$w] ?? $w;
        }
        $s = implode(' ', $words);
        $s = preg_replace('/\binformation communication and technology\b/u', 'information communication and technology', $s);
        $s = preg_replace('/\bgeneral knowlegde\b/u', 'general knowledge', $s);

        return self::ABBREVIATIONS[$s] ?? $s;
    }

    /** Split a combined label into its subjects: "A + B", "A/B", "A, B". Things like "Vectors & 3D" stay whole. */
    public static function split(string $label): array
    {
        $parts = preg_split('/\s*[+\/,]\s*/u', $label) ?: [$label];

        return array_values(array_filter(array_map('trim', $parts), 'strlen'));
    }

    /**
     * Canonical subject names found in the label, in order, without duplicates. Unknown names are returned as typed
     * (normalised) so callers can report them.
     *
     * @return string[]
     */
    public static function subjects(string $label): array
    {
        $out = [];
        foreach (self::split($label) as $part) {
            $n = self::normalize($part);
            $out[$n] = true;
        }

        return array_keys($out);
    }

    /** @return string[] groups for the label, in order of first appearance; ['general'] when nothing is recognised */
    public static function groups(string $label): array
    {
        $groups = [];
        foreach (self::subjects($label) as $subject) {
            foreach (self::resolve($subject) as $g) {
                $groups[$g] = true;
            }
        }

        return $groups ? array_keys($groups) : ['general'];
    }

    /** Groups for one normalised subject: its entry in the catalogue, else the keywords found in the text. */
    private static function resolve(string $subject): array
    {
        if (isset(self::MAP[$subject])) {
            return self::MAP[$subject];
        }
        $groups = [];
        foreach (preg_split('/[^a-z0-9]+/u', $subject, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
            $word = self::TYPOS[$word] ?? $word;
            foreach (self::KEYWORDS[$word] ?? [] as $g) {
                $groups[$g] = true;
            }
        }

        return array_keys($groups);
    }

    /** Subject names in the label that this class does not know (typos not covered, new subjects). */
    public static function unknown(string $label): array
    {
        return array_values(array_filter(self::subjects($label), fn ($s) => self::resolve($s) === []));
    }

    /**
     * The pool of template groups to choose from: a single group for a single theme, the union for a few related
     * sciences, and 'general' for a mix (languages + social studies + maths + science ...). The optional title (a product
     * name such as "Blossom Colouring Book for 5+ Year Old Kids") refines the choice: see above.
     *
     * @return string[]
     */
    public static function pool(string $label, ?string $title = null): array
    {
        $groups = self::groups($label);

        if ($title !== null && trim($title) !== '') {
            // The title can say more than the subject filter: a colouring book filed under "English" is still a children's book,
            // and an unrecognised subject falls back to what the title is about.
            $found = array_keys(array_flip(array_merge(...array_map(fn ($s) => self::resolve($s), self::subjects($title))) ?: []));
            if (in_array('kids', $found, true)) {
                $groups = ['kids'];
            } elseif ($groups === ['general'] && $found !== []) {
                $groups = $found;
            }
        }
        if (in_array('kids', $groups, true)) {
            return ['kids'];   // a children's book keeps its playful look whatever else is mentioned
        }
        if (count($groups) === 1) {
            return $groups;
        }
        foreach ([['physics', 'chemistry', 'biology', 'maths', 'maths-advanced'], ['history', 'geography', 'civics']] as $family) {
            if (!array_diff($groups, $family)) {
                return $groups;   // related subjects: pick from all of their themes
            }
        }

        return ['general'];
    }

    /** @return array<string, string[]> canonical subject => groups */
    public static function catalogue(): array
    {
        return self::MAP;
    }
}
