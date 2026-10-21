<?php

namespace Packitifotech\Laragdads\Ads\Templates;

/**
 * Maps a subject label ("Physics", "PCM", "Computer Science - I", "Eng/Mar/His", "Alegbra" ...) to the theme groups whose
 * templates suit it. Labels are normalised first: case, spacing, "&" / "and", common typos and abbreviations, so the same
 * subject written in different ways lands in the same place. Combined labels ("History + Geography", "Eng/Hin/Mar")
 * are split and resolved subject by subject.
 *
 * Groups: physics, chemistry, biology, maths, maths-advanced, languages, history, geography, civics, commerce, computer,
 * psychology, art, reasoning, sports, environment and general (the fallback and the pool for multi-subject books).
 */
final class Subjects
{
    /** @var string[] */
    public const GROUPS = [
        'physics', 'chemistry', 'biology', 'maths', 'maths-advanced', 'languages', 'history', 'geography', 'civics',
        'commerce', 'computer', 'psychology', 'art', 'reasoning', 'sports', 'environment', 'general',
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
        'art and craft' => ['art'], 'drawing and coloring' => ['art'],
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
            foreach (self::MAP[$subject] ?? [] as $g) {
                $groups[$g] = true;
            }
        }

        return $groups ? array_keys($groups) : ['general'];
    }

    /** Subject names in the label that this class does not know (typos not covered, new subjects). */
    public static function unknown(string $label): array
    {
        return array_values(array_filter(self::subjects($label), fn ($s) => !isset(self::MAP[$s])));
    }

    /**
     * The pool of template groups to choose from: a single group for a single theme, the union for a few related
     * sciences, and 'general' for a mix (languages + social studies + maths + science ...).
     *
     * @return string[]
     */
    public static function pool(string $label): array
    {
        $groups = self::groups($label);
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
