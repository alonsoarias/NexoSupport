<?php
/**
 * Tests para la clase ThemeRenderer
 * @package theme_iser
 */

namespace ISER\Modules\Theme\Iser\Tests;

use PHPUnit\Framework\TestCase;
use ISER\Modules\Theme\Iser\ThemeRenderer;

class ThemeRendererTest extends TestCase
{
    private $renderer;

    protected function setUp(): void
    {
        $this->renderer = new ThemeRenderer();
    }

    public function testRenderAlert()
    {
        $html = $this->renderer->bsAlert('success', 'Test message', true);

        $this->assertStringContainsString('alert', $html);
        $this->assertStringContainsString('alert-success', $html);
        $this->assertStringContainsString('Test message', $html);
        $this->assertStringContainsString('btn-close', $html);
    }

    public function testRenderBadge()
    {
        $html = $this->renderer->bsBadge('Test', 'primary', false);

        $this->assertStringContainsString('badge', $html);
        $this->assertStringContainsString('bg-primary', $html);
        $this->assertStringContainsString('Test', $html);
    }

    public function testRenderButton()
    {
        $html = $this->renderer->bsButton('Click Me', 'primary', 'md');

        $this->assertStringContainsString('btn', $html);
        $this->assertStringContainsString('btn-primary', $html);
        $this->assertStringContainsString('Click Me', $html);
    }

    public function testRenderSpinner()
    {
        $html = $this->renderer->bsSpinner('border', 'primary', '');

        $this->assertStringContainsString('spinner-border', $html);
        $this->assertStringContainsString('text-primary', $html);
    }

    public function testFormInput()
    {
        $html = $this->renderer->formInput('username', 'Username', 'john', [
            'required' => true,
            'placeholder' => 'Enter username'
        ]);

        $this->assertStringContainsString('form-control', $html);
        $this->assertStringContainsString('username', $html);
        $this->assertStringContainsString('Username', $html);
        $this->assertStringContainsString('john', $html);
        $this->assertStringContainsString('required', $html);
    }

    public function testFormSelect()
    {
        $options = [
            '1' => 'Option 1',
            '2' => 'Option 2',
            '3' => 'Option 3'
        ];

        $html = $this->renderer->formSelect('choice', 'Choose', $options, '2');

        $this->assertStringContainsString('form-select', $html);
        $this->assertStringContainsString('Option 1', $html);
        $this->assertStringContainsString('Option 2', $html);
        $this->assertStringContainsString('selected', $html);
    }

    public function testFormCheckbox()
    {
        $html = $this->renderer->formCheckbox('agree', 'I agree', true);

        $this->assertStringContainsString('form-check-input', $html);
        $this->assertStringContainsString('agree', $html);
        $this->assertStringContainsString('I agree', $html);
        $this->assertStringContainsString('checked', $html);
    }

    public function testBreadcrumb()
    {
        $items = [
            ['text' => 'Home', 'url' => '/'],
            ['text' => 'Users', 'url' => '/users'],
            ['text' => 'Profile', 'url' => null]
        ];

        $html = $this->renderer->bsBreadcrumb($items);

        $this->assertStringContainsString('breadcrumb', $html);
        $this->assertStringContainsString('Home', $html);
        $this->assertStringContainsString('Users', $html);
        $this->assertStringContainsString('Profile', $html);
        $this->assertStringContainsString('active', $html);
    }

    public function testPagination()
    {
        $html = $this->renderer->bsPagination(3, 10, '/users');

        $this->assertStringContainsString('pagination', $html);
        $this->assertStringContainsString('page-item', $html);
        $this->assertStringContainsString('active', $html);
    }
}
