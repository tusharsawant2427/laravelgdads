<?php

namespace Packitifotech\Laragdads\Services\Ads;

use GdImage;

class VerticalAdsService extends AbstractAdsService
{
    public function createBanner(
        string $firstImagePath,
        string $secondImagePath,
        ?string $text,
        ?int $width,
        ?int $height,
        string $fontPath,
        string $backgroundStyle = 'stripes'
    ): GdImage {
        $resizeWidth =$resizeWidth ?? getimagesize($firstImagePath)[0];
        $resizeHeight =$resizeHeight ?? getimagesize($firstImagePath)[1];
        $backgroundImage = $this->createBackground($firstImagePath, $backgroundStyle, $fontPath, $width, $height, $text);
        $leftImage = $this->imageService->loadAndResizeImage($firstImagePath, $resizeWidth, $resizeHeight);
        $rightImage = $this->imageService->loadAndResizeImage($secondImagePath, $resizeWidth, $resizeHeight);

        $banner = $this->createLayout(
            $backgroundImage,
            $leftImage,
            $rightImage,
            $resizeWidth,
            $resizeHeight
        );
        if ($text) {
            // Sample from right side for secondary color
            $this->addTextToImage($banner, $text, $fontPath);
        }

        return $banner;
    }

    private function createLayout(
        GdImage $backgroundImage,
        GdImage $leftImage,
        GdImage $rightImage,
        int $resizeWidth,
        int $resizeHeight
    ): GdImage {
        imagecopyresampled($backgroundImage, $leftImage, 10, 10, 0, 0, 110, 130, $resizeWidth, $resizeHeight);
        imagecopyresampled($backgroundImage, $rightImage, 225, 10, 0, 0, 110, 130, $resizeWidth, $resizeHeight);

        return $backgroundImage;
    }
}
