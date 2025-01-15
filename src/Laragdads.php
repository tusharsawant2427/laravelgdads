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
use PhpParser\Node\Stmt\Else_;

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
        $colorUsageCount = [];

        // Count occurrences of each color
        foreach ($palette as $color) {
            $colorKey = implode(',', $color); // Convert color to a string key (e.g., "255,255,255")
            if (!isset($colorUsageCount[$colorKey])) {
                $colorUsageCount[$colorKey] = 0;
            }
            $colorUsageCount[$colorKey]++;
        }

        // Get the most used color
        arsort($colorUsageCount); // Sort by usage count in descending order
        $mostUsedColorKey = array_key_first($colorUsageCount); // Get the first key (most used color)
        $mostUsedColor = explode(',', $mostUsedColorKey); // Convert back to RGB array

        return array_map('intval', $mostUsedColor); // Ensure the color values are integers
    }

    /**
     * Get the most used color in an image using GD library, excluding white and black family colors.
     *
     * @param string $imagePath Path to the image.
     * @param int $whiteThreshold RGB value threshold to consider a color part of the white family.
     * @param int $blackThreshold RGB value threshold to consider a color part of the black family.
     * @return array RGB of the most used color.
     */
    public static function getMostUsedColorExcludingWhiteAndBlackFamilies(string $imagePath, int $whiteThreshold = 200, int $blackThreshold = 50): array
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
                    continue; // Skip white family
                }

                // Exclude black family colors (colors close to black)
                if ($r <= $blackThreshold && $g <= $blackThreshold && $b <= $blackThreshold) {
                    continue; // Skip black family
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

        // If no colors are left after exclusions, return a fallback color
        if (empty($colorCount)) {
            return [128, 128, 128]; // Fallback to a neutral gray
        }

        // Find the most frequent color
        arsort($colorCount); // Sort by frequency (highest first)
        $mostUsedColor = key($colorCount); // Get the color with the highest frequency

        // Extract RGB values from the key
        list($r, $g, $b) = explode('_', $mostUsedColor);

        // Free the image resource
        imagedestroy($image);

        // Return the most used color as an array
        return [(int)$r, (int)$g, (int)$b];
    }


    /**
     * Make a color faint by blending it with white.
     *
     * @param array $color RGB array ([R, G, B]).
     * @param float $blendFactor The factor to blend with white (0.0 = no change, 1.0 = completely white).
     * @return array Fainter RGB color.
     */
    public static function makeColorFaint(array $color, float $blendFactor = 0.3): array
    {
        return [
            (int) min(255, $color[0] + (255 - $color[0]) * $blendFactor),
            (int) min(255, $color[1] + (255 - $color[1]) * $blendFactor),
            (int) min(255, $color[2] + (255 - $color[2]) * $blendFactor),
        ];
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
     * @param int $imageWidth
     * @param int $imageHeight
     *
     * @return GdImage
     */
    private static function createSeamlessJoinedWaveBackground(string $storagePath, int $imageWidth = 1200, int $imageHeight = 200): GdImage
    {
        $primaryColor = self::getImagePrimaryColor($storagePath);
        $secondaryColor = self::getMostUsedColorExcludingWhiteAndBlackFamilies($storagePath);
        $backgroundImage = imagecreatetruecolor($imageWidth, $imageHeight);

        // Create a gradient background
        for ($y = 0; $y < $imageHeight; $y++) {
            $gradientColor = imagecolorallocate(
                $backgroundImage,
                $primaryColor[0] + ($secondaryColor[0] - $primaryColor[0]) * $y / $imageHeight,
                $primaryColor[1] + ($secondaryColor[1] - $primaryColor[1]) * $y / $imageHeight,
                $primaryColor[2] + ($secondaryColor[2] - $primaryColor[2]) * $y / $imageHeight
            );
            imageline($backgroundImage, 0, $y, $imageWidth, $y, $gradientColor);
        }

        // Add multiple waves with contrasting colors
        for ($wave = 0; $wave < 3; $wave++) {
            // Generate a contrasting color
            $contrastColor = [
                255 - rand($primaryColor[0], $secondaryColor[0]),
                255 - rand($primaryColor[1], $secondaryColor[1]),
                255 - rand($primaryColor[2], $secondaryColor[2]),
            ];

            // Ensure RGB values stay within the valid range
            $contrastColor = array_map(fn($value) => max(0, min(255, $value)), $contrastColor);

            // Create a color with optional transparency
            $color = imagecolorallocatealpha(
                $backgroundImage,
                $primaryColor[0],
                $primaryColor[1],
                $primaryColor[2],
                50 // Alpha for transparency
            );

            // Wave properties
            $amplitude = rand(20, 50);
            $frequency = rand(1, 3);
            $phaseShift = rand(0, 200);

            // Draw the wave
            for ($x = 0; $x < $imageWidth; $x++) {
                $y = sin(($x + $phaseShift) * $frequency / $imageWidth * 2 * M_PI) * $amplitude + $imageHeight / 2;
                imageline($backgroundImage, $x, (int)$y, $x, $imageHeight, $color);
            }
        }

        // Return the generated image
        return $backgroundImage;
    }

    /**
     * @param string $storagePath
     * @param int $imageWidth = 1200
     * @param int $imageHeight = 200
     *
     * @return GdImage
     */
    private static function createSeamlessJoinedWaveBackground1(string $storagePath, int $imageWidth = 1200, int $imageHeight = 200): GdImage
    {

        $primaryColor = self::getImagePrimaryColor($storagePath);
        $secondaryColor = self::getMostUsedColorExcludingWhiteAndBlackFamilies($storagePath);
        $primaryColor = self::makeColorFaint($primaryColor, 0.3); // Blend 30% with white
        $secondaryColor = self::makeColorFaint($secondaryColor, 0.3); // Blend 30% with white
        $backgroundImage = imagecreatetruecolor($imageWidth, $imageHeight);
        $white = imagecolorallocate($backgroundImage, 255, 255, 255);
        $backgroundColor = ImageColorAllocate($backgroundImage, $primaryColor[0], $primaryColor[1], $primaryColor[2]);

        imagefill($backgroundImage, 0, 0, $backgroundColor);

        $colorPrimary = imagecolorallocate($backgroundImage, $secondaryColor[0], $secondaryColor[1], $secondaryColor[2]);

        $offsetY = rand(30, $imageHeight / 2);
        $amplitude = 40;
        $frequency = 1;
        $phaseShift = 100;

        for ($x = 0; $x < $imageWidth; $x++) {
            $y = sin(($x + $phaseShift) * $frequency / $imageWidth * 2 * M_PI) * $amplitude + $offsetY;
            imageline($backgroundImage, $x, (int)$y, $x, $imageHeight, $colorPrimary);
        }

        // Return the generated image
        return $backgroundImage;
    }

    /**
     * @param string $storagePath
     * @param int $imageWidth
     * @param int $imageHeight
     *
     * @return GdImage
     */
    private static function createAbstractPatternedBackground(string $storagePath, int $imageWidth = 1200, int $imageHeight = 200): GdImage
    {
        // Light blue pastel background
        $backgroundImage = imagecreatetruecolor($imageWidth, $imageHeight);
        $backgroundColor = imagecolorallocate($backgroundImage, 204, 230, 255); // Pastel blue color
        imagefill($backgroundImage, 0, 0, $backgroundColor);

        // Get primary color to overlay on top
        $primaryColor = self::getImagePrimaryColor($storagePath);

        // Overlay primary color with some transparency to create an effect
        $overlayAlpha = 90;  // Adjust transparency (higher value makes the overlay more visible)
        $overlayColor = imagecolorallocatealpha(
            $backgroundImage,
            $primaryColor[0],
            $primaryColor[1],
            $primaryColor[2],
            $overlayAlpha
        );

        // Apply the primary color overlay
        imagefilledrectangle($backgroundImage, 0, 0, $imageWidth, $imageHeight, $overlayColor);

        // Adjust the color for shapes (lighter tone)
        $faintRed = max(0, min(255, $primaryColor[0] + rand(-30, 30)));
        $faintGreen = max(0, min(255, $primaryColor[1] + rand(-30, 30)));
        $faintBlue = max(0, min(255, $primaryColor[2] + rand(-30, 30)));

        // Create a lighter faint color with higher transparency for shapes
        $faintColor = imagecolorallocatealpha(
            $backgroundImage,
            $faintRed,
            $faintGreen,
            $faintBlue,
            120 // Higher alpha for increased transparency (lighter color)
        );

        // Predefined pattern with limited shapes
        $numColumns = 6;  // Number of columns for shapes
        $numRows = 3;     // Number of rows for shapes

        // Calculate shape width and height
        $shapeWidth = $imageWidth / $numColumns;
        $shapeHeight = $imageHeight / $numRows;

        // Place shapes in a grid pattern
        for ($row = 0; $row < $numRows; $row++) {
            for ($col = 0; $col < $numColumns; $col++) {
                $x1 = $col * $shapeWidth;
                $y1 = $row * $shapeHeight;

                // Add alternating long rectangles
                if (($row + $col) % 2 == 0) {
                    imagefilledrectangle(
                        $backgroundImage,
                        $x1,
                        $y1,
                        $x1 + $shapeWidth,
                        $y1 + $shapeHeight,
                        $faintColor
                    );
                } else {
                    // Add diagonal lines
                    imageline(
                        $backgroundImage,
                        $x1,
                        $y1,
                        $x1 + $shapeWidth,
                        $y1 + $shapeHeight,
                        $faintColor
                    );
                }

                // Optional: Add triangles within some shapes for variety
                if (($row + $col) % 3 == 0) {
                    $x2 = $x1 + $shapeWidth / 2;
                    $y2 = $y1 + $shapeHeight / 2;
                    imagefilledpolygon(
                        $backgroundImage,
                        [
                            $x1,
                            $y1,        // top-left
                            $x1 + $shapeWidth,
                            $y1,  // top-right
                            $x2,
                            $y2,        // center
                        ],
                        3,
                        $faintColor
                    );
                }
            }
        }

        // Add horizontal lines across the entire canvas to create an additional layer of pattern
        for ($i = 0; $i < 3; $i++) {
            $yPos = ($i + 1) * ($imageHeight / 4);
            imageline(
                $backgroundImage,
                0,
                $yPos,
                $imageWidth,
                $yPos,
                $faintColor
            );
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
        $secondColor = self::getMostUsedColorExcludingWhiteAndBlackFamilies($fImagepath);
        $rand = rand(1,3);
        // if( $rand== 1){
        //     $backgroundImage = Laragdads::createSeamlessJoinedWaveBackground($fImagepath);
        // }else if( $rand== 2){
        //     $backgroundImage = Laragdads::createSeamlessJoinedWaveBackground($fImagepath);
        // }else if( $rand== 3){
        //     $backgroundImage = Laragdads::createAbstractPatternedBackground($fImagepath);
        // }else{
        //     $backgroundImage = Laragdads::createSeamlessJoinedWaveBackground($fImagepath);
        // }
            $backgroundImage = Laragdads::createSeamlessJoinedWaveBackground($fImagepath);

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

        imagecopyresampled($backgroundImage, $resizeLImage, 10, 10, 0, 0, 160, 180, $resizeWidth, $resizeHeight);
        imagecopyresampled($backgroundImage, $resizeRImage, 1030, 10, 0, 0, 160, 180, $resizeWidth, $resizeHeight);

        $alpha = 0;

        $rBg = $primaryColor[0];
        $gBg = $primaryColor[1];
        $bBg = $primaryColor[2];

        $rText = 255;
        $gText = 255;
        $bText = 255;

        $blendedColor = imagecolorallocatealpha($backgroundImage, $rText, $gText, $bText, $alpha);

        $box = new Box($backgroundImage);
        $box->setFontFace($fontPath);
        $box->setFontColor(new Color($rText, $gText, $bText, $alpha));
        $box->setTextShadow(new Color(0, 0, 0, 50), 2, 2);


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
