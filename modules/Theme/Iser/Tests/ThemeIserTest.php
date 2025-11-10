<?php
/**
 * Tests para la clase ThemeIser
 * @package theme_iser
 */

namespace ISER\Modules\Theme\Iser\Tests;

use PHPUnit\Framework\TestCase;
use ISER\Modules\Theme\Iser\ThemeIser;
use ISER\Core\Database\Database;
use ISER\Core\Config\SettingsManager;
use Monolog\Logger;
use Monolog\Handler\NullHandler;

class ThemeIserTest extends TestCase
{
    private $db;
    private $settings;
    private $logger;
    private $theme;

    protected function setUp(): void
    {
        // Mock de Database
        $this->db = $this->createMock(Database::class);

        // Mock de SettingsManager
        $this->settings = $this->createMock(SettingsManager::class);
        $this->settings->method('getString')
            ->willReturn('ISER Plataforma');

        // Logger real pero con NullHandler
        $this->logger = new Logger('test');
        $this->logger->pushHandler(new NullHandler());

        // Crear instancia del tema
        $this->theme = new ThemeIser(
            $this->db,
            $this->settings,
            $this->logger,
            1
        );
    }

    public function testThemeInitialization()
    {
        $this->assertInstanceOf(ThemeIser::class, $this->theme);
    }

    public function testGetThemeSettings()
    {
        $settings = $this->theme->getThemeSettings();

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('name', $settings);
        $this->assertEquals('iser', $settings['name']);
        $this->assertArrayHasKey('version', $settings);
        $this->assertArrayHasKey('colors', $settings);
    }

    public function testGetLayout()
    {
        $layout = $this->theme->getLayout('base');

        $this->assertIsArray($layout);
        $this->assertArrayHasKey('template', $layout);
        $this->assertEquals('layouts/base', $layout['template']);
    }

    public function testGetLogoUrl()
    {
        $logoUrl = $this->theme->getLogoUrl();

        $this->assertIsString($logoUrl);
        $this->assertStringContainsString('iser-logo', $logoUrl);
    }

    public function testGetFaviconUrl()
    {
        $faviconUrl = $this->theme->getFaviconUrl();

        $this->assertIsString($faviconUrl);
        $this->assertStringContainsString('favicon', $faviconUrl);
    }

    public function testGetUserThemePreferences()
    {
        $preferences = $this->theme->getUserThemePreferences(1);

        $this->assertIsArray($preferences);
        $this->assertArrayHasKey('theme_mode', $preferences);
        $this->assertArrayHasKey('sidebar_collapsed', $preferences);
    }

    public function testUpdateThemeSettings()
    {
        $newSettings = [
            'colors' => [
                'primary' => '#ff0000'
            ]
        ];

        $result = $this->theme->updateThemeSettings($newSettings);

        $this->assertTrue($result);
    }

    public function testGetRenderer()
    {
        $renderer = $this->theme->getRenderer();

        $this->assertInstanceOf(\ISER\Core\Render\MustacheRenderer::class, $renderer);
    }

    public function testGetAssets()
    {
        $assets = $this->theme->getAssets();

        $this->assertInstanceOf(\ISER\Modules\Theme\Iser\ThemeAssets::class, $assets);
    }

    public function testGetLayouts()
    {
        $layouts = $this->theme->getLayouts();

        $this->assertInstanceOf(\ISER\Modules\Theme\Iser\ThemeLayouts::class, $layouts);
    }

    public function testGetNavigation()
    {
        $navigation = $this->theme->getNavigation();

        $this->assertInstanceOf(\ISER\Modules\Theme\Iser\ThemeNavigation::class, $navigation);
    }
}
