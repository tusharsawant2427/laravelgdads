<?php

namespace Packitifotech\Laragdads;

use ColorThief\ColorThief;
use GdImage;
use GDText\Box;
use GDText\Color;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class Laragdads
{
    /**
     * @param string $storagePath
     *
     * @return array //RGB
     */
    public static function getImagePrimaryColor(string $storagePath): array
    {
        $palettes = ColorThief::getColor($storagePath);
        return $palettes;
    }

    /**
     * @param string $storagePath
     *
     * @return array //RGB
     */
    protected static function getImageSecondColor(string $storagePath): array
    {
        $palette = ColorThief::getColor($storagePath);
        $seconfPalettes = ColorThief::getPalette($storagePath);
        $percenatgeArray = [];
        for ($i = 0; $i < count($seconfPalettes); $i++) {
            $absoluteColorDistance = self::absoluteColorDistance($palette, $seconfPalettes[$i]);
            $percenatgeArray = array_merge($percenatgeArray, [$i => ['spalette' => $seconfPalettes[$i],  'absoluteColorDistance' => $absoluteColorDistance]]);
        }
        $percenatgeArray = Arr::sortRecursive($percenatgeArray, SORT_REGULAR, false);
        return !empty($percenatgeArray) ? Arr::last($percenatgeArray)['spalette'] : $seconfPalettes[1];
    }

    /**
     * @param array $color1
     * @param array $color2
     *
     * @return int
     */
    private static function  absoluteColorDistance(array $color1, array $color2): int
    {
        return
            abs($color1[0] - $color2[0]) +
            abs($color1[1] - $color2[1]) +
            abs($color1[2] - $color2[2]);
    }

    /**
     * @param string $storagePath
     * @param int $imageWidth = 1200
     * @param int $imageHeight = 200
     *
     * @return GdImage
     */
    private static function createHorizontalWithPrimaryColorBanner(string $storagePath, int $imageWidth = 1200, int $imageHeight = 200): GdImage
    {
        $palettes = self::getImagePrimaryColor($storagePath);
        $backgroundImage = imagecreatetruecolor($imageWidth, $imageHeight);
        $backgroundColor = ImageColorAllocate($backgroundImage, $palettes[0], $palettes[1], $palettes[2]);
        imagefill($backgroundImage, 0, 0, $backgroundColor);
        return $backgroundImage;
    }

    /**
     * @param string $storagePath
     * @param int $imageWidth = 300
     * @param int $imageHeight = 300
     *
     * @return GdImage
     */
    private static function createBlockWithPrimaryColorBanner(string $storagePath, int $imageWidth = 300, int $imageHeight = 300): GdImage
    {
        $palettes = self::getImagePrimaryColor($storagePath);
        $backgroundImage = imagecreatetruecolor($imageWidth, $imageHeight);
        $backgroundColor = ImageColorAllocate($backgroundImage, $palettes[0], $palettes[1], $palettes[2]);
        imagefill($backgroundImage, 0, 0, $backgroundColor);
        return $backgroundImage;
    }


    /**
     * @param string $fImagepath
     * @param string $sImagePath
     * @param string|null $text
     * @param int|null $resizeWidth
     * @param int|null $resizeHeight
     *
     * @return GdImage
     */
    public static function createHorizontalAdsBanner(string $fImagepath, string $sImagePath, ?string $text, ?int $resizeWidth,  ?int $resizeHeight, string $fontPath): GdImage
    {
        $resizeWidth = empty($resizeWidth) ? getimagesize($fImagepath)[0] : $resizeWidth;
        $resizeHeight = empty($resizeHeight) ? getimagesize($fImagepath)[1] : $resizeHeight;

        $ext = File::extension($fImagepath);
        $secondColor = self::getImageSecondColor($fImagepath);
        $backgroundImage = Laragdads::createHorizontalWithPrimaryColorBanner($fImagepath);
        if ($ext == "png") {
            $leftSideImage = imagecreatefrompng($fImagepath);
        } else {
            $leftSideImage = imagecreatefromjpeg($fImagepath);
        }
        $lImageWidth  = imagesx($leftSideImage);
        $lImageHeight = imagesy($leftSideImage);
        $resizeLImage = imagecreatetruecolor($resizeWidth, $resizeHeight);
        imagecopyresampled($resizeLImage, $leftSideImage, 0, 0, 0, 0, $resizeWidth, $resizeHeight, $lImageWidth, $lImageHeight);

        if ($ext == "png") {
            $rightSideImage = imagecreatefrompng($sImagePath);
        } else {
            $rightSideImage = imagecreatefromjpeg($sImagePath);
        }
        $rImageWidth  = imagesx($rightSideImage);
        $rImageHeight = imagesy($rightSideImage);
        $resizeRImage = imagecreatetruecolor($resizeWidth, $resizeHeight);
        imagecopyresampled($resizeRImage, $rightSideImage, 0, 0, 0, 0, $resizeWidth, $resizeHeight, $rImageWidth, $rImageHeight);
        imagecopyresampled($backgroundImage, $resizeLImage, 10, 10, 0, 0, 160, 180, $resizeWidth, $resizeHeight);
        imagecopyresampled($backgroundImage, $resizeRImage, 1030, 10, 0, 0, 160, 180, $resizeWidth, $resizeHeight);
        $box = new Box($backgroundImage);
        $box->setFontFace($fontPath);
        $box->setFontColor(new Color($secondColor[0], $secondColor[1], $secondColor[2]));
        $box->setTextShadow(new Color(0, 0, 0, 50), 0, 0);
        $box->setFontSize(30);
        $box->setBox(350, 0, 550, 200);
        $box->setTextAlign('left', 'center');
        $box->draw($text);

        return $backgroundImage;
    }


    /**
     * @param string $imagePath
     * @param int|null $resizeWidth
     * @param int|null $resizeHeight
     *
     * @return GdImage
     */
    public static function createBlockAdsBanner(string $imagePath, ?int $resizeWidth,  ?int $resizeHeight): GdImage
    {
        $resizeWidth = empty($resizeWidth) ? getimagesize($imagePath)[0] : $resizeWidth;
        $resizeHeight = empty($resizeHeight) ? getimagesize($imagePath)[1] : $resizeHeight;
        $ext = File::extension($imagePath);
        $backgroundImage = Laragdads::createBlockWithPrimaryColorBanner($imagePath);
        if ($ext == "png") {
            $blockImage = imagecreatefrompng($imagePath);
        } else {
            $blockImage = imagecreatefromjpeg($imagePath);
        }
        $imageWidth  = imagesx($blockImage);
        $imageHeight = imagesy($blockImage);
        $resizeImage = imagecreatetruecolor($resizeWidth, $resizeHeight);
        imagecopyresampled($resizeImage, $blockImage, 0, 0, 0, 0, $resizeWidth, $resizeHeight, $imageWidth, $imageHeight);
        imagecopyresampled($backgroundImage, $resizeImage, 10, 0, 0, 0, 280, 320, $resizeWidth, $resizeHeight);
        return $backgroundImage;
    }

    /**
     * @param GdImage $gdImage
     * @param string $storagePath
     *
     * @return string
     */
    public static function saveAdsBannerPng(GdImage $gdImage, string $storagePath): string
    {
        header("Content-type: image/png;");
        imagepng($gdImage, $storagePath, 9, PNG_ALL_FILTERS);
        imagedestroy($gdImage);
        return $storagePath;
    }

    /**
     * @param GdImage $gdImage
     * @param string $storagePath
     *
     * @return string
     */
    public static function saveAdsBannerJpg(GdImage $gdImage, string $storagePath): string
    {
        header("Content-type: image/jpeg;");
        imagejpeg($gdImage, $storagePath, 9);
        imagedestroy($gdImage);
        return $storagePath;
    }
}
