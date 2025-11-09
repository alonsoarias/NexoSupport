<?php

/**
 * ISER Authentication System - Helpers Unit Tests
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

use ISER\Core\Utils\Helpers;
use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function testEscape(): void
    {
        $input = '<script>alert("XSS")</script>';
        $output = Helpers::escape($input);

        $this->assertStringContainsString('&lt;script&gt;', $output);
        $this->assertStringNotContainsString('<script>', $output);
    }

    public function testRandomString(): void
    {
        $length = 32;
        $random = Helpers::randomString($length);

        $this->assertEquals($length, strlen($random));
    }

    public function testGenerateToken(): void
    {
        $token = Helpers::generateToken(16);

        $this->assertEquals(32, strlen($token)); // hex output is 2x length
    }

    public function testHashPassword(): void
    {
        $password = 'testPassword123';
        $hash = Helpers::hashPassword($password);

        $this->assertNotEquals($password, $hash);
        $this->assertTrue(password_verify($password, $hash));
    }

    public function testVerifyPassword(): void
    {
        $password = 'testPassword123';
        $hash = Helpers::hashPassword($password);

        $this->assertTrue(Helpers::verifyPassword($password, $hash));
        $this->assertFalse(Helpers::verifyPassword('wrongPassword', $hash));
    }

    public function testValidateEmail(): void
    {
        $this->assertTrue(Helpers::validateEmail('test@example.com'));
        $this->assertTrue(Helpers::validateEmail('user.name+tag@example.co.uk'));
        $this->assertFalse(Helpers::validateEmail('invalid-email'));
        $this->assertFalse(Helpers::validateEmail('test@'));
    }

    public function testValidateUrl(): void
    {
        $this->assertTrue(Helpers::validateUrl('https://example.com'));
        $this->assertTrue(Helpers::validateUrl('http://example.com/path'));
        $this->assertFalse(Helpers::validateUrl('not-a-url'));
        $this->assertFalse(Helpers::validateUrl('ftp://example.com'));
    }

    public function testUuid(): void
    {
        $uuid = Helpers::uuid();

        $this->assertEquals(36, strlen($uuid));
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid
        );
    }

    public function testFormatBytes(): void
    {
        $this->assertEquals('0 B', Helpers::formatBytes(0));
        $this->assertEquals('1 KB', Helpers::formatBytes(1024));
        $this->assertEquals('1 MB', Helpers::formatBytes(1048576));
        $this->assertEquals('1 GB', Helpers::formatBytes(1073741824));
    }

    public function testSlug(): void
    {
        $this->assertEquals('hello-world', Helpers::slug('Hello World'));
        $this->assertEquals('test-123', Helpers::slug('Test 123'));
        $this->assertEquals('special-chars', Helpers::slug('Special!@#$%Chars'));
    }

    public function testTruncate(): void
    {
        $text = 'This is a long text that needs to be truncated';

        $truncated = Helpers::truncate($text, 20);
        $this->assertEquals(20, strlen($truncated));
        $this->assertStringEndsWith('...', $truncated);

        $notTruncated = Helpers::truncate('Short', 20);
        $this->assertEquals('Short', $notTruncated);
    }

    public function testParseJson(): void
    {
        $json = '{"key": "value", "number": 123}';
        $parsed = Helpers::parseJson($json);

        $this->assertIsArray($parsed);
        $this->assertEquals('value', $parsed['key']);
        $this->assertEquals(123, $parsed['number']);

        $invalid = Helpers::parseJson('invalid json');
        $this->assertNull($invalid);
    }

    public function testToJson(): void
    {
        $data = ['key' => 'value', 'number' => 123];
        $json = Helpers::toJson($data);

        $this->assertIsString($json);
        $this->assertStringContainsString('"key"', $json);
        $this->assertStringContainsString('"value"', $json);
    }

    public function testArrayGet(): void
    {
        $array = [
            'user' => [
                'name' => 'John',
                'email' => 'john@example.com',
            ],
        ];

        $this->assertEquals('John', Helpers::arrayGet($array, 'user.name'));
        $this->assertEquals('john@example.com', Helpers::arrayGet($array, 'user.email'));
        $this->assertEquals('default', Helpers::arrayGet($array, 'user.phone', 'default'));
    }

    public function testIsEmpty(): void
    {
        $this->assertTrue(Helpers::isEmpty(null));
        $this->assertTrue(Helpers::isEmpty(''));
        $this->assertTrue(Helpers::isEmpty([]));
        $this->assertTrue(Helpers::isEmpty(false));

        $this->assertFalse(Helpers::isEmpty('text'));
        $this->assertFalse(Helpers::isEmpty(0));
        $this->assertFalse(Helpers::isEmpty(['item']));
    }

    public function testTimestampMs(): void
    {
        $timestamp = Helpers::timestampMs();

        $this->assertIsInt($timestamp);
        $this->assertGreaterThan(0, $timestamp);
    }

    public function testFormatDate(): void
    {
        $timestamp = strtotime('2024-01-15 12:30:45');
        $formatted = Helpers::formatDate($timestamp, 'Y-m-d');

        $this->assertEquals('2024-01-15', $formatted);
    }
}
