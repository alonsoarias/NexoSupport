<?php

/**
 * ISER Authentication System - Core System Integration Tests
 *
 * @package    ISER\Tests\Integration
 * @category   Tests
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Phase 1
 */

namespace ISER\Tests\Integration;

use ISER\Core\Bootstrap;
use ISER\Core\Config\ConfigManager;
use ISER\Core\Config\Environment;
use ISER\Core\Utils\Logger;
use PHPUnit\Framework\TestCase;

class CoreSystemTest extends TestCase
{
    private Bootstrap $bootstrap;

    protected function setUp(): void
    {
        $this->bootstrap = new Bootstrap(ISER_BASE_DIR);
    }

    public function testSystemInitialization(): void
    {
        $this->assertInstanceOf(Bootstrap::class, $this->bootstrap);

        // Initialize the system
        $this->bootstrap->init();

        $this->assertTrue($this->bootstrap->isInitialized());
    }

    public function testConfigurationLoaded(): void
    {
        $this->bootstrap->init();

        $config = $this->bootstrap->getConfig();
        $this->assertInstanceOf(ConfigManager::class, $config);

        // Test basic config access
        $this->assertNotNull($config->get('APP_ENV'));
    }

    public function testEnvironmentSetup(): void
    {
        $this->bootstrap->init();

        $environment = $this->bootstrap->getEnvironment();
        $this->assertInstanceOf(Environment::class, $environment);

        $this->assertEquals('testing', $environment->getEnvironment());
    }

    public function testLoggingInitialized(): void
    {
        $this->bootstrap->init();

        $this->assertTrue(Logger::isInitialized());

        // Test logging
        Logger::info('Test log message');
        $this->assertTrue(true); // If no exception, test passes
    }

    public function testRouterAvailable(): void
    {
        $this->bootstrap->init();

        $router = $this->bootstrap->getRouter();
        $this->assertNotNull($router);
    }

    public function testAutoloaderAvailable(): void
    {
        $this->bootstrap->init();

        $autoloader = $this->bootstrap->getAutoloader();
        $this->assertNotNull($autoloader);
    }

    public function testJWTSessionAvailable(): void
    {
        $this->bootstrap->init();

        $jwtSession = $this->bootstrap->getJWTSession();
        $this->assertNotNull($jwtSession);
    }

    public function testGetSystemInfo(): void
    {
        $this->bootstrap->init();

        $info = $this->bootstrap->getSystemInfo();

        $this->assertIsArray($info);
        $this->assertArrayHasKey('version', $info);
        $this->assertArrayHasKey('initialized', $info);
        $this->assertArrayHasKey('environment', $info);
        $this->assertArrayHasKey('php_version', $info);

        $this->assertTrue($info['initialized']);
        $this->assertEquals('testing', $info['environment']);
    }

    public function testGetVersion(): void
    {
        $version = $this->bootstrap->getVersion();

        $this->assertIsString($version);
        $this->assertEquals('1.0.0', $version);
    }

    public function testGetBaseDir(): void
    {
        $baseDir = $this->bootstrap->getBaseDir();

        $this->assertIsString($baseDir);
        $this->assertDirectoryExists($baseDir);
    }

    public function testModulesDiscovery(): void
    {
        $this->bootstrap->init();

        $modules = $this->bootstrap->getModules();

        $this->assertIsArray($modules);
        // Modules array might be empty in Phase 1, which is okay
    }

    public function testSystemCanHandleMultipleInitializations(): void
    {
        // First initialization
        $this->bootstrap->init();
        $this->assertTrue($this->bootstrap->isInitialized());

        // Second initialization should not cause errors
        $this->bootstrap->init();
        $this->assertTrue($this->bootstrap->isInitialized());
    }
}
