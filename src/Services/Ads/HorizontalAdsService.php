<?php

namespace Packitifotech\Laragdads\Services\Ads;

use GdImage;

class HorizontalAdsService extends AbstractAdsService
{
    public function createBanner(
        string $firstImagePath,
        string $secondImagePath,
        ?string $text,
        ?int $resizeWidth,
        ?int $resizeHeight,
        string $fontPath,
        string $backgroundStyle = 'wave1'
    ): GdImage {
        $resizeWidth = $resizeWidth ?? getimagesize($firstImagePath)[0];
        $resizeHeight = $resizeHeight ?? getimagesize($firstImagePath)[1];
        $backgroundImage = $this->createBackground($firstImagePath, $backgroundStyle, $fontPath);
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
        imagecopyresampled($backgroundImage, $leftImage, 10, 10, 0, 0, 160, 180, $resizeWidth, $resizeHeight);
        imagecopyresampled($backgroundImage, $rightImage, 1030, 10, 0, 0, 160, 180, $resizeWidth, $resizeHeight);

        return $backgroundImage;
    }
}
