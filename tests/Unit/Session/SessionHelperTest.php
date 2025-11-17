<?php
/**
 * SessionHelper Test
 *
 * Comprehensive unit tests for the SessionHelper class
 *
 * @package    ISER\Tests\Unit\Session
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Tests\Unit\Session;

use ISER\Core\Session\SessionHelper;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * SessionHelper test cases
 */
class SessionHelperTest extends TestCase
{
    private SessionHelper $helper;
    private array $sessionBackup = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Backup current session data
        if (isset($_SESSION)) {
            $this->sessionBackup = $_SESSION;
        }

        // Clear session for clean test
        $_SESSION = [];

        // Reset singleton instance using reflection
        $reflection = new \ReflectionClass(SessionHelper::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);

        $this->helper = SessionHelper::getInstance();
    }

    protected function tearDown(): void
    {
        // Restore session data
        $_SESSION = $this->sessionBackup;

        parent::tearDown();
    }

    #[Test]
    public function it_implements_singleton_pattern(): void
    {
        // Arrange & Act
        $instance1 = SessionHelper::getInstance();
        $instance2 = SessionHelper::getInstance();

        // Assert
        $this->assertInstanceOf(SessionHelper::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }

    #[Test]
    public function it_returns_false_when_user_not_logged_in(): void
    {
        // Arrange - ensure no user_id in session
        unset($_SESSION['user_id']);

        // Act
        $isLoggedIn = $this->helper->isLoggedIn();

        // Assert
        $this->assertFalse($isLoggedIn);
    }

    #[Test]
    public function it_returns_true_when_user_is_logged_in(): void
    {
        // Arrange
        $_SESSION['user_id'] = 42;

        // Act
        $isLoggedIn = $this->helper->isLoggedIn();

        // Assert
        $this->assertTrue($isLoggedIn);
    }

    #[Test]
    public function it_returns_false_when_user_id_is_zero(): void
    {
        // Arrange
        $_SESSION['user_id'] = 0;

        // Act
        $isLoggedIn = $this->helper->isLoggedIn();

        // Assert
        $this->assertFalse($isLoggedIn);
    }

    #[Test]
    public function it_returns_false_when_user_id_is_negative(): void
    {
        // Arrange
        $_SESSION['user_id'] = -1;

        // Act
        $isLoggedIn = $this->helper->isLoggedIn();

        // Assert
        $this->assertFalse($isLoggedIn);
    }

    #[Test]
    public function it_gets_current_user_id_when_logged_in(): void
    {
        // Arrange
        $_SESSION['user_id'] = 123;

        // Act
        $userId = $this->helper->getCurrentUserId();

        // Assert
        $this->assertEquals(123, $userId);
        $this->assertIsInt($userId);
    }

    #[Test]
    public function it_returns_zero_when_no_user_id_in_session(): void
    {
        // Arrange
        unset($_SESSION['user_id']);

        // Act
        $userId = $this->helper->getCurrentUserId();

        // Assert
        $this->assertEquals(0, $userId);
    }

    #[Test]
    public function it_sets_user_id_in_session(): void
    {
        // Arrange
        $userId = 456;

        // Act
        $this->helper->setUserId($userId);

        // Assert
        $this->assertEquals($userId, $_SESSION['user_id']);
    }

    #[Test]
    public function it_throws_exception_when_login_required_and_not_logged_in(): void
    {
        // Arrange
        unset($_SESSION['user_id']);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Login required');

        // Act
        $this->helper->requireLogin();
    }

    #[Test]
    public function it_does_not_throw_when_login_required_and_logged_in(): void
    {
        // Arrange
        $_SESSION['user_id'] = 789;

        // Act & Assert - should not throw
        $this->helper->requireLogin();
        $this->assertTrue(true); // If we get here, test passed
    }

    #[Test]
    public function it_gets_current_user_data(): void
    {
        // Arrange
        $_SESSION['user_id'] = 1;
        $_SESSION['user_data'] = [
            'id' => 1,
            'username' => 'testuser',
            'email' => 'test@example.com'
        ];

        // Act
        $userData = $this->helper->getCurrentUser();

        // Assert
        $this->assertIsArray($userData);
        $this->assertEquals('testuser', $userData['username']);
        $this->assertEquals('test@example.com', $userData['email']);
    }

    #[Test]
    public function it_returns_null_when_getting_user_data_and_not_logged_in(): void
    {
        // Arrange
        unset($_SESSION['user_id']);

        // Act
        $userData = $this->helper->getCurrentUser();

        // Assert
        $this->assertNull($userData);
    }

    #[Test]
    public function it_returns_null_when_user_data_not_set(): void
    {
        // Arrange
        $_SESSION['user_id'] = 1;
        unset($_SESSION['user_data']);

        // Act
        $userData = $this->helper->getCurrentUser();

        // Assert
        $this->assertNull($userData);
    }

    #[Test]
    public function it_sets_current_user_data(): void
    {
        // Arrange
        $userData = [
            'id' => 99,
            'username' => 'newuser',
            'email' => 'new@example.com'
        ];

        // Act
        $this->helper->setCurrentUser($userData);

        // Assert
        $this->assertEquals($userData, $_SESSION['user_data']);
        $this->assertEquals(99, $_SESSION['user_id']);
    }

    #[Test]
    public function it_sets_current_user_data_without_id_field(): void
    {
        // Arrange
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com'
        ];

        // Act
        $this->helper->setCurrentUser($userData);

        // Assert
        $this->assertEquals($userData, $_SESSION['user_data']);
        $this->assertArrayNotHasKey('user_id', $_SESSION);
    }

    #[Test]
    public function it_gets_session_value(): void
    {
        // Arrange
        $_SESSION['test_key'] = 'test_value';

        // Act
        $value = $this->helper->get('test_key');

        // Assert
        $this->assertEquals('test_value', $value);
    }

    #[Test]
    public function it_returns_default_when_session_key_not_found(): void
    {
        // Arrange
        $default = 'default_value';

        // Act
        $value = $this->helper->get('nonexistent_key', $default);

        // Assert
        $this->assertEquals($default, $value);
    }

    #[Test]
    public function it_returns_null_when_no_default_provided(): void
    {
        // Arrange & Act
        $value = $this->helper->get('nonexistent_key');

        // Assert
        $this->assertNull($value);
    }

    #[Test]
    public function it_sets_session_value(): void
    {
        // Arrange
        $key = 'custom_key';
        $value = 'custom_value';

        // Act
        $this->helper->set($key, $value);

        // Assert
        $this->assertEquals($value, $_SESSION[$key]);
    }

    #[Test]
    public function it_checks_if_session_has_key(): void
    {
        // Arrange
        $_SESSION['existing_key'] = 'value';

        // Act & Assert
        $this->assertTrue($this->helper->has('existing_key'));
        $this->assertFalse($this->helper->has('nonexistent_key'));
    }

    #[Test]
    public function it_removes_session_value(): void
    {
        // Arrange
        $_SESSION['key_to_remove'] = 'value';
        $this->assertTrue(isset($_SESSION['key_to_remove']));

        // Act
        $this->helper->remove('key_to_remove');

        // Assert
        $this->assertFalse(isset($_SESSION['key_to_remove']));
    }

    #[Test]
    public function it_sets_flash_message(): void
    {
        // Arrange
        $key = 'success';
        $message = 'Operation completed successfully';

        // Act
        $this->helper->setFlash($key, $message);

        // Assert
        $this->assertEquals($message, $_SESSION["_flash_{$key}"]);
    }

    #[Test]
    public function it_gets_flash_message_and_removes_it(): void
    {
        // Arrange
        $key = 'error';
        $message = 'An error occurred';
        $_SESSION["_flash_{$key}"] = $message;

        // Act
        $retrievedMessage = $this->helper->getFlash($key);

        // Assert
        $this->assertEquals($message, $retrievedMessage);
        $this->assertArrayNotHasKey("_flash_{$key}", $_SESSION);
    }

    #[Test]
    public function it_returns_default_when_flash_not_found(): void
    {
        // Arrange
        $default = 'default message';

        // Act
        $message = $this->helper->getFlash('nonexistent', $default);

        // Assert
        $this->assertEquals($default, $message);
    }

    #[Test]
    public function it_gets_all_session_data(): void
    {
        // Arrange
        $_SESSION = [
            'key1' => 'value1',
            'key2' => 'value2',
            'user_id' => 123
        ];

        // Act
        $allData = $this->helper->all();

        // Assert
        $this->assertIsArray($allData);
        $this->assertEquals($_SESSION, $allData);
        $this->assertCount(3, $allData);
    }

    #[Test]
    #[DataProvider('userIdProvider')]
    public function it_validates_various_user_id_values(mixed $userId, bool $expectedLoggedIn): void
    {
        // Arrange
        $_SESSION['user_id'] = $userId;

        // Act
        $isLoggedIn = $this->helper->isLoggedIn();

        // Assert
        $this->assertEquals($expectedLoggedIn, $isLoggedIn);
    }

    /**
     * Data provider for user ID testing
     */
    public static function userIdProvider(): array
    {
        return [
            'positive integer' => [1, true],
            'large integer' => [999999, true],
            'zero' => [0, false],
            'negative' => [-1, false],
            'string number' => ['123', true],
            'string zero' => ['0', false],
        ];
    }

    #[Test]
    public function it_handles_array_session_values(): void
    {
        // Arrange
        $arrayValue = ['item1', 'item2', 'item3'];

        // Act
        $this->helper->set('array_key', $arrayValue);
        $retrieved = $this->helper->get('array_key');

        // Assert
        $this->assertIsArray($retrieved);
        $this->assertEquals($arrayValue, $retrieved);
    }

    #[Test]
    public function it_handles_object_session_values(): void
    {
        // Arrange
        $objectValue = (object)['property' => 'value'];

        // Act
        $this->helper->set('object_key', $objectValue);
        $retrieved = $this->helper->get('object_key');

        // Assert
        $this->assertIsObject($retrieved);
        $this->assertEquals('value', $retrieved->property);
    }

    #[Test]
    public function it_overwrites_existing_session_value(): void
    {
        // Arrange
        $key = 'test_key';
        $this->helper->set($key, 'initial_value');

        // Act
        $this->helper->set($key, 'new_value');

        // Assert
        $this->assertEquals('new_value', $this->helper->get($key));
    }

    #[Test]
    public function it_handles_multiple_flash_messages(): void
    {
        // Arrange
        $this->helper->setFlash('success', 'Success message');
        $this->helper->setFlash('error', 'Error message');
        $this->helper->setFlash('info', 'Info message');

        // Act
        $success = $this->helper->getFlash('success');
        $error = $this->helper->getFlash('error');
        $info = $this->helper->getFlash('info');

        // Assert
        $this->assertEquals('Success message', $success);
        $this->assertEquals('Error message', $error);
        $this->assertEquals('Info message', $info);

        // Verify all flash messages were removed
        $this->assertArrayNotHasKey('_flash_success', $_SESSION);
        $this->assertArrayNotHasKey('_flash_error', $_SESSION);
        $this->assertArrayNotHasKey('_flash_info', $_SESSION);
    }

    #[Test]
    public function it_handles_boolean_session_values(): void
    {
        // Arrange & Act
        $this->helper->set('bool_true', true);
        $this->helper->set('bool_false', false);

        // Assert
        $this->assertTrue($this->helper->get('bool_true'));
        $this->assertFalse($this->helper->get('bool_false'));
    }

    #[Test]
    public function it_converts_string_user_id_to_integer(): void
    {
        // Arrange
        $_SESSION['user_id'] = '456';

        // Act
        $userId = $this->helper->getCurrentUserId();

        // Assert
        $this->assertIsInt($userId);
        $this->assertEquals(456, $userId);
    }

    #[Test]
    public function it_returns_empty_array_when_session_is_empty(): void
    {
        // Arrange
        $_SESSION = [];

        // Act
        $allData = $this->helper->all();

        // Assert
        $this->assertIsArray($allData);
        $this->assertEmpty($allData);
    }

    #[Test]
    public function it_handles_null_session_values(): void
    {
        // Arrange
        $this->helper->set('null_key', null);

        // Act
        $value = $this->helper->get('null_key');

        // Assert
        $this->assertNull($value);
        $this->assertTrue($this->helper->has('null_key'));
    }

    #[Test]
    public function it_removes_nonexistent_key_without_error(): void
    {
        // Arrange & Act
        $this->helper->remove('nonexistent_key');

        // Assert - should not throw exception
        $this->assertFalse($this->helper->has('nonexistent_key'));
    }

    #[Test]
    public function it_preserves_user_id_when_setting_user_data_with_id(): void
    {
        // Arrange
        $_SESSION['user_id'] = 100;
        $userData = [
            'id' => 200,
            'username' => 'test'
        ];

        // Act
        $this->helper->setCurrentUser($userData);

        // Assert - should update user_id to match user_data id
        $this->assertEquals(200, $_SESSION['user_id']);
    }
}
