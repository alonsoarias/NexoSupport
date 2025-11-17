<?php
/**
 * LogController Test
 *
 * Comprehensive unit tests for the LogController class
 *
 * @package    ISER\Tests\Unit\Report
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Tests\Unit\Report;

use ISER\Report\Log\LogController;
use ISER\Report\Log\LogRepository;
use ISER\Core\Database\Database;
use ISER\Core\Config\Config;
use ISER\Core\I18n\Translator;
use ISER\Core\View\ViewRenderer;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * LogController test cases
 */
class LogControllerTest extends TestCase
{
    private LogController $controller;
    private Database $mockDb;
    private Config $mockConfig;
    private Translator $mockTranslator;
    private ViewRenderer $mockRenderer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks
        $this->mockDb = $this->createMock(Database::class);
        $this->mockConfig = $this->createMock(Config::class);
        $this->mockTranslator = $this->createMock(Translator::class);
        $this->mockRenderer = $this->createMock(ViewRenderer::class);

        // Set up default translator behavior
        $this->mockTranslator->method('get_string')
            ->willReturnCallback(function($key, $component) {
                return "{$component}:{$key}";
            });

        // Set up default config behavior
        $this->mockConfig->method('get')
            ->willReturnCallback(function($key, $default) {
                if ($key === 'wwwroot') {
                    return 'http://localhost';
                }
                return $default;
            });

        // Create controller
        $this->controller = new LogController(
            $this->mockDb,
            $this->mockConfig,
            $this->mockTranslator,
            $this->mockRenderer
        );
    }

    #[Test]
    public function it_constructs_with_required_dependencies(): void
    {
        // Arrange & Act
        $controller = new LogController(
            $this->mockDb,
            $this->mockConfig,
            $this->mockTranslator,
            $this->mockRenderer
        );

        // Assert
        $this->assertInstanceOf(LogController::class, $controller);
    }

    #[Test]
    public function it_displays_index_page_with_default_parameters(): void
    {
        // Arrange
        $expectedEntries = [
            (object)[
                'id' => 1,
                'username' => 'testuser',
                'action' => 'login',
                'ip_address' => '192.168.1.1',
                'details' => 'User logged in',
                'created_at' => 1234567890
            ]
        ];

        $this->mockDb->method('get_records_sql')
            ->willReturn($expectedEntries);

        $this->mockDb->method('count_records_sql')
            ->willReturn(1);

        $this->mockRenderer->expects($this->once())
            ->method('render')
            ->with(
                'report_log/index',
                $this->callback(function($data) {
                    return isset($data['entries'])
                        && isset($data['pagination'])
                        && isset($data['filters']);
                })
            )
            ->willReturn('<html>Index Page</html>');

        // Act
        $result = $this->controller->index();

        // Assert
        $this->assertEquals('<html>Index Page</html>', $result);
    }

    #[Test]
    public function it_applies_user_id_filter(): void
    {
        // Arrange
        $params = ['userid' => 5];

        $this->mockDb->expects($this->once())
            ->method('get_records_sql')
            ->with(
                $this->stringContains('AND l.user_id = ?'),
                $this->anything()
            )
            ->willReturn([]);

        $this->mockDb->method('count_records_sql')
            ->willReturn(0);

        $this->mockRenderer->method('render')
            ->willReturn('');

        // Act
        $this->controller->index($params);

        // Assert - verified by mock expectations
        $this->assertTrue(true);
    }

    #[Test]
    public function it_applies_action_filter(): void
    {
        // Arrange
        $params = ['action' => 'login'];

        $this->mockDb->expects($this->once())
            ->method('get_records_sql')
            ->with(
                $this->stringContains('AND l.action LIKE ?'),
                $this->anything()
            )
            ->willReturn([]);

        $this->mockDb->method('count_records_sql')
            ->willReturn(0);

        $this->mockRenderer->method('render')
            ->willReturn('');

        // Act
        $this->controller->index($params);

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    public function it_applies_date_from_filter(): void
    {
        // Arrange
        $params = ['datefrom' => 1234567890];

        $this->mockDb->method('get_records_sql')
            ->willReturn([]);

        $this->mockDb->method('count_records_sql')
            ->willReturn(0);

        $this->mockRenderer->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function($data) {
                    return $data['filters']['datefrom'] === date('Y-m-d', 1234567890);
                })
            )
            ->willReturn('');

        // Act
        $this->controller->index($params);

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    public function it_applies_date_to_filter(): void
    {
        // Arrange
        $params = ['dateto' => 1234567999];

        $this->mockDb->method('get_records_sql')
            ->willReturn([]);

        $this->mockDb->method('count_records_sql')
            ->willReturn(0);

        $this->mockRenderer->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function($data) {
                    return $data['filters']['dateto'] === date('Y-m-d', 1234567999);
                })
            )
            ->willReturn('');

        // Act
        $this->controller->index($params);

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    public function it_formats_entries_for_display(): void
    {
        // Arrange
        $dbEntries = [
            (object)[
                'id' => 1,
                'username' => 'testuser',
                'action' => 'login',
                'ip_address' => '192.168.1.1',
                'details' => 'User logged in successfully',
                'created_at' => 1234567890
            ]
        ];

        $this->mockDb->method('get_records_sql')
            ->willReturn($dbEntries);

        $this->mockDb->method('count_records_sql')
            ->willReturn(1);

        $this->mockRenderer->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function($data) {
                    $entry = $data['entries'][0];
                    return $entry['id'] === 1
                        && $entry['username'] === 'testuser'
                        && $entry['action'] === 'login'
                        && $entry['ip_address'] === '192.168.1.1'
                        && isset($entry['created_at']);
                })
            )
            ->willReturn('');

        // Act
        $this->controller->index();

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    public function it_handles_missing_username_in_entry(): void
    {
        // Arrange
        $dbEntries = [
            (object)[
                'id' => 1,
                'username' => null,
                'action' => 'test',
                'created_at' => 1234567890
            ]
        ];

        $this->mockDb->method('get_records_sql')
            ->willReturn($dbEntries);

        $this->mockDb->method('count_records_sql')
            ->willReturn(1);

        $this->mockRenderer->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function($data) {
                    return $data['entries'][0]['username'] === 'report_log:unknown';
                })
            )
            ->willReturn('');

        // Act
        $this->controller->index();

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    public function it_truncates_long_details(): void
    {
        // Arrange
        $longDetails = str_repeat('x', 200);
        $dbEntries = [
            (object)[
                'id' => 1,
                'username' => 'test',
                'action' => 'test',
                'details' => $longDetails,
                'created_at' => 1234567890
            ]
        ];

        $this->mockDb->method('get_records_sql')
            ->willReturn($dbEntries);

        $this->mockDb->method('count_records_sql')
            ->willReturn(1);

        $this->mockRenderer->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function($data) {
                    return strlen($data['entries'][0]['details']) === 100;
                })
            )
            ->willReturn('');

        // Act
        $this->controller->index();

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    public function it_escapes_html_in_action_field(): void
    {
        // Arrange
        $dbEntries = [
            (object)[
                'id' => 1,
                'username' => 'test',
                'action' => '<script>alert("XSS")</script>',
                'details' => '',
                'created_at' => 1234567890
            ]
        ];

        $this->mockDb->method('get_records_sql')
            ->willReturn($dbEntries);

        $this->mockDb->method('count_records_sql')
            ->willReturn(1);

        $this->mockRenderer->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function($data) {
                    $action = $data['entries'][0]['action'];
                    return !str_contains($action, '<script>')
                        && str_contains($action, '&lt;script&gt;');
                })
            )
            ->willReturn('');

        // Act
        $this->controller->index();

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    public function it_calculates_pagination_correctly(): void
    {
        // Arrange
        $this->mockDb->method('get_records_sql')
            ->willReturn([]);

        $this->mockDb->method('count_records_sql')
            ->willReturn(150); // 3 pages with perpage=50

        $this->mockRenderer->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function($data) {
                    $pagination = $data['pagination'];
                    return $pagination['total'] === 3
                        && $pagination['current'] === 0
                        && $pagination['perpage'] === 50
                        && $pagination['has_prev'] === false
                        && $pagination['has_next'] === true;
                })
            )
            ->willReturn('');

        // Act
        $this->controller->index(['page' => 0]);

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    public function it_builds_pagination_urls_with_filters(): void
    {
        // Arrange
        $params = [
            'page' => 1,
            'userid' => 5,
            'action' => 'login'
        ];

        $this->mockDb->method('get_records_sql')
            ->willReturn([]);

        $this->mockDb->method('count_records_sql')
            ->willReturn(100);

        $this->mockRenderer->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function($data) {
                    $pagination = $data['pagination'];
                    return str_contains($pagination['prev_url'], 'userid=5')
                        && str_contains($pagination['prev_url'], 'action=login')
                        && str_contains($pagination['next_url'], 'userid=5')
                        && str_contains($pagination['next_url'], 'action=login');
                })
            )
            ->willReturn('');

        // Act
        $this->controller->index($params);

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    public function it_sets_has_entries_flag_correctly(): void
    {
        // Arrange
        $this->mockDb->method('get_records_sql')
            ->willReturn([]);

        $this->mockDb->method('count_records_sql')
            ->willReturn(0);

        $this->mockRenderer->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function($data) {
                    return $data['has_entries'] === false;
                })
            )
            ->willReturn('');

        // Act
        $this->controller->index();

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    public function it_builds_export_url_correctly(): void
    {
        // Arrange
        $this->mockDb->method('get_records_sql')
            ->willReturn([]);

        $this->mockDb->method('count_records_sql')
            ->willReturn(0);

        $this->mockRenderer->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function($data) {
                    return str_contains($data['export_url'], 'report/log/export.php');
                })
            )
            ->willReturn('');

        // Act
        $this->controller->index();

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    public function it_includes_all_required_strings_in_template_data(): void
    {
        // Arrange
        $this->mockDb->method('get_records_sql')
            ->willReturn([]);

        $this->mockDb->method('count_records_sql')
            ->willReturn(0);

        $requiredStrings = [
            'user', 'allusers', 'action', 'allactions', 'from', 'to',
            'filter', 'nologs', 'id', 'ipaddress', 'details', 'date',
            'exportcsv', 'previous', 'next'
        ];

        $this->mockRenderer->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function($data) use ($requiredStrings) {
                    foreach ($requiredStrings as $key) {
                        if (!isset($data['strings'][$key])) {
                            return false;
                        }
                    }
                    return true;
                })
            )
            ->willReturn('');

        // Act
        $this->controller->index();

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    #[DataProvider('paginationDataProvider')]
    public function it_handles_various_pagination_scenarios(
        int $page,
        int $perpage,
        int $totalCount,
        bool $expectedHasPrev,
        bool $expectedHasNext
    ): void {
        // Arrange
        $this->mockDb->method('get_records_sql')
            ->willReturn([]);

        $this->mockDb->method('count_records_sql')
            ->willReturn($totalCount);

        $this->mockRenderer->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function($data) use ($expectedHasPrev, $expectedHasNext) {
                    return $data['pagination']['has_prev'] === $expectedHasPrev
                        && $data['pagination']['has_next'] === $expectedHasNext;
                })
            )
            ->willReturn('');

        // Act
        $this->controller->index(['page' => $page, 'perpage' => $perpage]);

        // Assert
        $this->assertTrue(true);
    }

    /**
     * Data provider for pagination scenarios
     */
    public static function paginationDataProvider(): array
    {
        return [
            'first page with more' => [0, 50, 100, false, true],
            'middle page' => [1, 50, 150, true, true],
            'last page' => [2, 50, 150, true, false],
            'only page' => [0, 50, 30, false, false],
            'custom perpage first' => [0, 25, 100, false, true],
            'custom perpage last' => [3, 25, 100, true, false],
        ];
    }

    #[Test]
    public function it_ignores_zero_userid_filter(): void
    {
        // Arrange
        $params = ['userid' => 0];

        $this->mockDb->expects($this->once())
            ->method('get_records_sql')
            ->with(
                $this->logicalNot($this->stringContains('AND l.user_id')),
                $this->anything()
            )
            ->willReturn([]);

        $this->mockDb->method('count_records_sql')
            ->willReturn(0);

        $this->mockRenderer->method('render')
            ->willReturn('');

        // Act
        $this->controller->index($params);

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    public function it_ignores_empty_action_filter(): void
    {
        // Arrange
        $params = ['action' => ''];

        $this->mockDb->expects($this->once())
            ->method('get_records_sql')
            ->with(
                $this->logicalNot($this->stringContains('AND l.action')),
                $this->anything()
            )
            ->willReturn([]);

        $this->mockDb->method('count_records_sql')
            ->willReturn(0);

        $this->mockRenderer->method('render')
            ->willReturn('');

        // Act
        $this->controller->index($params);

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    public function it_handles_missing_ip_address(): void
    {
        // Arrange
        $dbEntries = [
            (object)[
                'id' => 1,
                'username' => 'test',
                'action' => 'test',
                'ip_address' => null,
                'created_at' => 1234567890
            ]
        ];

        $this->mockDb->method('get_records_sql')
            ->willReturn($dbEntries);

        $this->mockDb->method('count_records_sql')
            ->willReturn(1);

        $this->mockRenderer->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function($data) {
                    return $data['entries'][0]['ip_address'] === 'N/A';
                })
            )
            ->willReturn('');

        // Act
        $this->controller->index();

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    public function it_handles_empty_details(): void
    {
        // Arrange
        $dbEntries = [
            (object)[
                'id' => 1,
                'username' => 'test',
                'action' => 'test',
                'details' => null,
                'created_at' => 1234567890
            ]
        ];

        $this->mockDb->method('get_records_sql')
            ->willReturn($dbEntries);

        $this->mockDb->method('count_records_sql')
            ->willReturn(1);

        $this->mockRenderer->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function($data) {
                    return $data['entries'][0]['details'] === '';
                })
            )
            ->willReturn('');

        // Act
        $this->controller->index();

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    public function it_formats_created_at_timestamp_correctly(): void
    {
        // Arrange
        $timestamp = 1234567890;
        $expectedDate = date('Y-m-d H:i:s', $timestamp);

        $dbEntries = [
            (object)[
                'id' => 1,
                'username' => 'test',
                'action' => 'test',
                'created_at' => $timestamp
            ]
        ];

        $this->mockDb->method('get_records_sql')
            ->willReturn($dbEntries);

        $this->mockDb->method('count_records_sql')
            ->willReturn(1);

        $this->mockRenderer->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function($data) use ($expectedDate) {
                    return $data['entries'][0]['created_at'] === $expectedDate;
                })
            )
            ->willReturn('');

        // Act
        $this->controller->index();

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    public function it_uses_correct_template_name(): void
    {
        // Arrange
        $this->mockDb->method('get_records_sql')
            ->willReturn([]);

        $this->mockDb->method('count_records_sql')
            ->willReturn(0);

        $this->mockRenderer->expects($this->once())
            ->method('render')
            ->with('report_log/index', $this->anything())
            ->willReturn('');

        // Act
        $this->controller->index();

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    public function it_displays_pagination_as_one_indexed(): void
    {
        // Arrange
        $this->mockDb->method('get_records_sql')
            ->willReturn([]);

        $this->mockDb->method('count_records_sql')
            ->willReturn(100);

        $this->mockRenderer->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function($data) {
                    // current is 0-indexed, current_display should be 1-indexed
                    return $data['pagination']['current'] === 0
                        && $data['pagination']['current_display'] === 1;
                })
            )
            ->willReturn('');

        // Act
        $this->controller->index(['page' => 0]);

        // Assert
        $this->assertTrue(true);
    }
}
