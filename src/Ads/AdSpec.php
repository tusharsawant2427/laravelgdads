<?php

namespace Packitifotech\Laragdads\Ads;

use GdImage;

/**
 * Everything a layout needs to know about one ad. Built by AdBuilder.
 */
final class AdSpec
{
    public int $width = 1200;
    public int $height = 628;

    /** @var array<GdImage|string> */
    public array $images = [];
    public GdImage|string|null $logo = null;

    public ?string $headline = null;
    public ?string $subheadline = null;
    public ?string $cta = null;
    public ?string $badge = null;

    /** @var string[] up to 3 stacked title lines, each sized to fill the title column (promo layout) */
    public array $hero = [];

    /** @var array{style:string,prompt:?string,key:?string,seed:?int,strict:bool,hires:bool,topic:?string}|null AI Horde background request */
    public ?array $ai = null;

    /** Companion layout: handwritten line on a brush stroke above the title. */
    public ?string $tagline = null;

    /** @var array<int, array{0:string,1:string,2:string}> [icon, title, subtitle] feature cards (companion layout) */
    public array $highlights = [];

    /** @var string[] handwritten slogan words, e.g. ['Concepts', 'Practice', 'Confidence'] */
    public array $slogan = [];

    /** Picture on a white background melted into the bottom-right of the page (multiply blend). */
    public GdImage|string|null $artwork = null;

    public ?string $scriptFontPath = null;

    /** Name of a subject banner template (Templates\\TemplateLibrary). */
    public ?string $template = null;

    /** Subject label that picked the template, e.g. "Physics + Chemistry + Maths" (informational). */
    public ?string $subject = null;

    public GdImage|string|null $footerLogo = null;
    /** @var string[] short selling points, rendered as a tick list when there is room */
    public array $features = [];

    public string $fontPath = '';
    public ?string $bodyFontPath = null;

    public string $layout = 'auto';
    public string $background = 'auto';
    /** Soft shadow behind headline and sub-headline (automatic on AI-photo strips). */
    public bool $textShadow = false;

    /** null = layout default; left | center | right */
    public ?string $textAlign = null;

    /** Soft panel behind the text for busy backgrounds. null = automatic (on with an AI background). */
    public ?bool $textPanel = null;
    public string $imageFit = 'cover';
    public bool $shadows = true;

    /** Remove a flat white packshot background: null = automatic. */
    public ?bool $cutout = null;

    public ?Theme $theme = null;
    public GdImage|string|null $themeSource = null;

    public string $format = 'png';
    public int $quality = 90;

    public function aspectRatio(): float
    {
        return $this->width / max(1, $this->height);
    }

    public function hasImages(): bool
    {
        return $this->images !== [];
    }
}
