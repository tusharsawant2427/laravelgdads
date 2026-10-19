<?php

namespace Packitifotech\Laragdads\Ads;

/**
 * Common ad placements. Use ->width() / ->height(), or pass the case straight to AdBuilder::size().
 */
enum AdSize: string
{
    case MEDIUM_RECTANGLE = 'medium_rectangle';
    case LARGE_RECTANGLE = 'large_rectangle';
    case LEADERBOARD = 'leaderboard';
    case LARGE_LEADERBOARD = 'large_leaderboard';
    case BILLBOARD = 'billboard';
    case HALF_PAGE = 'half_page';
    case SKYSCRAPER = 'skyscraper';
    case MOBILE_BANNER = 'mobile_banner';
    case LARGE_MOBILE_BANNER = 'large_mobile_banner';
    case SQUARE = 'square';
    case PORTRAIT = 'portrait';
    case STORY = 'story';
    case LANDSCAPE = 'landscape';
    case STRIP = 'strip';

    public function width(): int
    {
        return $this->dimensions()[0];
    }

    public function height(): int
    {
        return $this->dimensions()[1];
    }

    /** @return array{0:int,1:int} */
    public function dimensions(): array
    {
        return match ($this) {
            self::MEDIUM_RECTANGLE => [300, 250],
            self::LARGE_RECTANGLE => [336, 280],
            self::LEADERBOARD => [728, 90],
            self::LARGE_LEADERBOARD => [970, 90],
            self::BILLBOARD => [970, 250],
            self::HALF_PAGE => [300, 600],
            self::SKYSCRAPER => [160, 600],
            self::MOBILE_BANNER => [320, 50],
            self::LARGE_MOBILE_BANNER => [320, 100],
            self::SQUARE => [1080, 1080],      // Instagram / Facebook feed
            self::PORTRAIT => [1080, 1350],    // Instagram portrait
            self::STORY => [1080, 1920],       // Stories / Reels / TikTok
            self::LANDSCAPE => [1200, 628],    // Facebook / LinkedIn / X link ads
            self::STRIP => [1200, 200],        // the original Laragdads banner
        };
    }

    public function aspectRatio(): float
    {
        return $this->width() / $this->height();
    }
}
