<?php

/**
 * ISER Authentication System - Environment Unit Tests
 *
 * @package    ISER\Tests\Unit\Core
 * @category   Tests
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Phase 1
 */

namespace ISER\Tests\Unit\Core;

use ISER\Core\Config\Environment;
use PHPUnit\Framework\TestCase;

class EnvironmentTest extends TestCase
{
    private Environment $environment;

    protected function setUp(): void
    {
        $this->environment = new Environment('testing', true);
    }

    public function testEnvironmentCreation(): void
    {
        $this->assertInstanceOf(Environment::class, $this->environment);
    }

    public function testGetEnvironment(): void
    {
        $this->assertEquals('testing', $this->environment->getEnvironment());
    }

    public function testIsDevelopment(): void
    {
        $devEnv = new Environment('development');
        $this->assertTrue($devEnv->isDevelopment());
        $this->assertFalse($this->environment->isDevelopment());
    }

    public function testIsProduction(): void
    {
        $prodEnv = new Environment('production');
        $this->assertTrue($prodEnv->isProduction());
        $this->assertFalse($this->environment->isProduction());
    }

    public function testIsTesting(): void
    {
        $this->assertTrue($this->environment->isTesting());
    }

    public function testIsDebugMode(): void
    {
        $this->assertTrue($this->environment->isDebugMode());
    }

    public function testValidatePhpVersion(): void
    {
        $result = $this->environment->validatePhpVersion();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('current', $result);
        $this->assertArrayHasKey('required', $result);
        $this->assertTrue($result['valid']);
    }

    public function testValidateRequiredExtensions(): void
    {
        $result = $this->environment->validateRequiredExtensions();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('loaded', $result);
        $this->assertTrue($result['valid']);
    }

    public function testCheckRecommendedExtensions(): void
    {
        $result = $this->environment->checkRecommendedExtensions();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('loaded', $result);
        $this->assertArrayHasKey('missing', $result);
    }

    public function testGetPhpInfo(): void
    {
        $info = $this->environment->getPhpInfo();

        $this->assertIsArray($info);
        $this->assertArrayHasKey('version', $info);
        $this->assertArrayHasKey('sapi', $info);
        $this->assertArrayHasKey('os', $info);
        $this->assertArrayHasKey('extensions', $info);
    }

    public function testGetSystemInfo(): void
    {
        $info = $this->environment->getSystemInfo();

        $this->assertIsArray($info);
        $this->assertArrayHasKey('environment', $info);
        $this->assertArrayHasKey('debug_mode', $info);
        $this->assertArrayHasKey('php', $info);
        $this->assertArrayHasKey('requirements', $info);
    }

    public function testConfigurePhpSettings(): void
    {
        // This should not throw any exceptions
        $this->environment->configurePhpSettings();
        $this->assertTrue(true);
    }
}
