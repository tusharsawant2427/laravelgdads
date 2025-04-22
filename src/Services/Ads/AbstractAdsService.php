<?php

namespace Packitifotech\Laragdads\Services\Ads;

use GdImage;
use Packitifotech\Laragdads\Services\Image\ImageService;
use GDText\Box;
use GDText\Color;
abstract class AbstractAdsService
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    protected function createBackground(string $imagePath, string $backgroundStyle = 'wave1', $fontPath="",  int $imageWidth = ImageService::DEFAULT_BANNER_WIDTH,
    int $imageHeight = ImageService::DEFAULT_BANNER_HEIGHT): GdImage
    {
        return match($backgroundStyle) {
            'wave1' => $this->imageService->createSeamlessJoinedWaveBackground1($imagePath, $imageWidth, $imageHeight),
            'abstract' => $this->imageService->createAbstractPatternedBackground($imagePath, $imageWidth, $imageHeight),
            // 'geometric' => $this->imageService->createGeometricPatternBackground($imagePath),
            'stripes' => $this->imageService->createDiagonalStripesBackground($imagePath, $imageWidth, $imageHeight),
            'radial' => $this->imageService->createRadialGradientBackground($imagePath, $imageWidth, $imageHeight),
            'marketing' => $this->imageService->createMarketingAgencyBanner($imagePath,  null, $fontPath, $imageWidth, $imageHeight),
            'spotlight' => $this->imageService->createSpotlightBackground($imagePath, $imageWidth, $imageHeight),
            'burst' => $this->imageService->createBurstPatternBackground($imagePath, $imageWidth, $imageHeight),
            'mobile-dots' => $this->imageService->createModernDotsPattern($imagePath, $imageWidth, $imageHeight),
            'mobile-card' => $this->imageService->createMaterialCardBackground($imagePath, $imageWidth, $imageHeight),
            'mobile-diagonal' => $this->imageService->createDiagonalMobilePattern($imagePath, $imageWidth, $imageHeight),
            'mobile-flat' => $this->imageService->createFlatMobileUIBackground($imagePath, $imageWidth, $imageHeight),
            'medical' => $this->imageService->createMedicalBannerBackground($imagePath, $imageWidth, $imageHeight),
            default => $this->imageService->createSeamlessJoinedWaveBackground($imagePath, $imageWidth, $imageHeight),
        };
    }

    protected function addTextToImage(GdImage $image, string $text, string $fontPath): void
    {
        $box = new Box($image);
        $box->setFontFace($fontPath);
        $box->setFontColor(new Color(255, 255, 255, 0));
        $box->setTextShadow(new Color(0, 0, 0, 50), 2, 2);
        $box->setFontSize(30);
        $box->setBox(350, 0, 550, 200);
        $box->setTextAlign('left', 'center');
        $box->setLineHeight(1.5); // Set line height for wrapping
        $box->draw($this->mb_wordwrap($text, 50, "\n"));
    }

    private function mb_wordwrap(string $text, int $width, string $break = "\n"): string
    {
        return preg_replace('/(.{1,' . $width . '})(\s+|$)/uS', '$1' . $break, $text);
    }

    public  function createBlockAdsBanner(string $imagePath): GdImage
    {
        return $this->imageService->createBlockWithPrimaryColorBanner($imagePath);
    }
}
