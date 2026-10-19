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

    /** @var array<int, string|array{0:string,1:float}> info lines for the promo layout; '---' is a divider */
    public array $details = [];

    /** @var array{0:string,1:string}|null plaque such as ['Std. X', 'ENGLISH MEDIUM'] */
    public ?array $tag = null;

    /** @var array{lines:string[],color:?array}|null round seal, e.g. INCLUDES / 2025 / BOARD QUESTION PAPERS */
    public ?array $seal = null;

    public GdImage|string|null $footerLogo = null;
    public string $ctaStyle = 'solid';
    public bool $book3d = false;

    /** @var string[] short selling points, rendered as a tick list when there is room */
    public array $features = [];

    public string $fontPath = '';
    public ?string $bodyFontPath = null;

    public string $layout = 'auto';
    public string $background = 'auto';
    public string $imageSide = 'left';
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
