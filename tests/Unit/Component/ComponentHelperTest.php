<?php
/**
 * ComponentHelper Test
 *
 * Comprehensive unit tests for the ComponentHelper class
 *
 * @package    ISER\Tests\Unit\Component
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Tests\Unit\Component;

use ISER\Core\Component\ComponentHelper;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * ComponentHelper test cases
 */
class ComponentHelperTest extends TestCase
{
    private ComponentHelper $helper;
    private string $componentsJsonPath;
    private ?string $originalJsonContent = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Store original components.json content for restoration
        $this->componentsJsonPath = LIB_DIR . '/components.json';
        if (file_exists($this->componentsJsonPath)) {
            $this->originalJsonContent = file_get_contents($this->componentsJsonPath);
        }

        // Create test components.json
        $this->createTestComponentsJson();

        // Reset singleton instance using reflection
        $reflection = new \ReflectionClass(ComponentHelper::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);

        $components = $reflection->getProperty('components');
        $components->setAccessible(true);
        $components->setValue(null, null);

        $this->helper = ComponentHelper::getInstance();
    }

    protected function tearDown(): void
    {
        // Restore original components.json
        if ($this->originalJsonContent !== null) {
            file_put_contents($this->componentsJsonPath, $this->originalJsonContent);
        }

        parent::tearDown();
    }

    /**
     * Create test components.json file
     */
    private function createTestComponentsJson(): void
    {
        $testConfig = [
            'plugintypes' => [
                'auth' => 'auth',
                'tool' => 'admin/tool',
                'report' => 'report',
                'theme' => 'theme',
            ],
            'subsystems' => [
                'core' => 'lib/classes',
            ]
        ];

        file_put_contents($this->componentsJsonPath, json_encode($testConfig, JSON_PRETTY_PRINT));
    }

    #[Test]
    public function it_implements_singleton_pattern(): void
    {
        // Arrange & Act
        $instance1 = ComponentHelper::getInstance();
        $instance2 = ComponentHelper::getInstance();

        // Assert
        $this->assertInstanceOf(ComponentHelper::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }

    #[Test]
    public function it_resolves_valid_component_path_for_auth_type(): void
    {
        // Arrange
        $component = 'auth_manual';

        // Act
        $path = $this->helper->getPath($component);

        // Assert
        $this->assertNotNull($path);
        $this->assertStringContainsString('auth/manual', $path);
        $this->assertStringStartsWith(BASE_DIR, $path);
    }

    #[Test]
    public function it_resolves_valid_component_path_for_report_type(): void
    {
        // Arrange
        $component = 'report_log';

        // Act
        $path = $this->helper->getPath($component);

        // Assert
        $this->assertNotNull($path);
        $this->assertStringContainsString('report/log', $path);
    }

    #[Test]
    public function it_returns_null_for_invalid_component_without_underscore(): void
    {
        // Arrange
        $component = 'invalidcomponent';

        // Act
        $path = $this->helper->getPath($component);

        // Assert
        $this->assertNull($path);
    }

    #[Test]
    public function it_returns_null_for_non_existent_component_type(): void
    {
        // Arrange
        $component = 'nonexistent_component';

        // Act
        $path = $this->helper->getPath($component);

        // Assert
        $this->assertNull($path);
    }

    #[Test]
    public function it_returns_null_for_non_existent_component_name(): void
    {
        // Arrange - valid type, but component doesn't exist
        $component = 'auth_nonexistent12345';

        // Act
        $path = $this->helper->getPath($component);

        // Assert
        $this->assertNull($path);
    }

    #[Test]
    public function it_checks_component_exists_returns_true_for_valid_component(): void
    {
        // Arrange
        $component = 'report_log';

        // Act
        $exists = $this->helper->componentExists($component);

        // Assert
        $this->assertTrue($exists);
    }

    #[Test]
    public function it_checks_component_exists_returns_false_for_invalid_component(): void
    {
        // Arrange
        $component = 'invalid_component123';

        // Act
        $exists = $this->helper->componentExists($component);

        // Assert
        $this->assertFalse($exists);
    }

    #[Test]
    public function it_parses_valid_component_name(): void
    {
        // Arrange
        $component = 'auth_manual';

        // Act
        $parsed = $this->helper->parseComponent($component);

        // Assert
        $this->assertIsArray($parsed);
        $this->assertArrayHasKey('type', $parsed);
        $this->assertArrayHasKey('name', $parsed);
        $this->assertEquals('auth', $parsed['type']);
        $this->assertEquals('manual', $parsed['name']);
    }

    #[Test]
    public function it_parses_component_with_multiple_underscores(): void
    {
        // Arrange
        $component = 'tool_uploaduser';

        // Act
        $parsed = $this->helper->parseComponent($component);

        // Assert
        $this->assertIsArray($parsed);
        $this->assertEquals('tool', $parsed['type']);
        $this->assertEquals('uploaduser', $parsed['name']);
    }

    #[Test]
    public function it_returns_null_when_parsing_invalid_component_name(): void
    {
        // Arrange
        $component = 'invalidname';

        // Act
        $parsed = $this->helper->parseComponent($component);

        // Assert
        $this->assertNull($parsed);
    }

    #[Test]
    public function it_gets_all_plugin_types(): void
    {
        // Arrange & Act
        $types = $this->helper->getPluginTypes();

        // Assert
        $this->assertIsArray($types);
        $this->assertArrayHasKey('auth', $types);
        $this->assertArrayHasKey('tool', $types);
        $this->assertArrayHasKey('report', $types);
        $this->assertEquals('auth', $types['auth']);
        $this->assertEquals('admin/tool', $types['tool']);
    }

    #[Test]
    public function it_gets_components_by_valid_type(): void
    {
        // Arrange
        $type = 'report';

        // Act
        $components = $this->helper->getComponentsByType($type);

        // Assert
        $this->assertIsArray($components);
        $this->assertNotEmpty($components);
        $this->assertContains('report_log', $components);
    }

    #[Test]
    public function it_returns_empty_array_for_invalid_type(): void
    {
        // Arrange
        $type = 'nonexistent';

        // Act
        $components = $this->helper->getComponentsByType($type);

        // Assert
        $this->assertIsArray($components);
        $this->assertEmpty($components);
    }

    #[Test]
    public function it_gets_all_components(): void
    {
        // Arrange & Act
        $components = $this->helper->getAllComponents();

        // Assert
        $this->assertIsArray($components);
        $this->assertNotEmpty($components);

        // Should contain components from different types
        foreach ($components as $component) {
            $this->assertStringContainsString('_', $component);
        }
    }

    #[Test]
    public function it_requires_lib_returns_false_for_invalid_component(): void
    {
        // Arrange
        $component = 'invalid_component123';

        // Act
        $result = $this->helper->requireLib($component);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function it_clears_cache_successfully(): void
    {
        // Arrange
        $initialTypes = $this->helper->getPluginTypes();

        // Act
        $this->helper->clearCache();
        $typesAfterClear = $this->helper->getPluginTypes();

        // Assert - should still work after cache clear
        $this->assertEquals($initialTypes, $typesAfterClear);
    }

    #[Test]
    public function it_reloads_components_successfully(): void
    {
        // Arrange
        $initialTypes = $this->helper->getPluginTypes();

        // Act
        $this->helper->reload();
        $typesAfterReload = $this->helper->getPluginTypes();

        // Assert
        $this->assertEquals($initialTypes, $typesAfterReload);
    }

    #[Test]
    public function it_handles_empty_component_name(): void
    {
        // Arrange
        $component = '';

        // Act
        $path = $this->helper->getPath($component);

        // Assert
        $this->assertNull($path);
    }

    #[Test]
    public function it_handles_component_with_only_underscore(): void
    {
        // Arrange
        $component = '_';

        // Act
        $path = $this->helper->getPath($component);

        // Assert
        $this->assertNull($path);
    }

    #[Test]
    public function it_handles_component_starting_with_underscore(): void
    {
        // Arrange
        $component = '_component';

        // Act
        $parsed = $this->helper->parseComponent($component);

        // Assert
        $this->assertIsArray($parsed);
        $this->assertEquals('', $parsed['type']);
        $this->assertEquals('component', $parsed['name']);
    }

    #[Test]
    #[DataProvider('componentNameProvider')]
    public function it_handles_various_component_name_formats(string $component, ?array $expected): void
    {
        // Act
        $parsed = $this->helper->parseComponent($component);

        // Assert
        if ($expected === null) {
            $this->assertNull($parsed);
        } else {
            $this->assertEquals($expected, $parsed);
        }
    }

    /**
     * Data provider for component name testing
     */
    public static function componentNameProvider(): array
    {
        return [
            'simple component' => [
                'auth_manual',
                ['type' => 'auth', 'name' => 'manual']
            ],
            'tool component' => [
                'tool_uploaduser',
                ['type' => 'tool', 'name' => 'uploaduser']
            ],
            'report component' => [
                'report_log',
                ['type' => 'report', 'name' => 'log']
            ],
            'component with underscores in name' => [
                'auth_ldap_sync',
                ['type' => 'auth', 'name' => 'ldap_sync']
            ],
            'no underscore' => [
                'invalid',
                null
            ],
            'empty string' => [
                '',
                null
            ],
        ];
    }

    #[Test]
    public function it_loads_components_map_only_once(): void
    {
        // Arrange - Get instance multiple times
        $helper1 = ComponentHelper::getInstance();
        $helper2 = ComponentHelper::getInstance();

        // Act
        $types1 = $helper1->getPluginTypes();
        $types2 = $helper2->getPluginTypes();

        // Assert - should return same data without reloading
        $this->assertSame($types1, $types2);
        $this->assertSame($helper1, $helper2);
    }

    #[Test]
    public function it_handles_missing_components_json_gracefully(): void
    {
        // Arrange - temporarily remove components.json
        $backup = null;
        if (file_exists($this->componentsJsonPath)) {
            $backup = file_get_contents($this->componentsJsonPath);
            unlink($this->componentsJsonPath);
        }

        // Reset singleton
        $reflection = new \ReflectionClass(ComponentHelper::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);

        $components = $reflection->getProperty('components');
        $components->setAccessible(true);
        $components->setValue(null, null);

        // Act
        $helper = ComponentHelper::getInstance();
        $types = $helper->getPluginTypes();

        // Assert
        $this->assertIsArray($types);
        $this->assertEmpty($types);

        // Cleanup - restore file
        if ($backup !== null) {
            file_put_contents($this->componentsJsonPath, $backup);
        }
    }
}
