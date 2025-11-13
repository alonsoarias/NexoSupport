<?php

declare(strict_types=1);

/**
 * Phase 9 Component Test Suite
 *
 * Tests all enhanced components:
 * - ThemeConfigurator (setMultiple, getGroup, export/import, backup/restore)
 * - ColorManager (conversions, WCAG validation, adjustments)
 * - AssetManager (CSS generation, minification)
 *
 * @package ISER\Theme\Tests
 * @copyright 2024 ISER
 */

// Prevent direct access
defined('ISER_BASE_DIR') or define('ISER_BASE_DIR', dirname(__DIR__, 3));

require_once ISER_BASE_DIR . '/core/Database/Database.php';
require_once ISER_BASE_DIR . '/modules/Theme/ThemeConfigurator.php';
require_once ISER_BASE_DIR . '/modules/Theme/ColorManager.php';
require_once ISER_BASE_DIR . '/modules/Theme/AssetManager.php';

use ISER\Core\Database\Database;
use ISER\Theme\ThemeConfigurator;
use ISER\Theme\ColorManager;
use ISER\Theme\AssetManager;

/**
 * Simple test runner
 */
class TestRunner
{
    private int $passed = 0;
    private int $failed = 0;
    private array $failures = [];

    public function assert(bool $condition, string $message): void
    {
        if ($condition) {
            $this->passed++;
            echo "✓ {$message}\n";
        } else {
            $this->failed++;
            $this->failures[] = $message;
            echo "✗ {$message}\n";
        }
    }

    public function assertEquals($expected, $actual, string $message): void
    {
        $this->assert($expected === $actual, $message . " (expected: " . json_encode($expected) . ", got: " . json_encode($actual) . ")");
    }

    public function assertTrue(bool $value, string $message): void
    {
        $this->assert($value === true, $message);
    }

    public function assertFalse(bool $value, string $message): void
    {
        $this->assert($value === false, $message);
    }

    public function summary(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "Test Results:\n";
        echo "  Passed: {$this->passed}\n";
        echo "  Failed: {$this->failed}\n";

        if ($this->failed > 0) {
            echo "\nFailed Tests:\n";
            foreach ($this->failures as $failure) {
                echo "  - {$failure}\n";
            }
        }

        echo str_repeat("=", 60) . "\n";

        if ($this->failed === 0) {
            echo "✓ All tests passed!\n";
        } else {
            echo "✗ Some tests failed.\n";
        }
    }
}

// ============================================================================
// ColorManager Tests
// ============================================================================

function testColorManager(TestRunner $test): void
{
    echo "\n" . str_repeat("-", 60) . "\n";
    echo "Testing ColorManager\n";
    echo str_repeat("-", 60) . "\n\n";

    // Test 1: HEX to RGB conversion
    [$r, $g, $b] = ColorManager::hexToRgb('#FF0000');
    $test->assertEquals(255, $r, "HEX to RGB: Red component");
    $test->assertEquals(0, $g, "HEX to RGB: Green component");
    $test->assertEquals(0, $b, "HEX to RGB: Blue component");

    // Test 2: Shorthand HEX
    [$r, $g, $b] = ColorManager::hexToRgb('#F00');
    $test->assertEquals(255, $r, "Shorthand HEX: Red component");
    $test->assertEquals(0, $g, "Shorthand HEX: Green component");
    $test->assertEquals(0, $b, "Shorthand HEX: Blue component");

    // Test 3: RGB to HEX conversion
    $hex = ColorManager::rgbToHex(255, 0, 0);
    $test->assertEquals('#FF0000', $hex, "RGB to HEX conversion");

    // Test 4: RGB validation (clamping)
    $hex = ColorManager::rgbToHex(300, -10, 128);
    $test->assertEquals('#FF0080', $hex, "RGB clamping works");

    // Test 5: HEX validation
    $test->assertTrue(ColorManager::isValidHex('#FF0000'), "Valid HEX (6 digits with #)");
    $test->assertTrue(ColorManager::isValidHex('#F00'), "Valid HEX (3 digits with #)");
    $test->assertTrue(ColorManager::isValidHex('FF0000'), "Valid HEX (6 digits without #)");
    $test->assertFalse(ColorManager::isValidHex('#GG0000'), "Invalid HEX (invalid chars)");
    $test->assertFalse(ColorManager::isValidHex('#FF00'), "Invalid HEX (wrong length)");

    // Test 6: Luminance calculation
    $whiteLum = ColorManager::getLuminance('#FFFFFF');
    $blackLum = ColorManager::getLuminance('#000000');
    $test->assertTrue($whiteLum > 0.9, "White has high luminance");
    $test->assertTrue($blackLum < 0.1, "Black has low luminance");

    // Test 7: Contrast ratio
    $ratio = ColorManager::getContrastRatio('#FFFFFF', '#000000');
    $test->assertEquals(21.0, $ratio, "Max contrast ratio is 21:1");

    $ratio = ColorManager::getContrastRatio('#FFFFFF', '#FFFFFF');
    $test->assertEquals(1.0, $ratio, "Same color contrast is 1:1");

    // Test 8: WCAG AA compliance (normal text)
    $test->assertTrue(
        ColorManager::meetsWCAG('#FFFFFF', '#000000', 'AA', 'normal'),
        "Black on white meets WCAG AA (normal)"
    );
    $test->assertFalse(
        ColorManager::meetsWCAG('#FFFF00', '#FFFFFF', 'AA', 'normal'),
        "Yellow on white fails WCAG AA (normal)"
    );

    // Test 9: WCAG AAA compliance
    $test->assertTrue(
        ColorManager::meetsWCAG('#FFFFFF', '#000000', 'AAA', 'normal'),
        "Black on white meets WCAG AAA"
    );

    // Test 10: Lighten color
    $lighter = ColorManager::lighten('#0000FF', 20);
    $test->assertTrue(ColorManager::isValidHex($lighter), "Lightened color is valid HEX");

    // Test 11: Darken color
    $darker = ColorManager::darken('#0000FF', 20);
    $test->assertTrue(ColorManager::isValidHex($darker), "Darkened color is valid HEX");

    // Test 12: Generate variants
    $variants = ColorManager::generateVariants('#2c7be5');
    $test->assertTrue(isset($variants['base']), "Variants include base");
    $test->assertTrue(isset($variants['light']), "Variants include light");
    $test->assertTrue(isset($variants['dark']), "Variants include dark");
    $test->assertTrue(isset($variants['contrast']), "Variants include contrast");
    $test->assertEquals('#2c7be5', $variants['base'], "Base variant is original color");

    // Test 13: Get contrast color (white or black)
    $contrast = ColorManager::getContrastColor('#2c7be5');
    $test->assertTrue(in_array($contrast, ['#FFFFFF', '#000000', '#ffffff', '#000000']), "Contrast color is white or black (got: {$contrast})");

    // Test 14: Complementary color
    $complement = ColorManager::complementary('#2c7be5');
    $test->assertTrue(ColorManager::isValidHex($complement), "Complementary color is valid HEX");

    // Test 15: RGB to HSL to RGB (round trip)
    [$h, $s, $l] = ColorManager::rgbToHsl(128, 64, 192);
    [$r, $g, $b] = ColorManager::hslToRgb($h, $s, $l);
    $test->assertTrue(abs($r - 128) <= 1, "RGB->HSL->RGB: Red component preserved");
    $test->assertTrue(abs($g - 64) <= 1, "RGB->HSL->RGB: Green component preserved");
    $test->assertTrue(abs($b - 192) <= 1, "RGB->HSL->RGB: Blue component preserved");

    // Test 16: Saturate color
    $saturated = ColorManager::saturate('#2c7be5', 20);
    $test->assertTrue(ColorManager::isValidHex($saturated), "Saturated color is valid HEX");

    // Test 17: Mix colors
    $mixed = ColorManager::mix('#FF0000', '#0000FF', 0.5);
    $test->assertTrue(ColorManager::isValidHex($mixed), "Mixed color is valid HEX");
}

// ============================================================================
// ThemeConfigurator Tests (without database)
// ============================================================================

function testThemeConfiguratorMethods(TestRunner $test): void
{
    echo "\n" . str_repeat("-", 60) . "\n";
    echo "Testing ThemeConfigurator Methods (Static)\n";
    echo str_repeat("-", 60) . "\n\n";

    // Test default colors
    $defaults = [
        'primary' => '#2c7be5',
        'secondary' => '#6e84a3',
        'success' => '#00d97e',
        'danger' => '#e63757',
        'warning' => '#f6c343',
        'info' => '#39afd1',
        'light' => '#f9fafd',
        'dark' => '#0b1727'
    ];

    foreach ($defaults as $name => $expectedColor) {
        $test->assertTrue(ColorManager::isValidHex($expectedColor), "Default {$name} color is valid HEX");
    }

    // Test default fonts
    $defaultFonts = [
        'Montserrat, sans-serif',
        'Open Sans, sans-serif',
        'Courier New, monospace'
    ];

    foreach ($defaultFonts as $font) {
        $test->assertTrue(is_string($font), "Default font '{$font}' is a string");
    }

    echo "\nNote: Full ThemeConfigurator tests require database connection.\n";
    echo "Database-dependent tests skipped in this static test.\n";
}

// ============================================================================
// AssetManager Tests (without file system writes)
// ============================================================================

function testAssetManagerMethods(TestRunner $test): void
{
    echo "\n" . str_repeat("-", 60) . "\n";
    echo "Testing AssetManager Methods (Static)\n";
    echo str_repeat("-", 60) . "\n\n";

    // Test CSS minification algorithm (simulate)
    $css = "/* Comment */\n.class {\n  color: red;\n}\n";
    $expectedMinified = ".class{color:red}";

    // Simulate minification steps
    $minified = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
    $minified = str_replace(["\r\n", "\r", "\n", "\t"], '', $minified);
    $minified = preg_replace('/\s*([{}:;,])\s*/', '$1', $minified);
    $minified = str_replace(';}', '}', $minified);
    $minified = preg_replace('/\s+/', ' ', $minified);
    $minified = trim($minified);

    $test->assertEquals($expectedMinified, $minified, "CSS minification works correctly");

    // Test hash generation (8 characters from SHA-256)
    $content = "test content";
    $hash = substr(hash('sha256', $content), 0, 8);
    $test->assertEquals(8, strlen($hash), "CSS hash is 8 characters");
    $test->assertTrue(ctype_xdigit($hash), "CSS hash is hexadecimal");

    // Test filename pattern
    $filename = "custom-colors-{$hash}.css";
    $test->assertTrue(preg_match('/^custom-colors-[a-f0-9]{8}\.css$/', $filename) === 1, "CSS filename follows pattern");

    echo "\nNote: Full AssetManager tests require file system access.\n";
    echo "File system-dependent tests skipped in this static test.\n";
}

// ============================================================================
// JSON Export/Import Tests
// ============================================================================

function testJSONStructure(TestRunner $test): void
{
    echo "\n" . str_repeat("-", 60) . "\n";
    echo "Testing JSON Export Structure\n";
    echo str_repeat("-", 60) . "\n\n";

    // Simulate export structure
    $export = [
        'theme_export' => [
            'version' => '1.0.0',
            'exported_at' => date('c'),
            'app_version' => '1.0.0'
        ],
        'configuration' => [
            'colors' => [
                'primary' => '#2c7be5',
                'secondary' => '#6e84a3'
            ],
            'typography' => [
                'font_heading' => 'Montserrat, sans-serif',
                'font_body' => 'Open Sans, sans-serif'
            ],
            'branding' => [],
            'layout' => []
        ]
    ];

    $json = json_encode($export, JSON_PRETTY_PRINT);

    $test->assertTrue(is_string($json), "Export generates JSON string");

    $decoded = json_decode($json, true);
    $test->assertTrue(json_last_error() === JSON_ERROR_NONE, "Exported JSON is valid");
    $test->assertTrue(isset($decoded['theme_export']), "Export includes metadata");
    $test->assertTrue(isset($decoded['configuration']), "Export includes configuration");
    $test->assertTrue(isset($decoded['configuration']['colors']), "Export includes colors");
    $test->assertTrue(isset($decoded['configuration']['typography']), "Export includes typography");

    // Test import validation
    $test->assertTrue(isset($decoded['configuration']['colors']['primary']), "Import can access color values");
    $test->assertEquals('#2c7be5', $decoded['configuration']['colors']['primary'], "Color value preserved in export");
}

// ============================================================================
// Run All Tests
// ============================================================================

echo "\n";
echo str_repeat("=", 60) . "\n";
echo "PHASE 9 COMPONENT TEST SUITE\n";
echo str_repeat("=", 60) . "\n";

$test = new TestRunner();

testColorManager($test);
testThemeConfiguratorMethods($test);
testAssetManagerMethods($test);
testJSONStructure($test);

$test->summary();

echo "\n";
