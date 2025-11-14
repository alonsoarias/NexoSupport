<?php

/**
 * ISER - Color Scheme Generator
 *
 * Advanced color manipulation and generation for theme system.
 * Handles color variations, accessibility, and dark mode conversions.
 *
 * @package    ISER\Core\Theme
 * @category   Core
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Week 5-6 - Theme System Implementation
 */

namespace ISER\Core\Theme;

use ISER\Core\Utils\Logger;

/**
 * ColorSchemeGenerator Class
 *
 * Provides color manipulation utilities including:
 * - Generate color variations (lighter/darker)
 * - Calculate contrast ratios
 * - Dark mode color conversion
 * - Generate color palettes
 * - Accessibility validation
 * - Color format conversion
 */
class ColorSchemeGenerator
{
    /**
     * WCAG 2.0 contrast ratio thresholds
     */
    private const CONTRAST_AA_NORMAL = 4.5;
    private const CONTRAST_AA_LARGE = 3.0;
    private const CONTRAST_AAA_NORMAL = 7.0;
    private const CONTRAST_AAA_LARGE = 4.5;

    /**
     * Generate color palette from base color
     *
     * @param string $baseColor Base color (hex)
     * @param int $shades Number of shades to generate
     * @return array Array of color shades
     */
    public function generatePalette(string $baseColor, int $shades = 9): array
    {
        $palette = [];
        $rgb = $this->hexToRgb($baseColor);

        if (!$rgb) {
            Logger::warning('Invalid base color for palette generation', [
                'color' => $baseColor
            ]);
            return [];
        }

        // Generate lighter shades
        for ($i = $shades; $i > 5; $i--) {
            $percentage = (($shades - $i) / $shades) * 100;
            $palette[$i * 100] = $this->lighten($baseColor, $percentage);
        }

        // Base color (500)
        $palette[500] = $baseColor;

        // Generate darker shades
        for ($i = 4; $i >= 1; $i--) {
            $percentage = ((5 - $i) / 5) * 80; // Max 80% darker
            $palette[$i * 100] = $this->darken($baseColor, $percentage);
        }

        return $palette;
    }

    /**
     * Lighten a color by percentage
     *
     * @param string $color Hex color
     * @param float $percentage Percentage to lighten (0-100)
     * @return string Lightened hex color
     */
    public function lighten(string $color, float $percentage): string
    {
        $rgb = $this->hexToRgb($color);

        if (!$rgb) {
            return $color;
        }

        $rgb['r'] = min(255, $rgb['r'] + (255 - $rgb['r']) * ($percentage / 100));
        $rgb['g'] = min(255, $rgb['g'] + (255 - $rgb['g']) * ($percentage / 100));
        $rgb['b'] = min(255, $rgb['b'] + (255 - $rgb['b']) * ($percentage / 100));

        return $this->rgbToHex($rgb);
    }

    /**
     * Darken a color by percentage
     *
     * @param string $color Hex color
     * @param float $percentage Percentage to darken (0-100)
     * @return string Darkened hex color
     */
    public function darken(string $color, float $percentage): string
    {
        $rgb = $this->hexToRgb($color);

        if (!$rgb) {
            return $color;
        }

        $rgb['r'] = max(0, $rgb['r'] - $rgb['r'] * ($percentage / 100));
        $rgb['g'] = max(0, $rgb['g'] - $rgb['g'] * ($percentage / 100));
        $rgb['b'] = max(0, $rgb['b'] - $rgb['b'] * ($percentage / 100));

        return $this->rgbToHex($rgb);
    }

    /**
     * Convert color to dark mode equivalent
     *
     * Inverts lightness while maintaining hue and saturation.
     *
     * @param string $color Hex color
     * @return string Dark mode hex color
     */
    public function convertToDarkMode(string $color): string
    {
        $rgb = $this->hexToRgb($color);

        if (!$rgb) {
            return $color;
        }

        $hsl = $this->rgbToHsl($rgb);

        // Invert lightness
        $hsl['l'] = 100 - $hsl['l'];

        // Adjust saturation slightly for dark mode
        $hsl['s'] = $hsl['s'] * 0.9;

        $rgb = $this->hslToRgb($hsl);

        return $this->rgbToHex($rgb);
    }

    /**
     * Calculate contrast ratio between two colors
     *
     * Based on WCAG 2.0 formula.
     *
     * @param string $color1 First color (hex)
     * @param string $color2 Second color (hex)
     * @return float Contrast ratio (1-21)
     */
    public function calculateContrastRatio(string $color1, string $color2): float
    {
        $lum1 = $this->getRelativeLuminance($color1);
        $lum2 = $this->getRelativeLuminance($color2);

        $lighter = max($lum1, $lum2);
        $darker = min($lum1, $lum2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Check if color combination meets WCAG contrast requirements
     *
     * @param string $foreground Foreground color (hex)
     * @param string $background Background color (hex)
     * @param string $level WCAG level (AA or AAA)
     * @param bool $largeText Is large text (18pt+)
     * @return array ['passes' => bool, 'ratio' => float, 'required' => float]
     */
    public function meetsContrastRequirement(
        string $foreground,
        string $background,
        string $level = 'AA',
        bool $largeText = false
    ): array {
        $ratio = $this->calculateContrastRatio($foreground, $background);

        $required = match ($level) {
            'AAA' => $largeText ? self::CONTRAST_AAA_LARGE : self::CONTRAST_AAA_NORMAL,
            default => $largeText ? self::CONTRAST_AA_LARGE : self::CONTRAST_AA_NORMAL,
        };

        return [
            'passes' => $ratio >= $required,
            'ratio' => round($ratio, 2),
            'required' => $required,
            'level' => $level
        ];
    }

    /**
     * Find accessible color for text on background
     *
     * Adjusts text color until it meets contrast requirements.
     *
     * @param string $textColor Initial text color (hex)
     * @param string $bgColor Background color (hex)
     * @param string $level WCAG level
     * @param bool $largeText Is large text
     * @return string Accessible text color (hex)
     */
    public function getAccessibleTextColor(
        string $textColor,
        string $bgColor,
        string $level = 'AA',
        bool $largeText = false
    ): string {
        $check = $this->meetsContrastRequirement($textColor, $bgColor, $level, $largeText);

        if ($check['passes']) {
            return $textColor;
        }

        // Determine if we should lighten or darken
        $bgLum = $this->getRelativeLuminance($bgColor);
        $shouldLighten = $bgLum < 0.5;

        // Adjust color iteratively
        $adjustedColor = $textColor;
        $step = 5;
        $maxIterations = 20;
        $iterations = 0;

        while (!$check['passes'] && $iterations < $maxIterations) {
            $adjustedColor = $shouldLighten
                ? $this->lighten($adjustedColor, $step)
                : $this->darken($adjustedColor, $step);

            $check = $this->meetsContrastRequirement($adjustedColor, $bgColor, $level, $largeText);
            $iterations++;
        }

        return $adjustedColor;
    }

    /**
     * Get relative luminance of color
     *
     * Used for contrast ratio calculation.
     *
     * @param string $color Hex color
     * @return float Relative luminance (0-1)
     */
    private function getRelativeLuminance(string $color): float
    {
        $rgb = $this->hexToRgb($color);

        if (!$rgb) {
            return 0;
        }

        // Convert to 0-1 range
        $r = $rgb['r'] / 255;
        $g = $rgb['g'] / 255;
        $b = $rgb['b'] / 255;

        // Apply gamma correction
        $r = ($r <= 0.03928) ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = ($g <= 0.03928) ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = ($b <= 0.03928) ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        // Calculate luminance
        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Convert hex color to RGB
     *
     * @param string $hex Hex color
     * @return array|null RGB array or null if invalid
     */
    public function hexToRgb(string $hex): ?array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (strlen($hex) !== 6) {
            return null;
        }

        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2))
        ];
    }

    /**
     * Convert RGB to hex color
     *
     * @param array $rgb RGB array
     * @return string Hex color
     */
    public function rgbToHex(array $rgb): string
    {
        $r = max(0, min(255, round($rgb['r'])));
        $g = max(0, min(255, round($rgb['g'])));
        $b = max(0, min(255, round($rgb['b'])));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Convert RGB to HSL
     *
     * @param array $rgb RGB array
     * @return array HSL array (h: 0-360, s: 0-100, l: 0-100)
     */
    public function rgbToHsl(array $rgb): array
    {
        $r = $rgb['r'] / 255;
        $g = $rgb['g'] / 255;
        $b = $rgb['b'] / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $delta = $max - $min;

        $l = ($max + $min) / 2;

        if ($delta == 0) {
            $h = $s = 0;
        } else {
            $s = $l > 0.5 ? $delta / (2 - $max - $min) : $delta / ($max + $min);

            switch ($max) {
                case $r:
                    $h = (($g - $b) / $delta) + ($g < $b ? 6 : 0);
                    break;
                case $g:
                    $h = (($b - $r) / $delta) + 2;
                    break;
                case $b:
                    $h = (($r - $g) / $delta) + 4;
                    break;
                default:
                    $h = 0;
            }

            $h /= 6;
        }

        return [
            'h' => $h * 360,
            's' => $s * 100,
            'l' => $l * 100
        ];
    }

    /**
     * Convert HSL to RGB
     *
     * @param array $hsl HSL array
     * @return array RGB array
     */
    public function hslToRgb(array $hsl): array
    {
        $h = $hsl['h'] / 360;
        $s = $hsl['s'] / 100;
        $l = $hsl['l'] / 100;

        if ($s == 0) {
            $r = $g = $b = $l * 255;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;

            $r = $this->hueToRgb($p, $q, $h + 1/3) * 255;
            $g = $this->hueToRgb($p, $q, $h) * 255;
            $b = $this->hueToRgb($p, $q, $h - 1/3) * 255;
        }

        return [
            'r' => $r,
            'g' => $g,
            'b' => $b
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
    private function hueToRgb(float $p, float $q, float $t): float
    {
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1/6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1/2) return $q;
        if ($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;
        return $p;
    }

    /**
     * Generate complementary color
     *
     * @param string $color Base color (hex)
     * @return string Complementary color (hex)
     */
    public function getComplementary(string $color): string
    {
        $rgb = $this->hexToRgb($color);
        if (!$rgb) return $color;

        $hsl = $this->rgbToHsl($rgb);
        $hsl['h'] = ($hsl['h'] + 180) % 360;

        $rgb = $this->hslToRgb($hsl);
        return $this->rgbToHex($rgb);
    }

    /**
     * Generate triadic colors
     *
     * @param string $color Base color (hex)
     * @return array Array of 3 colors
     */
    public function getTriadic(string $color): array
    {
        $rgb = $this->hexToRgb($color);
        if (!$rgb) return [$color, $color, $color];

        $hsl = $this->rgbToHsl($rgb);

        $colors = [$color];

        // +120 degrees
        $hsl2 = $hsl;
        $hsl2['h'] = ($hsl['h'] + 120) % 360;
        $colors[] = $this->rgbToHex($this->hslToRgb($hsl2));

        // +240 degrees
        $hsl3 = $hsl;
        $hsl3['h'] = ($hsl['h'] + 240) % 360;
        $colors[] = $this->rgbToHex($this->hslToRgb($hsl3));

        return $colors;
    }

    /**
     * Generate analogous colors
     *
     * @param string $color Base color (hex)
     * @param int $angle Angle between colors (default: 30)
     * @return array Array of 3 colors
     */
    public function getAnalogous(string $color, int $angle = 30): array
    {
        $rgb = $this->hexToRgb($color);
        if (!$rgb) return [$color, $color, $color];

        $hsl = $this->rgbToHsl($rgb);

        $colors = [];

        // -angle degrees
        $hsl1 = $hsl;
        $hsl1['h'] = ($hsl['h'] - $angle + 360) % 360;
        $colors[] = $this->rgbToHex($this->hslToRgb($hsl1));

        // Base color
        $colors[] = $color;

        // +angle degrees
        $hsl2 = $hsl;
        $hsl2['h'] = ($hsl['h'] + $angle) % 360;
        $colors[] = $this->rgbToHex($this->hslToRgb($hsl2));

        return $colors;
    }
}
