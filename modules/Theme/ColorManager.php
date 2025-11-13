<?php

declare(strict_types=1);

/**
 * ISER - Color Manager
 *
 * Utility class for color manipulation and accessibility validation
 * Provides color conversion, contrast calculation, and WCAG compliance checking
 *
 * @package ISER\Theme
 * @author ISER Development Team
 * @copyright 2024 ISER
 * @license Proprietary
 */

namespace ISER\Theme;

/**
 * ColorManager Class
 *
 * Static utility methods for color manipulation and accessibility validation
 */
class ColorManager
{
    /**
     * Convert HEX color to RGB
     *
     * @param string $hex HEX color (e.g., "#FF0000" or "#F00")
     * @return array [r, g, b] values (0-255)
     */
    public static function hexToRgb(string $hex): array
    {
        // Remove # if present
        $hex = ltrim($hex, '#');

        // Expand shorthand notation (e.g., "F00" to "FF0000")
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        // Convert to RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return [$r, $g, $b];
    }

    /**
     * Convert RGB to HEX
     *
     * @param int $r Red (0-255)
     * @param int $g Green (0-255)
     * @param int $b Blue (0-255)
     * @return string HEX color (e.g., "#FF0000")
     */
    public static function rgbToHex(int $r, int $g, int $b): string
    {
        // Clamp values to 0-255
        $r = max(0, min(255, $r));
        $g = max(0, min(255, $g));
        $b = max(0, min(255, $b));

        return sprintf("#%02X%02X%02X", $r, $g, $b);
    }

    /**
     * Convert RGB to HSL
     *
     * @param int $r Red (0-255)
     * @param int $g Green (0-255)
     * @param int $b Blue (0-255)
     * @return array [h, s, l] - Hue (0-360), Saturation (0-100), Lightness (0-100)
     */
    public static function rgbToHsl(int $r, int $g, int $b): array
    {
        // Normalize RGB to 0-1
        $r = $r / 255;
        $g = $g / 255;
        $b = $b / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $delta = $max - $min;

        // Calculate Lightness
        $l = ($max + $min) / 2;

        // Calculate Saturation
        if ($delta == 0) {
            $h = 0;
            $s = 0;
        } else {
            $s = $l > 0.5 ? $delta / (2 - $max - $min) : $delta / ($max + $min);

            // Calculate Hue
            if ($max == $r) {
                $h = (($g - $b) / $delta) + ($g < $b ? 6 : 0);
            } elseif ($max == $g) {
                $h = (($b - $r) / $delta) + 2;
            } else {
                $h = (($r - $g) / $delta) + 4;
            }

            $h = $h / 6;
        }

        // Convert to degrees and percentages
        $h = round($h * 360);
        $s = round($s * 100);
        $l = round($l * 100);

        return [(int)$h, (int)$s, (int)$l];
    }

    /**
     * Convert HSL to RGB
     *
     * @param float $h Hue (0-360)
     * @param float $s Saturation (0-100)
     * @param float $l Lightness (0-100)
     * @return array [r, g, b] values (0-255)
     */
    public static function hslToRgb(float $h, float $s, float $l): array
    {
        // Normalize to 0-1
        $h = $h / 360;
        $s = $s / 100;
        $l = $l / 100;

        if ($s == 0) {
            // Achromatic (gray)
            $r = $g = $b = $l;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;

            $r = self::hueToRgb($p, $q, $h + 1/3);
            $g = self::hueToRgb($p, $q, $h);
            $b = self::hueToRgb($p, $q, $h - 1/3);
        }

        return [
            (int)round($r * 255),
            (int)round($g * 255),
            (int)round($b * 255)
        ];
    }

    /**
     * Helper function for HSL to RGB conversion
     *
     * @param float $p
     * @param float $q
     * @param float $t
     * @return float
     */
    private static function hueToRgb(float $p, float $q, float $t): float
    {
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1/6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1/2) return $q;
        if ($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;
        return $p;
    }

    /**
     * Lighten color by percentage
     *
     * @param string $hex HEX color
     * @param int $percent Percentage (0-100)
     * @return string Lightened HEX color
     */
    public static function lighten(string $hex, int $percent): string
    {
        [$r, $g, $b] = self::hexToRgb($hex);
        [$h, $s, $l] = self::rgbToHsl($r, $g, $b);

        // Increase lightness
        $l = min(100, $l + $percent);

        [$r, $g, $b] = self::hslToRgb($h, $s, $l);

        return self::rgbToHex($r, $g, $b);
    }

    /**
     * Darken color by percentage
     *
     * @param string $hex HEX color
     * @param int $percent Percentage (0-100)
     * @return string Darkened HEX color
     */
    public static function darken(string $hex, int $percent): string
    {
        [$r, $g, $b] = self::hexToRgb($hex);
        [$h, $s, $l] = self::rgbToHsl($r, $g, $b);

        // Decrease lightness
        $l = max(0, $l - $percent);

        [$r, $g, $b] = self::hslToRgb($h, $s, $l);

        return self::rgbToHex($r, $g, $b);
    }

    /**
     * Calculate relative luminance (WCAG 2.0 formula)
     *
     * @param string $hex HEX color
     * @return float Luminance (0-1)
     */
    public static function getLuminance(string $hex): float
    {
        [$r, $g, $b] = self::hexToRgb($hex);

        // Normalize to 0-1
        $r = $r / 255;
        $g = $g / 255;
        $b = $b / 255;

        // Apply sRGB companding
        $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        // Calculate luminance
        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Calculate contrast ratio between two colors (WCAG 2.0 formula)
     *
     * @param string $color1 First HEX color
     * @param string $color2 Second HEX color
     * @return float Contrast ratio (1-21)
     */
    public static function getContrastRatio(string $color1, string $color2): float
    {
        $l1 = self::getLuminance($color1);
        $l2 = self::getLuminance($color2);

        // Ensure l1 is lighter
        if ($l1 < $l2) {
            [$l1, $l2] = [$l2, $l1];
        }

        return round(($l1 + 0.05) / ($l2 + 0.05), 2);
    }

    /**
     * Check if contrast ratio meets WCAG standards
     *
     * @param string $foreground Foreground HEX color
     * @param string $background Background HEX color
     * @param string $level 'AA' or 'AAA'
     * @param string $size 'normal' or 'large'
     * @return bool Passes standards
     */
    public static function meetsWCAG(
        string $foreground,
        string $background,
        string $level = 'AA',
        string $size = 'normal'
    ): bool {
        $ratio = self::getContrastRatio($foreground, $background);

        // WCAG 2.0 requirements
        $requirements = [
            'AA' => [
                'normal' => 4.5,  // Normal text
                'large' => 3.0    // Large text (18pt+ or 14pt+ bold)
            ],
            'AAA' => [
                'normal' => 7.0,
                'large' => 4.5
            ]
        ];

        $required = $requirements[$level][$size] ?? 4.5;

        return $ratio >= $required;
    }

    /**
     * Get best contrast color (black or white) for given background
     *
     * @param string $hex Background HEX color
     * @return string '#000000' or '#ffffff'
     */
    public static function getContrastColor(string $hex): string
    {
        $luminance = self::getLuminance($hex);

        // Use white text on dark backgrounds, black text on light backgrounds
        // Threshold: 0.5 (middle luminance)
        return $luminance > 0.5 ? '#000000' : '#ffffff';
    }

    /**
     * Generate color variants (light, dark, contrast)
     *
     * @param string $hex Base HEX color
     * @return array Variants array
     */
    public static function generateVariants(string $hex): array
    {
        return [
            'base' => $hex,
            'light' => self::lighten($hex, 20),
            'dark' => self::darken($hex, 20),
            'contrast' => self::getContrastColor($hex)
        ];
    }

    /**
     * Adjust color saturation
     *
     * @param string $hex HEX color
     * @param int $percent Percentage adjustment (-100 to 100)
     * @return string Adjusted HEX color
     */
    public static function saturate(string $hex, int $percent): string
    {
        [$r, $g, $b] = self::hexToRgb($hex);
        [$h, $s, $l] = self::rgbToHsl($r, $g, $b);

        // Adjust saturation
        $s = max(0, min(100, $s + $percent));

        [$r, $g, $b] = self::hslToRgb($h, $s, $l);

        return self::rgbToHex($r, $g, $b);
    }

    /**
     * Mix two colors
     *
     * @param string $color1 First HEX color
     * @param string $color2 Second HEX color
     * @param float $weight Weight of first color (0-1, default 0.5)
     * @return string Mixed HEX color
     */
    public static function mix(string $color1, string $color2, float $weight = 0.5): string
    {
        [$r1, $g1, $b1] = self::hexToRgb($color1);
        [$r2, $g2, $b2] = self::hexToRgb($color2);

        $weight = max(0, min(1, $weight));

        $r = (int)round($r1 * $weight + $r2 * (1 - $weight));
        $g = (int)round($g1 * $weight + $g2 * (1 - $weight));
        $b = (int)round($b1 * $weight + $b2 * (1 - $weight));

        return self::rgbToHex($r, $g, $b);
    }

    /**
     * Get complementary color (opposite on color wheel)
     *
     * @param string $hex HEX color
     * @return string Complementary HEX color
     */
    public static function complementary(string $hex): string
    {
        [$r, $g, $b] = self::hexToRgb($hex);
        [$h, $s, $l] = self::rgbToHsl($r, $g, $b);

        // Add 180 degrees to hue (opposite)
        $h = ($h + 180) % 360;

        [$r, $g, $b] = self::hslToRgb($h, $s, $l);

        return self::rgbToHex($r, $g, $b);
    }

    /**
     * Validate HEX color format
     *
     * @param string $hex HEX color string
     * @return bool Valid status
     */
    public static function isValidHex(string $hex): bool
    {
        return (bool)preg_match('/^#?([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $hex);
    }
}
