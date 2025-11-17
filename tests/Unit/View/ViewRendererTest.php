<?php
/**
 * ViewRenderer Test
 *
 * Comprehensive unit tests for the ViewRenderer class
 *
 * @package    ISER\Tests\Unit\View
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Tests\Unit\View;

use ISER\Core\View\ViewRenderer;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * ViewRenderer test cases
 */
class ViewRendererTest extends TestCase
{
    private ViewRenderer $renderer;
    private string $testTemplatesDir;
    private array $createdFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Reset singleton instance using reflection
        $reflection = new \ReflectionClass(ViewRenderer::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);

        // Create test template directories
        $this->testTemplatesDir = BASE_DIR . '/tests/Fixtures/templates';
        $this->createTestTemplateStructure();

        $this->renderer = ViewRenderer::getInstance();
    }

    protected function tearDown(): void
    {
        // Clean up created test files
        foreach ($this->createdFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        // Clean up directories
        $this->removeTestDirectories();

        parent::tearDown();
    }

    /**
     * Create test template directory structure
     */
    private function createTestTemplateStructure(): void
    {
        $dirs = [
            BASE_DIR . '/report/log/templates',
            BASE_DIR . '/admin/user/templates',
            BASE_DIR . '/core/templates',
            BASE_DIR . '/templates',
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    /**
     * Remove test directories
     */
    private function removeTestDirectories(): void
    {
        $dirs = [
            BASE_DIR . '/report/log/templates',
            BASE_DIR . '/admin/user/templates',
            BASE_DIR . '/core/templates',
        ];

        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                rmdir($dir);
            }
        }
    }

    /**
     * Create a test template file
     */
    private function createTestTemplate(string $path, string $content): string
    {
        $fullPath = BASE_DIR . '/' . $path;
        $dir = dirname($fullPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($fullPath, $content);
        $this->createdFiles[] = $fullPath;

        return $fullPath;
    }

    #[Test]
    public function it_implements_singleton_pattern(): void
    {
        // Arrange & Act
        $instance1 = ViewRenderer::getInstance();
        $instance2 = ViewRenderer::getInstance();

        // Assert
        $this->assertInstanceOf(ViewRenderer::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }

    #[Test]
    public function it_renders_simple_template(): void
    {
        // Arrange
        $this->createTestTemplate('report/log/templates/simple.mustache', 'Hello {{name}}!');

        $data = ['name' => 'World'];

        // Act
        $result = $this->renderer->render('report_log/simple', $data);

        // Assert
        $this->assertEquals('Hello World!', $result);
    }

    #[Test]
    public function it_renders_template_with_multiple_variables(): void
    {
        // Arrange
        $template = 'User: {{username}}, Email: {{email}}, Role: {{role}}';
        $this->createTestTemplate('admin/user/templates/profile.mustache', $template);

        $data = [
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'role' => 'admin'
        ];

        // Act
        $result = $this->renderer->render('admin_user/profile', $data);

        // Assert
        $this->assertStringContainsString('john_doe', $result);
        $this->assertStringContainsString('john@example.com', $result);
        $this->assertStringContainsString('admin', $result);
    }

    #[Test]
    public function it_escapes_html_in_template_variables(): void
    {
        // Arrange
        $this->createTestTemplate('core/templates/test.mustache', '<div>{{content}}</div>');

        $data = ['content' => '<script>alert("XSS")</script>'];

        // Act
        $result = $this->renderer->render('core/test', $data);

        // Assert
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    #[Test]
    public function it_throws_exception_for_non_existent_template(): void
    {
        // Arrange
        $nonExistentTemplate = 'report_log/nonexistent';

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Template not found');

        // Act
        $this->renderer->render($nonExistentTemplate, []);
    }

    #[Test]
    public function it_converts_component_name_to_path_correctly(): void
    {
        // Arrange
        $this->createTestTemplate('report/log/templates/index.mustache', 'Log Index');

        // Act
        $result = $this->renderer->render('report_log/index', []);

        // Assert
        $this->assertEquals('Log Index', $result);
    }

    #[Test]
    public function it_handles_admin_component_paths(): void
    {
        // Arrange
        $this->createTestTemplate('admin/user/templates/list.mustache', 'User List');

        // Act
        $result = $this->renderer->render('admin_user/list', []);

        // Assert
        $this->assertEquals('User List', $result);
    }

    #[Test]
    public function it_checks_if_template_exists(): void
    {
        // Arrange
        $this->createTestTemplate('report/log/templates/exists.mustache', 'Content');

        // Act
        $exists = $this->renderer->exists('report_log/exists');
        $notExists = $this->renderer->exists('report_log/notexists');

        // Assert
        $this->assertTrue($exists);
        $this->assertFalse($notExists);
    }

    #[Test]
    public function it_renders_partial_template(): void
    {
        // Arrange
        $this->createTestTemplate('report/log/templates/partial.mustache', 'Partial: {{value}}');

        $data = ['value' => 'test'];

        // Act
        $result = $this->renderer->renderPartial('report_log/partial', $data);

        // Assert
        $this->assertEquals('Partial: test', $result);
    }

    #[Test]
    public function it_renders_template_with_empty_data(): void
    {
        // Arrange
        $this->createTestTemplate('core/templates/empty.mustache', 'Static Content');

        // Act
        $result = $this->renderer->render('core/empty', []);

        // Assert
        $this->assertEquals('Static Content', $result);
    }

    #[Test]
    public function it_handles_template_with_sections(): void
    {
        // Arrange
        $template = '{{#items}}Item: {{name}}, {{/items}}';
        $this->createTestTemplate('report/log/templates/section.mustache', $template);

        $data = [
            'items' => [
                ['name' => 'First'],
                ['name' => 'Second'],
            ]
        ];

        // Act
        $result = $this->renderer->render('report_log/section', $data);

        // Assert
        $this->assertStringContainsString('Item: First', $result);
        $this->assertStringContainsString('Item: Second', $result);
    }

    #[Test]
    public function it_handles_template_with_inverted_sections(): void
    {
        // Arrange
        $template = '{{^items}}No items{{/items}}{{#items}}Has items{{/items}}';
        $this->createTestTemplate('report/log/templates/inverted.mustache', $template);

        // Act
        $resultEmpty = $this->renderer->render('report_log/inverted', ['items' => []]);
        $resultWithItems = $this->renderer->render('report_log/inverted', ['items' => ['item1']]);

        // Assert
        $this->assertEquals('No items', $resultEmpty);
        $this->assertEquals('Has items', $resultWithItems);
    }

    #[Test]
    #[DataProvider('componentPathProvider')]
    public function it_converts_various_component_paths(string $component, string $expectedPath): void
    {
        // Arrange - Use reflection to access private method
        $reflection = new \ReflectionClass(ViewRenderer::class);
        $method = $reflection->getMethod('componentToPath');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->renderer, $component);

        // Assert
        $this->assertEquals($expectedPath, $result);
    }

    /**
     * Data provider for component path conversion
     */
    public static function componentPathProvider(): array
    {
        return [
            'report_log' => ['report_log', 'report/log'],
            'admin_user' => ['admin_user', 'admin/user'],
            'core' => ['core', 'core'],
            'auth_manual' => ['auth_manual', 'auth/manual'],
            'tool_uploaduser' => ['tool_uploaduser', 'tool/uploaduser'],
        ];
    }

    #[Test]
    public function it_resolves_template_path_for_report_component(): void
    {
        // Arrange - Use reflection to access private method
        $reflection = new \ReflectionClass(ViewRenderer::class);
        $method = $reflection->getMethod('resolveTemplatePath');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->renderer, 'report_log/index');

        // Assert
        $this->assertStringEndsWith('report/log/templates/index.mustache', $result);
        $this->assertStringStartsWith(BASE_DIR, $result);
    }

    #[Test]
    public function it_resolves_template_path_for_admin_component(): void
    {
        // Arrange
        $reflection = new \ReflectionClass(ViewRenderer::class);
        $method = $reflection->getMethod('resolveTemplatePath');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->renderer, 'admin_user/profile');

        // Assert
        $this->assertStringEndsWith('admin/user/templates/profile.mustache', $result);
    }

    #[Test]
    public function it_handles_nested_template_paths(): void
    {
        // Arrange
        $this->createTestTemplate('report/log/templates/partials/table.mustache', 'Table Content');

        // Act
        $result = $this->renderer->render('report_log/partials/table', []);

        // Assert
        $this->assertEquals('Table Content', $result);
    }

    #[Test]
    public function it_handles_special_characters_in_data(): void
    {
        // Arrange
        $this->createTestTemplate('core/templates/special.mustache', 'Value: {{text}}');

        $data = ['text' => 'Special & chars < > " \''];

        // Act
        $result = $this->renderer->render('core/special', $data);

        // Assert
        $this->assertStringContainsString('&amp;', $result);
        $this->assertStringContainsString('&lt;', $result);
        $this->assertStringContainsString('&gt;', $result);
        $this->assertStringContainsString('&quot;', $result);
    }

    #[Test]
    public function it_renders_template_with_boolean_values(): void
    {
        // Arrange
        $template = '{{#active}}Active{{/active}}{{^active}}Inactive{{/active}}';
        $this->createTestTemplate('core/templates/boolean.mustache', $template);

        // Act
        $resultActive = $this->renderer->render('core/boolean', ['active' => true]);
        $resultInactive = $this->renderer->render('core/boolean', ['active' => false]);

        // Assert
        $this->assertEquals('Active', $resultActive);
        $this->assertEquals('Inactive', $resultInactive);
    }

    #[Test]
    public function it_renders_template_with_numeric_values(): void
    {
        // Arrange
        $this->createTestTemplate('core/templates/numbers.mustache', 'Count: {{count}}, Price: {{price}}');

        $data = ['count' => 42, 'price' => 19.99];

        // Act
        $result = $this->renderer->render('core/numbers', $data);

        // Assert
        $this->assertStringContainsString('Count: 42', $result);
        $this->assertStringContainsString('Price: 19.99', $result);
    }

    #[Test]
    public function it_renders_template_with_nested_objects(): void
    {
        // Arrange
        $template = 'User: {{user.name}}, Email: {{user.email}}';
        $this->createTestTemplate('core/templates/nested.mustache', $template);

        $data = [
            'user' => [
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ]
        ];

        // Act
        $result = $this->renderer->render('core/nested', $data);

        // Assert
        $this->assertStringContainsString('John Doe', $result);
        $this->assertStringContainsString('john@example.com', $result);
    }

    #[Test]
    public function it_handles_missing_variables_gracefully(): void
    {
        // Arrange
        $this->createTestTemplate('core/templates/missing.mustache', 'Value: {{missing_var}}');

        // Act
        $result = $this->renderer->render('core/missing', []);

        // Assert
        $this->assertEquals('Value: ', $result);
    }

    #[Test]
    public function it_adds_custom_helper(): void
    {
        // Arrange
        $helper = function($text) {
            return strtoupper($text);
        };

        // Act
        $this->renderer->addHelper('uppercase', $helper);

        // Assert - helper should be added without error
        $this->assertTrue(true);
    }

    #[Test]
    public function it_handles_core_templates_without_underscore(): void
    {
        // Arrange
        $this->createTestTemplate('core/templates/header.mustache', 'Header Content');

        // Act
        $result = $this->renderer->render('core/header', []);

        // Assert
        $this->assertEquals('Header Content', $result);
    }

    #[Test]
    public function it_returns_false_for_nonexistent_template_check(): void
    {
        // Arrange & Act
        $exists = $this->renderer->exists('nonexistent_component/template');

        // Assert
        $this->assertFalse($exists);
    }

    #[Test]
    public function it_renders_template_with_whitespace_preserved(): void
    {
        // Arrange
        $template = "Line 1\n    Line 2\n        Line 3";
        $this->createTestTemplate('core/templates/whitespace.mustache', $template);

        // Act
        $result = $this->renderer->render('core/whitespace', []);

        // Assert
        $this->assertStringContainsString("\n", $result);
        $this->assertStringContainsString('    Line 2', $result);
    }

    #[Test]
    public function it_handles_empty_template_file(): void
    {
        // Arrange
        $this->createTestTemplate('core/templates/empty_file.mustache', '');

        // Act
        $result = $this->renderer->render('core/empty_file', []);

        // Assert
        $this->assertEquals('', $result);
    }

    #[Test]
    public function it_handles_template_with_only_whitespace(): void
    {
        // Arrange
        $this->createTestTemplate('core/templates/whitespace_only.mustache', '   ');

        // Act
        $result = $this->renderer->render('core/whitespace_only', []);

        // Assert
        $this->assertEquals('   ', $result);
    }

    #[Test]
    public function it_renders_complex_template_with_multiple_features(): void
    {
        // Arrange
        $template = <<<'MUSTACHE'
<h1>{{title}}</h1>
{{#users}}
<div class="user">
    <span>{{name}}</span> - {{email}}
</div>
{{/users}}
{{^users}}
<p>No users found</p>
{{/users}}
MUSTACHE;

        $this->createTestTemplate('admin/user/templates/complex.mustache', $template);

        $data = [
            'title' => 'User List',
            'users' => [
                ['name' => 'Alice', 'email' => 'alice@example.com'],
                ['name' => 'Bob', 'email' => 'bob@example.com'],
            ]
        ];

        // Act
        $result = $this->renderer->render('admin_user/complex', $data);

        // Assert
        $this->assertStringContainsString('User List', $result);
        $this->assertStringContainsString('Alice', $result);
        $this->assertStringContainsString('alice@example.com', $result);
        $this->assertStringContainsString('Bob', $result);
        $this->assertStringNotContainsString('No users found', $result);
    }
}
