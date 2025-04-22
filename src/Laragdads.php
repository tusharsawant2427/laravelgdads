<?php

namespace Packitifotech\Laragdads;

use GdImage;
use Illuminate\Support\Facades\Storage;
use Packitifotech\Laragdads\Services\Ads\BlockAdsService;
use Packitifotech\Laragdads\Services\Ads\HorizontalAdsService;
use Packitifotech\Laragdads\Services\Ads\VerticalAdsService;
use Packitifotech\Laragdads\Services\Image\ImageService;

class Laragdads
{
    public function __construct(
        private HorizontalAdsService $horizontalAdsService,
        private BlockAdsService $blockAdsService
    ) {
        $this->horizontalAdsService = $horizontalAdsService;
        $this->blockAdsService = $blockAdsService;
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

    public static function createBlockAdsBanner(
        string $imagePath,
        ?string $text,
        ?int $resizeWidth,
        ?int $resizeHeight,
        string $fontPath,
        string $backgroundStyle = 'wave1'
    ): GdImage {
        return app(BlockAdsService::class)->createBanner(
            $imagePath,
            $text,
            $resizeWidth,
            $resizeHeight,
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
        header("Content-type: image/png");

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
        header("Content-type: image/jpeg");
        $tempFile = tempnam(sys_get_temp_dir(), 'jpeg_');
        imagejpeg($gdImage, $tempFile, 9);

        Storage::disk($disk)->put($storagePath, fopen($tempFile, 'rb'));
        unlink($tempFile);
        imagedestroy($gdImage);
        return $storagePath;
    }
}
