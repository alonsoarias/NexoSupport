<?php
/**
 * Tests para la clase ThemeAssets
 * @package theme_iser
 */

namespace ISER\Modules\Theme\Iser\Tests;

use PHPUnit\Framework\TestCase;
use ISER\Modules\Theme\Iser\ThemeAssets;

class ThemeAssetsTest extends TestCase
{
    private $assets;

    protected function setUp(): void
    {
        $this->assets = new ThemeAssets();
    }

    public function testAddCss()
    {
        $this->assets->addCss('test.css', [], 10);
        $files = $this->assets->getCssFiles();

        $this->assertIsArray($files);
        $this->assertNotEmpty($files);
    }

    public function testAddJs()
    {
        $this->assets->addJs('test.js', [], true, 10);
        $files = $this->assets->getJsFiles(true);

        $this->assertIsArray($files);
        $this->assertNotEmpty($files);
    }

    public function testAddJsVariable()
    {
        $this->assets->addJsVariable('testVar', 'testValue');

        // No hay getter público, pero podemos verificar que no lance error
        $this->assertTrue(true);
    }

    public function testGetImageUrl()
    {
        $url = $this->assets->getImageUrl('logo.png');

        $this->assertIsString($url);
        $this->assertStringContainsString('logo.png', $url);
    }

    public function testGetFontUrl()
    {
        $url = $this->assets->getFontUrl('montserrat.woff2');

        $this->assertIsString($url);
        $this->assertStringContainsString('montserrat.woff2', $url);
    }

    public function testMinifyCss()
    {
        $css = "
            body {
                margin: 0;
                padding: 0;
            }
        ";

        $minified = $this->assets->minifyCss($css);

        $this->assertIsString($minified);
        $this->assertLessThan(strlen($css), strlen($minified));
        $this->assertStringNotContainsString("\n", $minified);
    }

    public function testMinifyJs()
    {
        $js = "
            function test() {
                console.log('test');
            }
        ";

        $minified = $this->assets->minifyJs($js);

        $this->assertIsString($minified);
        $this->assertLessThan(strlen($js), strlen($minified));
    }

    public function testGetAssetsInfo()
    {
        $this->assets->addCss('test1.css');
        $this->assets->addCss('test2.css');
        $this->assets->addJs('test1.js');

        $info = $this->assets->getAssetsInfo();

        $this->assertIsArray($info);
        $this->assertArrayHasKey('css_count', $info);
        $this->assertArrayHasKey('js_count', $info);
        $this->assertEquals(2, $info['css_count']);
        $this->assertEquals(1, $info['js_count']);
    }
}
