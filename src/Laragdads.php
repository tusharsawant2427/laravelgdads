<?php

namespace Packitifotech\Laragdads;

use ColorThief\ColorThief;
use Exception;
use GdImage;
use GDText\Box;
use GDText\Color;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class Laragdads
{
    /**
     * @param string $storagePath
     *
     * @return array //RGB
     */
    public static function getImagePrimaryColor(string $storagePath): array
    {
        $palette = ColorThief::getPalette($storagePath, 10);
        $primaryColor = null;
        $maxBrightness = -1;
        $brightnessThreshold = 300; // Threshold to exclude dark colors (black family)

        foreach ($palette as $color) {
            // Calculate the brightness of the color (simple sum of RGB components)
            $brightness = array_sum($color); // Sum of R, G, B values
            // Exclude dark colors (black or near black family) based on brightness threshold
            if ($brightness < $brightnessThreshold) {
                continue; // Skip dark colors
            }

            // Select the color with the highest brightness
            if ($brightness > $maxBrightness) {
                $maxBrightness = $brightness;
                $primaryColor = $color;
            }
        }
        // If no color was found (all colors were dark), fallback to the brightest color
        if ($primaryColor === null) {
            $primaryColor = $palette[0];
        }

        return $primaryColor;
    }

    /**
     * Get the most used color in an image using GD library, excluding white and white family colors.
     *
     * @param string $imagePath Path to the image.
     * @param int $whiteThreshold RGB value threshold to consider a color part of the white family.
     * @return array RGB of the most used color.
     */
    public static function getMostUsedColorExcludingWhiteFamily(string $imagePath, int $whiteThreshold = 200): array
    {
        // Load the image
        $image = imagecreatefromstring(file_get_contents($imagePath));

        if (!$image) {
            throw new Exception("Could not load image.");
        }

        // Get image dimensions
        $width = imagesx($image);
        $height = imagesy($image);

        // Array to store color counts
        $colorCount = [];

        // Loop through each pixel in the image
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                // Get the color index of the pixel
                $rgb = imagecolorat($image, $x, $y);

                // Extract red, green, and blue components
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                // Exclude white family colors (colors close to white)
                if ($r >= $whiteThreshold && $g >= $whiteThreshold && $b >= $whiteThreshold) {
                    continue;  // Skip if the color is part of the white family
                }

                // Use RGB as the key for color counting
                $colorKey = "{$r}_{$g}_{$b}";

                if (isset($colorCount[$colorKey])) {
                    $colorCount[$colorKey]++;
                } else {
                    $colorCount[$colorKey] = 1;
                }
            }
        }

        // If no colors are left after excluding white family, return black as the fallback
        if (empty($colorCount)) {
            return [0, 0, 0];  // Black as the fallback color
        }

        // Find the most frequent color
        arsort($colorCount);  // Sort by frequency (highest first)
        $mostUsedColor = key($colorCount);  // Get the color with the highest frequency

        // Extract RGB values from the key
        list($r, $g, $b) = explode('_', $mostUsedColor);

        // Free the image resource
        imagedestroy($image);

        // Return the most used color as an array
        return [(int)$r, (int)$g, (int)$b];
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

        $lightenFactor = 2.1;
        $darkenFactor = 1.2;

        $lightR = min(255, $palettes[0] * $lightenFactor);
        $lightG = min(255, $palettes[1] * $lightenFactor);
        $lightB = min(255, $palettes[2] * $lightenFactor);

        $darkR = max(0, $palettes[0] * $darkenFactor);
        $darkG = max(0, $palettes[1] * $darkenFactor);
        $darkB = max(0, $palettes[2] * $darkenFactor);


        for ($x = 0; $x < $imageWidth; $x++) {
            $interpolation = min($x / ($imageWidth / 2), (1 - ($x - $imageWidth / 2) / ($imageWidth / 2)));

            $r = (int)($lightR * (1 - $interpolation) + $darkR * $interpolation);
            $g = (int)($lightG * (1 - $interpolation) + $darkG * $interpolation);
            $b = (int)($lightB * (1 - $interpolation) + $darkB * $interpolation);

            $color = imagecolorallocate($backgroundImage, $r, $g, $b);

            imageline($backgroundImage, $x, 0, $x, $imageHeight, $color);
        }

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
     * @param string $storagePath
     * @param int $imageWidth = 1200
     * @param int $imageHeight = 200
     *
     * @return GdImage
     */
    private static function createSeamlessJoinedWaveBackground(string $storagePath, int $imageWidth = 1200, int $imageHeight = 200): GdImage
    {

        // Get primary and secondary colors from the image
        $primaryColor = self::getImagePrimaryColor($storagePath);
        $secondaryColor = self::getMostUsedColorExcludingWhiteFamily($storagePath);
        // Create the image canvas
        $backgroundImage = imagecreatetruecolor($imageWidth, $imageHeight);
        $white = imagecolorallocate($backgroundImage, 255, 255, 255); // Background color (white)
        $backgroundColor = ImageColorAllocate($backgroundImage, $primaryColor[0], $primaryColor[1], $primaryColor[2]);

        imagefill($backgroundImage, 0, 0, $backgroundColor); // Fill background with white

        // Generate the primary and secondary colors for filling the sections
        $colorPrimary = imagecolorallocate($backgroundImage, $secondaryColor[0], $secondaryColor[1], $secondaryColor[2]);

        // Random vertical offset for sine wave to make its position dynamic
        $offsetY = rand(30, $imageHeight / 2); // Random offset to control where the sine wave peaks
        $amplitude = 40; // Amplitude of the sine wave
        $frequency = 1; // Frequency of the sine wave, controlling how many peaks the wave will have
        $phaseShift = 100; // Random phase shift to change the starting point of the wave
        // Log::info("offsetY " . $offsetY);
        // Draw sine wave
        for ($x = 0; $x < $imageWidth; $x++) {
            // Calculate the sine wave's Y position at the current X coordinate
            $y = sin(($x + $phaseShift) * $frequency / $imageWidth * 2 * M_PI) * $amplitude + $offsetY;

            // Divide the image into two sections using the sine wave:
            // If the current Y position is above the sine wave, use the primary color
            // Else use the secondary color
            // if ($x < $imageWidth / 2) {
            //     // Left side, fill with primary color
            //     imageline($backgroundImage, $x, 0, $x, (int)$y, $colorPrimary);
            // } else {
            //     // Right side, fill with secondary color
            //     imageline($backgroundImage, $x, (int)$y, $x, $imageHeight, $colorSecondary);
            // }
            imageline($backgroundImage, $x, (int)$y, $x, $imageHeight, $colorPrimary);
        }

        // Return the generated image
        return $backgroundImage;
    }

    public static function createHorizontalAdsBanner(string $fImagepath, string $sImagePath, ?string $text, ?int $resizeWidth,  ?int $resizeHeight, string $fontPath): GdImage
    {
        $resizeWidth = empty($resizeWidth) ? getimagesize($fImagepath)[0] : $resizeWidth;
        $resizeHeight = empty($resizeHeight) ? getimagesize($fImagepath)[1] : $resizeHeight;

        $ext = File::extension($fImagepath);
        $primaryColor = self::getImagePrimaryColor($fImagepath);
        $secondColor = self::getMostUsedColorExcludingWhiteFamily($fImagepath); // Secondary color for blending
        $backgroundImage = Laragdads::createSeamlessJoinedWaveBackground($fImagepath);

        // Load left side image
        if ($ext == "png") {
            $leftSideImage = imagecreatefrompng($fImagepath);
        } elseif ($ext == "webp") {
            $leftSideImage = imagecreatefromwebp($fImagepath);
        } else {
            $leftSideImage = imagecreatefromjpeg($fImagepath);
        }
        $lImageWidth  = imagesx($leftSideImage);
        $lImageHeight = imagesy($leftSideImage);
        $resizeLImage = imagecreatetruecolor($resizeWidth, $resizeHeight);
        imagecopyresampled($resizeLImage, $leftSideImage, 0, 0, 0, 0, $resizeWidth, $resizeHeight, $lImageWidth, $lImageHeight);

        // Load right side image
        if ($ext == "png") {
            $rightSideImage = imagecreatefrompng($sImagePath);
        } elseif ($ext == "webp") {
            $rightSideImage = imagecreatefromwebp($sImagePath);
        } else {
            $rightSideImage = imagecreatefromjpeg($sImagePath);
        }
        $rImageWidth  = imagesx($rightSideImage);
        $rImageHeight = imagesy($rightSideImage);
        $resizeRImage = imagecreatetruecolor($resizeWidth, $resizeHeight);
        imagecopyresampled($resizeRImage, $rightSideImage, 0, 0, 0, 0, $resizeWidth, $resizeHeight, $rImageWidth, $rImageHeight);

        // Merge images into the background
        imagecopyresampled($backgroundImage, $resizeLImage, 10, 10, 0, 0, 160, 180, $resizeWidth, $resizeHeight);
        imagecopyresampled($backgroundImage, $resizeRImage, 1030, 10, 0, 0, 160, 180, $resizeWidth, $resizeHeight);

        // Text color and opacity
        $alpha = 0; // Full opacity (no transparency)

        // Extract RGB values for secondary color (background)
        $rBg = $primaryColor[0];
        $gBg = $primaryColor[1];
        $bBg = $primaryColor[2];

        // Blending font color: Use a lighter color to ensure visibility
        $rText = 255; // Bright white for high contrast
        $gText = 255; // Bright white for high contrast
        $bText = 255; // Bright white for high contrast

        // Allocate the font color with full opacity
        $blendedColor = imagecolorallocatealpha($backgroundImage, $rText, $gText, $bText, $alpha);

        // Set up the text box and apply shadow for visibility
        $box = new Box($backgroundImage);
        $box->setFontFace($fontPath);
        $box->setFontColor(new Color($rText, $gText, $bText, $alpha)); // Use high-contrast white color
        $box->setTextShadow(new Color(0, 0, 0, 50), 2, 2); // Strong shadow to help text stand out


        // Draw the main text on top of the outline
        $box->setFontSize(30);
        $box->setBox(350, 0, 550, 200);
        $box->setTextAlign('left', 'center');
        $box->draw($text); // Draw the actual text on the background

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
        } elseif ($ext == "webp") {
            $blockImage = imagecreatefromwebp($imagePath);
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
