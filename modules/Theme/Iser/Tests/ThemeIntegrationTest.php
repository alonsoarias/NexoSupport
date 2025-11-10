<?php
/**
 * Tests de integración del tema ISER
 * @package theme_iser
 */

namespace ISER\Theme\Iser\Tests;

use PHPUnit\Framework\TestCase;
use ISER\Theme\Iser\ThemeIser;
use ISER\Theme\Iser\ThemeRenderer;
use ISER\Theme\Iser\ThemeAssets;
use ISER\Theme\Iser\ThemeLayouts;
use ISER\Core\Database\Database;
use ISER\Core\Config\SettingsManager;
use Monolog\Logger;
use Monolog\Handler\NullHandler;

class ThemeIntegrationTest extends TestCase
{
    private $db;
    private $settings;
    private $logger;

    protected function setUp(): void
    {
        $this->db = $this->createMock(Database::class);
        $this->settings = $this->createMock(SettingsManager::class);

        $this->logger = new Logger('test');
        $this->logger->pushHandler(new NullHandler());
    }

    public function testThemeInitialization()
    {
        $theme = new ThemeIser($this->db, $this->settings, $this->logger);
        $this->assertInstanceOf(ThemeIser::class, $theme);
    }

    public function testLayoutRendering()
    {
        $renderer = new ThemeRenderer();
        $html = $renderer->bsAlert('info', 'Test message');

        $this->assertStringContainsString('alert', $html);
        $this->assertStringContainsString('Test message', $html);
    }

    public function testAssetManagement()
    {
        $assets = new ThemeAssets();
        $assets->addCss('test.css');
        $assets->addJs('test.js');

        $cssFiles = $assets->getCssFiles();
        $jsFiles = $assets->getJsFiles();

        $this->assertNotEmpty($cssFiles);
        $this->assertNotEmpty($jsFiles);
    }

    public function testColorPalette()
    {
        $theme = new ThemeIser($this->db, $this->settings, $this->logger);
        $settings = $theme->getThemeSettings();

        $this->assertArrayHasKey('colors', $settings);
        $this->assertArrayHasKey('primary', $settings['colors']);
        $this->assertEquals('#2c7be5', $settings['colors']['primary']);
    }

    public function testLayoutsConfiguration()
    {
        $layouts = new ThemeLayouts();
        $allLayouts = $layouts->getAllLayouts();

        $this->assertIsArray($allLayouts);
        $this->assertArrayHasKey('base', $allLayouts);
        $this->assertArrayHasKey('admin', $allLayouts);
        $this->assertArrayHasKey('login', $allLayouts);
    }

    public function testLayoutHasRequiredProperties()
    {
        $layouts = new ThemeLayouts();
        $baseLayout = $layouts->getBaseLayout();

        $this->assertArrayHasKey('template', $baseLayout);
        $this->assertArrayHasKey('regions', $baseLayout);
        $this->assertArrayHasKey('has_sidebar', $baseLayout);
    }

    public function testRendererHelpers()
    {
        $renderer = new ThemeRenderer();

        // Test alert
        $alert = $renderer->bsAlert('success', 'Success!');
        $this->assertStringContainsString('alert-success', $alert);

        // Test badge
        $badge = $renderer->bsBadge('New', 'danger');
        $this->assertStringContainsString('badge', $badge);
        $this->assertStringContainsString('bg-danger', $badge);

        // Test button
        $button = $renderer->bsButton('Submit', 'primary');
        $this->assertStringContainsString('btn-primary', $button);
    }

    public function testAssetsPriority()
    {
        $assets = new ThemeAssets();

        $assets->addCss('low.css', [], 100);
        $assets->addCss('high.css', [], 10);

        $files = $assets->getCssFiles();

        $this->assertEquals(2, count($files));
        // El archivo con prioridad 10 debería estar primero
        $this->assertStringContainsString('high.css', $files[0]);
    }

    public function testFormValidation()
    {
        $renderer = new ThemeRenderer();

        $input = $renderer->formInput('email', 'Email', '', [
            'type' => 'email',
            'required' => true,
            'placeholder' => 'user@example.com'
        ]);

        $this->assertStringContainsString('required', $input);
        $this->assertStringContainsString('email', $input);
    }
}
