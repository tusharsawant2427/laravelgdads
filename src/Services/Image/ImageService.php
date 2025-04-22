<?php

namespace Packitifotech\Laragdads\Services\Image;

use GdImage;
use Illuminate\Support\Facades\File;
use ColorThief\ColorThief;
use Exception;
use GDText\Box;
use GDText\Color;

use function PHPUnit\Framework\isNumeric;

class ImageService
{
    public const DEFAULT_IMAGE_WIDTH = 300;
    public const DEFAULT_IMAGE_HEIGHT = 300;
    public const DEFAULT_BANNER_WIDTH = 1200;
    public const DEFAULT_BANNER_HEIGHT = 200;
    public const WHITE_THRESHOLD = 200;
    public const BLACK_THRESHOLD = 50;

    public function loadAndResizeImage(
        string $path,
        int $targetWidth,
        int $targetHeight
    ): GdImage {
        $ext = File::extension($path);
        $image = match ($ext) {
            "png" => imagecreatefrompng($path),
            "webp" => imagecreatefromwebp($path),
            default => imagecreatefromjpeg($path)
        };

        $resized = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled(
            $resized,
            $image,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            imagesx($image),
            imagesy($image)
        );

        return $resized;
    }


    /**
     * Get primary color from image using ColorThief
     */
    public static function getImagePrimaryColor(string $storagePath): array
    {
        $palette = ColorThief::getPalette($storagePath, 10);
        $colorUsageCount = self::countColorOccurrences($palette);

        arsort($colorUsageCount);
        $mostUsedColorKey = array_key_first($colorUsageCount);
        $mostUsedColor = explode(',', $mostUsedColorKey);

        return array_map('intval', $mostUsedColor);
    }

    /**
     * Count occurrences of each color in palette
     */
    private static function countColorOccurrences(array $palette): array
    {
        $colorUsageCount = [];
        foreach ($palette as $color) {
            $colorKey = implode(',', $color);
            $colorUsageCount[$colorKey] = ($colorUsageCount[$colorKey] ?? 0) + 1;
        }
        return $colorUsageCount;
    }

    /**
     * Get most used color excluding white and black families
     */
    public static function getMostUsedColorExcludingWhiteAndBlackFamilies(
        string $imagePath,
        int $whiteThreshold = self::WHITE_THRESHOLD,
        int $blackThreshold = self::BLACK_THRESHOLD
    ): array {
        $image = self::loadImage($imagePath);
        $colorCount = self::countColorsInImage($image, $whiteThreshold, $blackThreshold);

        if (empty($colorCount)) {
            imagedestroy($image);
            return [128, 128, 128]; // Neutral gray fallback
        }

        arsort($colorCount);
        $mostUsedColor = key($colorCount);
        list($r, $g, $b) = explode('_', $mostUsedColor);

        imagedestroy($image);
        return [(int)$r, (int)$g, (int)$b];
    }

    /**
     * Load image from path
     */
    private static function loadImage(string $path): GdImage
    {
        $image = imagecreatefromstring(file_get_contents($path));
        if (!$image) {
            throw new Exception("Could not load image.");
        }
        return $image;
    }

    /**
     * Count colors in image excluding white and black families
     */
    private static function countColorsInImage(
        GdImage $image,
        int $whiteThreshold,
        int $blackThreshold
    ): array {
        $width = imagesx($image);
        $height = imagesy($image);
        $colorCount = [];

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                if (
                    self::isWhiteFamily($r, $g, $b, $whiteThreshold) ||
                    self::isBlackFamily($r, $g, $b, $blackThreshold)
                ) {
                    continue;
                }

                $colorKey = "{$r}_{$g}_{$b}";
                $colorCount[$colorKey] = ($colorCount[$colorKey] ?? 0) + 1;
            }
        }

        return $colorCount;
    }

    private static function isWhiteFamily(int $r, int $g, int $b, int $threshold): bool
    {
        return $r >= $threshold && $g >= $threshold && $b >= $threshold;
    }

    private static function isBlackFamily(int $r, int $g, int $b, int $threshold): bool
    {
        return $r <= $threshold && $g <= $threshold && $b <= $threshold;
    }

    /**
     * Make a color faint by blending with white
     */
    public static function makeColorFaint(array $color, float $blendFactor = 0.3): array
    {
        return array_map(function ($component) use ($blendFactor) {
            return (int) min(255, $component + (255 - $component) * $blendFactor);
        }, $color);
    }

    /**
     * Create block with primary color banner
     */
    public static function createBlockWithPrimaryColorBanner(
        string $storagePath,
        int $imageWidth = self::DEFAULT_IMAGE_WIDTH,
        int $imageHeight = self::DEFAULT_IMAGE_HEIGHT
    ): GdImage {
        $palettes = self::getImagePrimaryColor($storagePath);
        $backgroundImage = imagecreatetruecolor($imageWidth, $imageHeight);
        $backgroundColor = imagecolorallocate($backgroundImage, ...$palettes);
        imagefill($backgroundImage, 0, 0, $backgroundColor);
        return $backgroundImage;
    }

    /**
     * Create seamless joined wave background
     */
    public static function createSeamlessJoinedWaveBackground(
        string $storagePath,
        int $imageWidth = self::DEFAULT_BANNER_WIDTH,
        int $imageHeight = self::DEFAULT_BANNER_HEIGHT
    ): GdImage {
        $primaryColor = self::getImagePrimaryColor($storagePath);
        $secondaryColor = self::getMostUsedColorExcludingWhiteAndBlackFamilies($storagePath);
        $backgroundImage = self::createGradientBackground($imageWidth, $imageHeight, $primaryColor, $secondaryColor);
        self::addWaves($backgroundImage, $imageWidth, $imageHeight, $primaryColor, $secondaryColor);
        return $backgroundImage;
    }

    public static function createGradientBackground(
        int $width,
        int $height,
        array $startColor,
        array $endColor
    ): GdImage {
        $image = imagecreatetruecolor($width, $height);

        for ($y = 0; $y < $height; $y++) {
            $gradientColor = imagecolorallocate(
                $image,
                ...array_map(
                    function ($start, $end) use ($y, $height) {
                        return $start + ($end - $start) * $y / $height;
                    },
                    $startColor,
                    $endColor
                )
            );
            imageline($image, 0, $y, $width, $y, $gradientColor);
        }

        return $image;
    }

    private static function addWaves(
        GdImage $image,
        int $width,
        int $height,
        array $primaryColor,
        array $secondaryColor
    ): void {
        for ($wave = 0; $wave < 5; $wave++) {
            $waveColorR = ($primaryColor[0] + $secondaryColor[0]) / 2 + rand(-25, 25);
            $waveColorG = ($primaryColor[1] + $secondaryColor[1]) / 2 + rand(-25, 25);
            $waveColorB = ($primaryColor[2] + $secondaryColor[2]) / 2 + rand(-25, 25);

            $waveColorR = max(0, min(255, (int)$waveColorR));
            $waveColorG = max(0, min(255, (int)$waveColorG));
            $waveColorB = max(0, min(255, (int)$waveColorB));

            $alpha = rand(70, 100);
            $color = imagecolorallocatealpha(
                $image,
                $waveColorR,
                $waveColorG,
                $waveColorB,
                $alpha
            );

            $amplitude = rand($height / 12, $height / 5);
            $frequency = rand(1, 4);
            $phaseShift = rand(0, $width);
            $yOffset = rand($height / 5, $height * 4 / 5);
            $thickness = rand(1, 4);

            for ($x = 0; $x < $width; $x++) {
                $y = sin(($x + $phaseShift) * $frequency / $width * 2 * M_PI) * $amplitude + $yOffset;

                $y1 = max(0, (int)($y - $thickness / 2));
                $y2 = min($height - 1, (int)($y + $thickness / 2));
                if ($y1 < $y2) {
                    imagefilledrectangle($image, $x, $y1, $x, $y2, $color);
                }
            }
        }
    }

    /**
     * Create seamless joined wave background (alternative version)
     */
    public static function createSeamlessJoinedWaveBackground1(
        string $storagePath,
        int $imageWidth = self::DEFAULT_BANNER_WIDTH,
        int $imageHeight = self::DEFAULT_BANNER_HEIGHT
    ): GdImage {
        $primaryColor = self::makeColorFaint(self::getImagePrimaryColor($storagePath));
        $secondaryColor = self::makeColorFaint(
            self::getMostUsedColorExcludingWhiteAndBlackFamilies($storagePath)
        );

        $backgroundImage = imagecreatetruecolor($imageWidth, $imageHeight);
        $backgroundColor = imagecolorallocate($backgroundImage, ...$primaryColor);
        $colorPrimary = imagecolorallocate($backgroundImage, ...$secondaryColor);

        imagefill($backgroundImage, 0, 0, $backgroundColor);

        $offsetY = rand(30, $imageHeight / 2);
        self::drawWave($backgroundImage, $imageWidth, $imageHeight, $offsetY, $colorPrimary);

        return $backgroundImage;
    }

    private static function drawWave(
        GdImage $image,
        int $width,
        int $height,
        int $offsetY,
        int $color,
        int $amplitude = 40,
        int $frequency = 1,
        int $phaseShift = 100
    ): void {
        for ($x = 0; $x < $width; $x++) {
            $y = sin(($x + $phaseShift) * $frequency / $width * 2 * M_PI) * $amplitude + $offsetY;
            imageline($image, $x, (int)$y, $x, $height, $color);
        }
    }

    /**
     * Create abstract patterned background
     */
    public static function createAbstractPatternedBackground(
        string $storagePath,
        int $imageWidth = self::DEFAULT_BANNER_WIDTH,
        int $imageHeight = self::DEFAULT_BANNER_HEIGHT
    ): GdImage {
        $backgroundImage = imagecreatetruecolor($imageWidth, $imageHeight);
        $backgroundColor = imagecolorallocate($backgroundImage, 204, 230, 255);
        imagefill($backgroundImage, 0, 0, $backgroundColor);

        $primaryColor = self::getImagePrimaryColor($storagePath);
        self::addOverlay($backgroundImage, $imageWidth, $imageHeight, $primaryColor);
        self::addPatterns($backgroundImage, $imageWidth, $imageHeight, $primaryColor);

        return $backgroundImage;
    }

    private static function addOverlay(
        GdImage $image,
        int $width,
        int $height,
        array $color,
        int $alpha = 90
    ): void {
        $overlayColor = imagecolorallocatealpha($image, ...[...$color, $alpha]);
        imagefilledrectangle($image, 0, 0, $width, $height, $overlayColor);
    }

    private static function addPatterns(
        GdImage $image,
        int $width,
        int $height,
        array $baseColor
    ): void {
        $faintColor = self::createFaintColor($image, $baseColor);

        $numColumns = 6;
        $numRows = 3;
        $shapeWidth = $width / $numColumns;
        $shapeHeight = $height / $numRows;

        self::drawShapeGrid($image, $numRows, $numColumns, $shapeWidth, $shapeHeight, $faintColor);
        self::drawHorizontalLines($image, $width, $height, $faintColor);
    }

    public static function createFaintColor(GdImage $image, array $baseColor): int
    {
        $faintColor = array_map(
            fn($value) => max(0, min(255, $value + rand(-30, 30))),
            $baseColor
        );
        return imagecolorallocatealpha($image, ...[...$faintColor, 120]);
    }

    private static function drawShapeGrid(
        GdImage $image,
        int $rows,
        int $columns,
        float $shapeWidth,
        float $shapeHeight,
        int $color
    ): void {
        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $columns; $col++) {
                $x1 = $col * $shapeWidth;
                $y1 = $row * $shapeHeight;

                if (($row + $col) % 2 == 0) {
                    imagefilledrectangle(
                        $image,
                        $x1,
                        $y1,
                        $x1 + $shapeWidth,
                        $y1 + $shapeHeight,
                        $color
                    );
                } else {
                    imageline(
                        $image,
                        $x1,
                        $y1,
                        $x1 + $shapeWidth,
                        $y1 + $shapeHeight,
                        $color
                    );
                }

                if (($row + $col) % 3 == 0) {
                    self::drawTriangle($image, $x1, $y1, $shapeWidth, $shapeHeight, $color);
                }
            }
        }
    }

    private static function drawTriangle(
        GdImage $image,
        float $x,
        float $y,
        float $width,
        float $height,
        int $color
    ): void {
        $x2 = $x + $width / 2;
        $y2 = $y + $height / 2;
        imagefilledpolygon(
            $image,
            [
                $x,
                $y,
                $x + $width,
                $y,
                $x2,
                $y2,
            ],
            3,
            $color
        );
    }

    private static function drawHorizontalLines(
        GdImage $image,
        int $width,
        int $height,
        int $color
    ): void {
        for ($i = 0; $i < 3; $i++) {
            $yPos = ($i + 1) * ($height / 4);
            imageline($image, 0, $yPos, $width, $yPos, $color);
        }
    }

    /**
     * Create geometric patterned background
     * Similar to abstract but potentially simpler for banners
     */
    public static function createGeometricPatternBackground(
        string $storagePath,
        int $imageWidth = self::DEFAULT_BANNER_WIDTH,
        int $imageHeight = self::DEFAULT_BANNER_HEIGHT
    ): GdImage {
        $primaryColor = self::getImagePrimaryColor($storagePath);
        $secondaryColor = self::getMostUsedColorExcludingWhiteAndBlackFamilies($storagePath);

        // Use a faint version of the primary color for the base
        $faintPrimary = self::makeColorFaint($primaryColor, 0.6);
        $backgroundImage = imagecreatetruecolor($imageWidth, $imageHeight);
        $backgroundColor = imagecolorallocate($backgroundImage, ...$faintPrimary);
        imagefill($backgroundImage, 0, 0, $backgroundColor);

        // Use secondary color (or a variation) for patterns
        $patternColorR = max(0, min(255, $secondaryColor[0] + rand(-20, 20)));
        $patternColorG = max(0, min(255, $secondaryColor[1] + rand(-20, 20)));
        $patternColorB = max(0, min(255, $secondaryColor[2] + rand(-20, 20)));
        $patternColor = imagecolorallocatealpha($backgroundImage, $patternColorR, $patternColorG, $patternColorB, 80); // Semi-transparent

        // Add some simple geometric shapes
        $numShapes = rand(15, 30);
        for ($i = 0; $i < $numShapes; $i++) {
            $x1 = rand(0, $imageWidth);
            $y1 = rand(0, $imageHeight);
            $x2 = $x1 + rand(20, $imageWidth / 4);
            $y2 = $y1 + rand(10, $imageHeight / 3);

            // Randomly choose between rectangle and line
            if (rand(0, 1) == 0) {
                imagefilledrectangle($backgroundImage, $x1, $y1, $x2, $y2, $patternColor);
            } else {
                imagesetthickness($backgroundImage, rand(1, 3));
                imageline($backgroundImage, $x1, $y1, rand(0, $imageWidth), rand(0, $imageHeight), $patternColor);
                imagesetthickness($backgroundImage, 1); // Reset thickness
            }
        }

        return $backgroundImage;
    }

    /**
     * Create diagonal stripes background
     */
    public static function createDiagonalStripesBackground(
        string $storagePath,
        int $imageWidth = self::DEFAULT_BANNER_WIDTH,
        int $imageHeight = self::DEFAULT_BANNER_HEIGHT
    ): GdImage {
        $primaryColor = self::getImagePrimaryColor($storagePath);
        $secondaryColor = self::getMostUsedColorExcludingWhiteAndBlackFamilies($storagePath);

        // Base background with primary color
        $backgroundImage = imagecreatetruecolor($imageWidth, $imageHeight);
        $backgroundColor = imagecolorallocate($backgroundImage, ...$primaryColor);
        imagefill($backgroundImage, 0, 0, $backgroundColor);

        // Create stripe colors (variations of secondary color)
        $stripeColor1 = imagecolorallocatealpha(
            $backgroundImage,
            max(0, min(255, $secondaryColor[0] + 20)),
            max(0, min(255, $secondaryColor[1] + 20)),
            max(0, min(255, $secondaryColor[2] + 20)),
            90 // Transparency
        );
        $stripeColor2 = imagecolorallocatealpha(
            $backgroundImage,
            max(0, min(255, $secondaryColor[0] - 20)),
            max(0, min(255, $secondaryColor[1] - 20)),
            max(0, min(255, $secondaryColor[2] - 20)),
            100 // Transparency
        );

        // Draw diagonal stripes
        $stripeWidth = rand(20, 50);
        imagesetthickness($backgroundImage, $stripeWidth);

        for ($x = -$imageHeight; $x < $imageWidth; $x += $stripeWidth * 2) {
            imageline($backgroundImage, $x, 0, $x + $imageHeight, $imageHeight, $stripeColor1);
            imageline($backgroundImage, $x + $stripeWidth, 0, $x + $stripeWidth + $imageHeight, $imageHeight, $stripeColor2);
        }
        imagesetthickness($backgroundImage, 1); // Reset thickness

        return $backgroundImage;
    }

    /**
     * Create radial gradient background
     */
    public static function createRadialGradientBackground(
        string $storagePath,
        int $imageWidth = self::DEFAULT_BANNER_WIDTH,
        int $imageHeight = self::DEFAULT_BANNER_HEIGHT
    ): GdImage {
        $primaryColor = self::getImagePrimaryColor($storagePath);
        $secondaryColor = self::getMostUsedColorExcludingWhiteAndBlackFamilies($storagePath);

        // Lighten secondary color for better contrast
        $secondaryColor = self::makeColorFaint($secondaryColor, 0.2);

        $backgroundImage = imagecreatetruecolor($imageWidth, $imageHeight);

        // Center of the radial gradient
        $centerX = $imageWidth / 2;
        $centerY = $imageHeight / 2;

        // Maximum distance from center to corner
        $maxDistance = sqrt(pow($imageWidth, 2) + pow($imageHeight, 2)) / 2;

        for ($y = 0; $y < $imageHeight; $y++) {
            for ($x = 0; $x < $imageWidth; $x++) {
                // Calculate distance from center
                $distance = sqrt(pow($x - $centerX, 2) + pow($y - $centerY, 2));
                $ratio = $distance / $maxDistance;

                // Blend colors based on distance
                $r = $primaryColor[0] + ($secondaryColor[0] - $primaryColor[0]) * $ratio;
                $g = $primaryColor[1] + ($secondaryColor[1] - $primaryColor[1]) * $ratio;
                $b = $primaryColor[2] + ($secondaryColor[2] - $primaryColor[2]) * $ratio;

                $color = imagecolorallocate($backgroundImage, (int)$r, (int)$g, (int)$b);
                imagesetpixel($backgroundImage, $x, $y, $color);
            }
        }

        return $backgroundImage;
    }

    /**
     * Create grid pattern background
     */
    public static function createGridPatternBackground(
        string $storagePath,
        int $imageWidth = self::DEFAULT_BANNER_WIDTH,
        int $imageHeight = self::DEFAULT_BANNER_HEIGHT
    ): GdImage {
        $primaryColor = self::getImagePrimaryColor($storagePath);
        // Lighten primary color for background
        $bgColor = self::makeColorFaint($primaryColor, 0.7);

        $backgroundImage = imagecreatetruecolor($imageWidth, $imageHeight);
        $backgroundColor = imagecolorallocate($backgroundImage, ...$bgColor);
        imagefill($backgroundImage, 0, 0, $backgroundColor);

        // Grid line color based on primary color but darkened
        $gridColorArray = array_map(function ($v) {
            return max(0, $v - 30);
        }, $primaryColor);

        $gridColor = imagecolorallocatealpha(
            $backgroundImage,
            $gridColorArray[0],
            $gridColorArray[1],
            $gridColorArray[2],
            70
        );

        // Draw horizontal grid lines
        $gridSpacingY = 20;
        for ($y = 0; $y < $imageHeight; $y += $gridSpacingY) {
            imageline($backgroundImage, 0, $y, $imageWidth, $y, $gridColor);
        }

        // Draw vertical grid lines
        $gridSpacingX = 40;
        for ($x = 0; $x < $imageWidth; $x += $gridSpacingX) {
            imageline($backgroundImage, $x, 0, $x, $imageHeight, $gridColor);
        }

        return $backgroundImage;
    }

    /**
     * Create spotlight background - highlights the center for text
     */
    public static function createSpotlightBackground(
        string $storagePath,
        int $imageWidth = self::DEFAULT_BANNER_WIDTH,
        int $imageHeight = self::DEFAULT_BANNER_HEIGHT
    ): GdImage {
        $primaryColor = self::getImagePrimaryColor($storagePath);
        $secondaryColor = self::getMostUsedColorExcludingWhiteAndBlackFamilies($storagePath);

        // Create a gradient background first
        $backgroundImage = self::createGradientBackground(
            $imageWidth,
            $imageHeight,
            $primaryColor,
            self::makeColorFaint($primaryColor, 0.3)
        );

        // Add a spotlight effect in the center (for text to stand out)
        $spotlightCenterX = $imageWidth / 2;
        $spotlightCenterY = $imageHeight / 2;
        $spotlightRadius = min($imageWidth, $imageHeight) * 0.4;

        // Create a lighter version of the secondary color for the spotlight
        $spotlightColor = self::makeColorFaint($secondaryColor, 0.7);

        for ($y = 0; $y < $imageHeight; $y++) {
            for ($x = 0; $x < $imageWidth; $x++) {
                $distance = sqrt(pow($x - $spotlightCenterX, 2) + pow($y - $spotlightCenterY, 2));

                if ($distance < $spotlightRadius) {
                    // Calculate intensity based on distance from center
                    $intensity = 1 - ($distance / $spotlightRadius);
                    $intensity = pow($intensity, 2); // Square for more dramatic falloff

                    // Get current pixel color
                    $rgb = imagecolorat($backgroundImage, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;

                    // Blend with spotlight color
                    $r = (int)($r * (1 - $intensity) + $spotlightColor[0] * $intensity);
                    $g = (int)($g * (1 - $intensity) + $spotlightColor[1] * $intensity);
                    $b = (int)($b * (1 - $intensity) + $spotlightColor[2] * $intensity);

                    $blendedColor = imagecolorallocate($backgroundImage, $r, $g, $b);
                    imagesetpixel($backgroundImage, $x, $y, $blendedColor);
                }
            }
        }

        return $backgroundImage;
    }


    /**
     * Create burst pattern background - lines radiating from corners
     */
    public static function createBurstPatternBackground(
        string $storagePath,
        int $imageWidth = self::DEFAULT_BANNER_WIDTH,
        int $imageHeight = self::DEFAULT_BANNER_HEIGHT
    ): GdImage {
        $primaryColor = self::getImagePrimaryColor($storagePath);
        $secondaryColor = self::getMostUsedColorExcludingWhiteAndBlackFamilies($storagePath);

        // Start with a gradient background
        $backgroundImage = self::createGradientBackground(
            $imageWidth,
            $imageHeight,
            self::makeColorFaint($primaryColor, 0.4),
            self::makeColorFaint($secondaryColor, 0.6)
        );

        // Burst line color based on secondary color
        $burstColor = imagecolorallocatealpha(
            $backgroundImage,
            $secondaryColor[0],
            $secondaryColor[1],
            $secondaryColor[2],
            80 // Semi-transparent
        );

        // Create bursts from multiple corners
        $corners = [
            [0, 0], // Top-left
            [$imageWidth, 0], // Top-right
            [0, $imageHeight], // Bottom-left
            [$imageWidth, $imageHeight], // Bottom-right
        ];

        foreach ($corners as $corner) {
            $numLines = rand(15, 25);
            $maxLength = sqrt(pow($imageWidth, 2) + pow($imageHeight, 2)) * 0.7;

            for ($i = 0; $i < $numLines; $i++) {
                $angle = (2 * M_PI) * ($i / $numLines);
                $length = $maxLength * (0.5 + (rand(0, 100) / 100) * 0.5);

                $endX = $corner[0] + cos($angle) * $length;
                $endY = $corner[1] + sin($angle) * $length;

                imagesetthickness($backgroundImage, rand(1, 3));
                imageline($backgroundImage, $corner[0], $corner[1], $endX, $endY, $burstColor);
            }
        }

        // Reset line thickness
        imagesetthickness($backgroundImage, 1);

        return $backgroundImage;
    }

    /**
     * Create modern dots pattern - clean, minimal style popular in mobile design
     */
    public static function createModernDotsPattern(
        string $storagePath,
        int $imageWidth = self::DEFAULT_BANNER_WIDTH,
        int $imageHeight = self::DEFAULT_BANNER_HEIGHT
    ): GdImage {
        $primaryColor = self::getImagePrimaryColor($storagePath);
        $secondaryColor = self::getMostUsedColorExcludingWhiteAndBlackFamilies($storagePath);

        // Create a clean, light background
        $backgroundImage = imagecreatetruecolor($imageWidth, $imageHeight);
        $bgColor = self::makeColorFaint($primaryColor, 0.8); // Very light version of primary color
        $backgroundColor = imagecolorallocate($backgroundImage, ...$bgColor);
        imagefill($backgroundImage, 0, 0, $backgroundColor);

        // Create dot colors from secondary color
        $dotColor = imagecolorallocatealpha(
            $backgroundImage,
            $secondaryColor[0],
            $secondaryColor[1],
            $secondaryColor[2],
            40 // Semi-transparent
        );

        // Draw a grid of dots - popular in modern mobile interfaces
        $dotSpacing = 30; // Space between dots
        $dotSize = 6;     // Size of each dot

        for ($y = $dotSpacing; $y < $imageHeight; $y += $dotSpacing) {
            for ($x = $dotSpacing; $x < $imageWidth; $x += $dotSpacing) {
                // Add slight position variation for more organic feel
                $xPos = $x + rand(-3, 3);
                $yPos = $y + rand(-3, 3);

                // Draw filled circle
                imagefilledellipse(
                    $backgroundImage,
                    $xPos,
                    $yPos,
                    $dotSize,
                    $dotSize,
                    $dotColor
                );
            }
        }

        return $backgroundImage;
    }

    /**
     * Create material card style background - inspired by mobile app card designs
     */
    public static function createMaterialCardBackground(
        string $storagePath,
        int $imageWidth = self::DEFAULT_BANNER_WIDTH,
        int $imageHeight = self::DEFAULT_BANNER_HEIGHT
    ): GdImage {
        $primaryColor = self::getImagePrimaryColor($storagePath);

        // Create a very light gray background common in mobile interfaces
        $backgroundImage = imagecreatetruecolor($imageWidth, $imageHeight);
        $backgroundColor = imagecolorallocate($backgroundImage, 245, 245, 245); // Light gray
        imagefill($backgroundImage, 0, 0, $backgroundColor);

        // Create a "card" in the center
        $cardMargin = 5;
        $cardX = $cardMargin;
        $cardY = $cardMargin;
        $cardWidth = $imageWidth - ($cardMargin * 2);
        $cardHeight = $imageHeight - ($cardMargin * 2);

        // Card background - use a light version of the primary color
        $cardBgColor = self::makeColorFaint($primaryColor, 0.5);
        $cardColor = imagecolorallocate($backgroundImage, ...$cardBgColor);
        imagefilledrectangle(
            $backgroundImage,
            $cardX,
            $cardY,
            $cardX + $cardWidth,
            $cardY + $cardHeight,
            $cardColor
        );

        // Add a subtle shadow effect (common in material design)
        $shadowColor = imagecolorallocatealpha($backgroundImage, 0, 0, 0, 110); // Very transparent black
        for ($i = 1; $i <= 5; $i++) {
            $shadowOffset = $i;
            imagerectangle(
                $backgroundImage,
                $cardX + $shadowOffset,
                $cardY + $shadowOffset,
                $cardX + $cardWidth + $shadowOffset,
                $cardY + $cardHeight + $shadowOffset,
                $shadowColor
            );
        }

        // Add a colored accent bar at the top (common mobile UI pattern)
        $accentHeight = 15;
        $accentColor = imagecolorallocate($backgroundImage, ...$primaryColor);
        imagefilledrectangle(
            $backgroundImage,
            $cardX,
            $cardY,
            $cardX + $cardWidth,
            $cardY + $accentHeight,
            $accentColor
        );

        return $backgroundImage;
    }

    /**
     * Create diagonal mobile pattern - optimized for both portrait and landscape
     */
    public static function createDiagonalMobilePattern(
        string $storagePath,
        int $imageWidth = self::DEFAULT_BANNER_WIDTH,
        int $imageHeight = self::DEFAULT_BANNER_HEIGHT
    ): GdImage {
        $primaryColor = self::getImagePrimaryColor($storagePath);
        $secondaryColor = self::getMostUsedColorExcludingWhiteAndBlackFamilies($storagePath);

        // Create a gradient background
        $backgroundImage = self::createGradientBackground(
            $imageWidth,
            $imageHeight,
            self::makeColorFaint($primaryColor, 0.5),
            self::makeColorFaint($secondaryColor, 0.5)
        );

        // Create diagonal stripes that look good on mobile screens
        $stripeColor = imagecolorallocatealpha(
            $backgroundImage,
            max(0, min(255, $secondaryColor[0] - 20)),
            max(0, min(255, $secondaryColor[1] - 20)),
            max(0, min(255, $secondaryColor[2] - 20)),
            100 // Transparency
        );

        // Calculate diagonal line spacing based on screen size
        // This ensures pattern looks good on different mobile screen sizes
        $diagonalSpacing = ceil(min($imageWidth, $imageHeight) / 20);
        $thickness = ceil($diagonalSpacing / 3);

        // Draw diagonal lines
        imagesetthickness($backgroundImage, $thickness);
        for ($i = -$imageHeight; $i < $imageWidth + $imageHeight; $i += $diagonalSpacing * 3) {
            imageline(
                $backgroundImage,
                $i,
                0,
                $i + $imageHeight,
                $imageHeight,
                $stripeColor
            );
        }

        // Reset line thickness
        imagesetthickness($backgroundImage, 1);

        return $backgroundImage;
    }

    /**
     * Create flat mobile UI style - clean look popular in modern apps
     */
    public static function createFlatMobileUIBackground(
        string $storagePath,
        int $imageWidth = self::DEFAULT_BANNER_WIDTH,
        int $imageHeight = self::DEFAULT_BANNER_HEIGHT
    ): GdImage {
        $primaryColor = self::getImagePrimaryColor($storagePath);
        $secondaryColor = self::getMostUsedColorExcludingWhiteAndBlackFamilies($storagePath);

        // Create background with primary color
        $backgroundImage = imagecreatetruecolor($imageWidth, $imageHeight);
        $backgroundColor = imagecolorallocate($backgroundImage, ...$primaryColor);
        imagefill($backgroundImage, 0, 0, $backgroundColor);

        // Create a set of horizontal bars in varying colors
        // This pattern adapts well to different mobile screen sizes
        $numBars = 5;
        $barHeight = $imageHeight / 15;

        for ($i = 0; $i < $numBars; $i++) {
            // Create color variation for each bar
            $colorRatio = $i / ($numBars - 1);
            $r = (int)($primaryColor[0] + ($secondaryColor[0] - $primaryColor[0]) * $colorRatio);
            $g = (int)($primaryColor[1] + ($secondaryColor[1] - $primaryColor[1]) * $colorRatio);
            $b = (int)($primaryColor[2] + ($secondaryColor[2] - $primaryColor[2]) * $colorRatio);

            $barColor = imagecolorallocatealpha($backgroundImage, $r, $g, $b, 70);

            // Position the bar
            $yPosition = $i * ($imageHeight / ($numBars + 1));

            // Draw the bar
            imagefilledrectangle(
                $backgroundImage,
                0,
                $yPosition,
                $imageWidth,
                $yPosition + $barHeight,
                $barColor
            );
        }

        return $backgroundImage;
    }

    /**
     * Create medical style banner background
     */
    public static function createMedicalBannerBackground(
        string $storagePath,
        int $imageWidth = self::DEFAULT_BANNER_WIDTH,
        int $imageHeight = self::DEFAULT_BANNER_HEIGHT
    ): GdImage {
        // Get colors from the image
        $primaryColor = self::getImagePrimaryColor($storagePath);
        $secondaryColor = self::getMostUsedColorExcludingWhiteAndBlackFamilies($storagePath);

        // Create lighter version for top gradient
        $topColor = self::makeColorFaint($primaryColor, 0.7);    // Lighter version
        $bottomColor = $secondaryColor;   // Original secondary color

        // Create base gradient background
        $backgroundImage = imagecreatetruecolor($imageWidth, $imageHeight);

        // Create gradient background
        for ($y = 0; $y < $imageHeight; $y++) {
            $ratio = $y / $imageHeight;
            $r = $topColor[0] + ($bottomColor[0] - $topColor[0]) * $ratio;
            $g = $topColor[1] + ($bottomColor[1] - $topColor[1]) * $ratio;
            $b = $topColor[2] + ($bottomColor[2] - $topColor[2]) * $ratio;

            $lineColor = imagecolorallocate($backgroundImage, (int)$r, (int)$g, (int)$b);
            imageline($backgroundImage, 0, $y, $imageWidth, $y, $lineColor);
        }

        // Add subtle diagonal lines using primary color
        $lineColor = imagecolorallocatealpha(
            $backgroundImage,
            $primaryColor[0],
            $primaryColor[1],
            $primaryColor[2],
            80
        );
        for ($i = -$imageHeight; $i < $imageWidth; $i += 50) {
            imageline($backgroundImage, $i, 0, $i + $imageHeight, $imageHeight, $lineColor);
        }

        // Add some soft circular elements using secondary color
        $circleColor = imagecolorallocatealpha(
            $backgroundImage,
            $secondaryColor[0],
            $secondaryColor[1],
            $secondaryColor[2],
            90
        );
        $radius = $imageHeight / 3;
        imagefilledellipse(
            $backgroundImage,
            $imageWidth - $radius,
            $radius,
            $radius * 2,
            $radius * 2,
            $circleColor
        );

        return $backgroundImage;
    }

    /**
     * Create medical banner with text
     */
    public static function createMedicalBanner(
        string $imagePath,
        string $title,
        string $subtitle,
        string $fontPath,
        ?int $width = null,
        ?int $height = null
    ): GdImage {
        $width = $width ?? self::DEFAULT_BANNER_WIDTH;
        $height = $height ?? self::DEFAULT_BANNER_HEIGHT;

        $banner = self::createMedicalBannerBackground($imagePath, $width, $height);

        // Get colors from the image for text
        $primaryColor = self::getImagePrimaryColor($imagePath);
        $secondaryColor = self::getMostUsedColorExcludingWhiteAndBlackFamilies($imagePath);

        // Calculate text color based on background brightness
        $avgBgBrightness = (array_sum($primaryColor) + array_sum($secondaryColor)) / 6;
        $isDarkBg = $avgBgBrightness < 128;

        // Add title text
        $box = new Box($banner);
        $box->setFontFace($fontPath);
        $box->setFontColor($isDarkBg ? new Color(255, 255, 255) : new Color(0, 0, 0));
        $box->setFontSize(48);
        $box->setBox(50, 50, $width - 100, $height / 2);
        $box->setTextAlign('left', 'center');
        $box->draw($title);

        // Add subtitle text
        $box->setFontColor($isDarkBg ? new Color(200, 200, 200) : new Color(80, 80, 80));
        $box->setFontSize(24);
        $box->setBox(50, $height / 2, $width - 100, $height - 50);
        $box->draw($subtitle);

        return $banner;
    }


    /**
     * Create marketing agency style banner with diagonal vector pattern
     */
    private static function createMarketingAgencyBackground(
        string $storagePath,
        int $imageWidth = self::DEFAULT_BANNER_WIDTH,
        int $imageHeight = self::DEFAULT_BANNER_HEIGHT
    ): GdImage {
        // Get colors from the image
        $primaryColor = self::getImagePrimaryColor($storagePath);
        $secondaryColor = self::getMostUsedColorExcludingWhiteAndBlackFamilies($storagePath);

        // Create base background
        $backgroundImage = imagecreatetruecolor($imageWidth, $imageHeight);

        // Create white background first
        $whiteColor = imagecolorallocate($backgroundImage, 255, 255, 255);
        imagefill($backgroundImage, 0, 0, $whiteColor);

        // Create diagonal sections - matching the example pattern
        $points = [
            // Left navy section (using primary color)
            [
                0,
                0,                            // Top left
                $imageWidth * 0.45,
                0,           // Top right
                $imageWidth * 0.35,
                $imageHeight, // Bottom right
                0,
                $imageHeight                  // Bottom left
            ],
            // Right red section (using secondary color)
            [
                $imageWidth * 0.35,
                0,           // Top left
                $imageWidth,
                0,                  // Top right
                $imageWidth,
                $imageHeight,       // Bottom right
                $imageWidth * 0.45,
                $imageHeight // Bottom left
            ]
        ];

        // Fill the diagonal sections
        imagefilledpolygon(
            $backgroundImage,
            $points[0],
            4,
            imagecolorallocate($backgroundImage, ...$primaryColor)
        );
        imagefilledpolygon(
            $backgroundImage,
            $points[1],
            4,
            imagecolorallocate($backgroundImage, ...$secondaryColor)
        );

        // Add white section in middle
        $whiteSection = [
            $imageWidth * 0.35,
            0,              // Top left
            $imageWidth * 0.45,
            0,              // Top right
            $imageWidth * 0.35,
            $imageHeight,   // Bottom right
            $imageWidth * 0.45,
            $imageHeight    // Bottom left
        ];
        imagefilledpolygon($backgroundImage, $whiteSection, 4, $whiteColor);

        return $backgroundImage;
    }

    /**
     * Create marketing agency banner with text
     */
    public static function createMarketingAgencyBanner(
        string $imagePath,
        ?string $title,
        string $fontPath,
        ?int $width = null,
        ?int $height = null
    ): GdImage {
        $width = $width ?? self::DEFAULT_BANNER_WIDTH;
        $height = $height ?? self::DEFAULT_BANNER_HEIGHT;

        // Load and analyze the source image first
        $sourceImage = imagecreatefromstring(file_get_contents($imagePath));
        if (!$sourceImage) {
            throw new \RuntimeException('Failed to load source image');
        }

        // Get the image dimensions
        $srcWidth = imagesx($sourceImage);
        $srcHeight = imagesy($sourceImage);

        // Sample colors from specific regions of the image
        $colors = [];

        // Sample from left side for primary color
        $primaryColorSamples = [];
        for ($x = 0; $x < $srcWidth / 3; $x++) {
            for ($y = 0; $y < $srcHeight; $y++) {
                $rgb = imagecolorat($sourceImage, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $primaryColorSamples[] = [$r, $g, $b];
            }
        }

        // Sample from right side for secondary color
        $secondaryColorSamples = [];
        for ($x = ($srcWidth * 2 / 3); $x < $srcWidth; $x++) {
            for ($y = 0; $y < $srcHeight; $y++) {
                $rgb = imagecolorat($sourceImage, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $secondaryColorSamples[] = [$r, $g, $b];
            }
        }

        // Calculate average colors
        $primaryColor = self::calculateAverageColor($primaryColorSamples);
        $secondaryColor = self::calculateAverageColor($secondaryColorSamples);
        $banner = self::createMarketingAgencyBackground($imagePath, $width, $height);

        if (!empty($title)) {
            // Create the banner with the extracted colors

            $box = new Box($banner);
            $box->setFontFace($fontPath);

            $box->setFontColor(new Color($primaryColor[0], $primaryColor[1], $primaryColor[2]));
            $box->setFontSize(24);
            $box->setBox($width * 0.35, $height * 0.2, $width * 0.45, $height * 0.5);
            $box->setTextAlign('center', 'center');
            $box->draw($title);
        }

        imagedestroy($sourceImage);

        return $banner;
    }

    /**
     * Calculate average color from samples
     */
    public static function calculateAverageColor(array $samples): array
    {
        if (empty($samples)) {
            return [0, 0, 0];
        }

        $r = $g = $b = 0;
        $count = count($samples);
        foreach ($samples as $color) {
            $r += (int)($color[0]) ? $color[0] : 0;
            $g += (int)($color[1]) ? $color[1] : 0;
            $b += (int)($color[2]) ? $color[2] : 0;

        }
        $colors = ([
            (int)($r / $count),
            (int)($g / $count),
            (int)($b / $count)
        ]);
        return $colors;
    }
}
