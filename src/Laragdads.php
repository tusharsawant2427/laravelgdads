<?php

namespace Packitifotech\Laragdads;

use GdImage;
use Packitifotech\Laragdads\Ads\AdBuilder;
use Illuminate\Support\Facades\Storage;
use Packitifotech\Laragdads\Services\Ads\HorizontalAdsService;
use Packitifotech\Laragdads\Services\Ads\VerticalAdsService;
use Packitifotech\Laragdads\Services\Image\ImageService;

class Laragdads
{
    /**
     * Start building a modern ad:
     *
     *   Laragdads::ad()->size(AdSize::SQUARE)->images($cover)->headline('...')->cta('Buy now')->saveTo('ads/a.png');
     */
    public static function ad(): AdBuilder
    {
        return AdBuilder::make();
    }

    public static function createHorizontalAdsBanner(
        string $firstImagePath,
        string $secondImagePath,
        ?string $text,
        ?int $width = ImageService::DEFAULT_BANNER_WIDTH,
        ?int $height = ImageService::DEFAULT_IMAGE_HEIGHT,
        string $fontPath,
        string $backgroundStyle = 'wave1'
    ): GdImage {
        return app(HorizontalAdsService::class)->createBanner(
            $firstImagePath,
            $secondImagePath,
            $text,
            $width,
            $height,
            $fontPath,
            $backgroundStyle
        );
    }

    public static function createVerticalAdsBanner(
        string $firstImagePath,
        string $secondImagePath,
        ?string $text,
        ?int $resizeWidth,
        ?int $resizeHeight,
        string $fontPath,
        string $backgroundStyle = 'wave1'
    ): GdImage {
        return app(VerticalAdsService::class)->createBanner(
            $firstImagePath,
            $secondImagePath,
            $text,
            $resizeWidth,
            $resizeHeight,
            $fontPath,
            $backgroundStyle
        );
    }

    /**
     * Save ads banner as PNG
     */
    public static function saveAdsBannerPng(
        GdImage $gdImage,
        string $storagePath,
        string $disk = "public"
    ): string {
        $tempFile = tempnam(sys_get_temp_dir(), 'png_');
        imagepng($gdImage, $tempFile, 9, PNG_ALL_FILTERS);

        Storage::disk($disk)->put($storagePath, fopen($tempFile, 'rb'));
        unlink($tempFile);
        imagedestroy($gdImage);

        return $storagePath;
    }

    /**
     * Save ads banner as JPG
     */
    public static function saveAdsBannerJpg(GdImage $gdImage, string $storagePath,  string $disk = "public"): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'jpeg_');
        imagejpeg($gdImage, $tempFile, 90);

        Storage::disk($disk)->put($storagePath, fopen($tempFile, 'rb'));
        unlink($tempFile);
        imagedestroy($gdImage);
        return $storagePath;
    }
}
