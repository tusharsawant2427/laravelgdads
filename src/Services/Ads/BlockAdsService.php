<?php

namespace Packitifotech\Laragdads\Services\Ads;

use GdImage;

class BlockAdsService extends AbstractAdsService
{
    public function createBanner(
        string $imagePath,
        ?string $text,
        ?int $resizeWidth,
        ?int $resizeHeight,
        string $fontPath,
        string $backgroundStyle = 'wave1'
    ): GdImage {
        $resizeWidth =$resizeWidth ?? getimagesize($imagePath)[0];
        $resizeHeight =$resizeHeight ?? getimagesize($imagePath)[1];
        $banner = $this->imageService->loadAndResizeImage($imagePath, $resizeWidth, $resizeHeight);
        return $banner;
    }

    private function createLayout(
        GdImage $backgroundImage,
        GdImage $image,
        int $resizeWidth,
        int $resizeHeight
    ): GdImage {

        $imageWidth  = imagesx($image);
        $imageHeight = imagesy($image);
        imagecopyresampled($backgroundImage, $image, 10, 10, 10, 10, $imageWidth, $imageHeight, $resizeWidth, $resizeHeight);
        return $backgroundImage;
    }
}
