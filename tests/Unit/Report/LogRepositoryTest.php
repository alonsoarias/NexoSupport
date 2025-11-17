<?php
/**
 * LogRepository Test
 *
 * Comprehensive unit tests for the LogRepository class
 *
 * @package    ISER\Tests\Unit\Report
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Tests\Unit\Report;

use ISER\Report\Log\LogRepository;
use ISER\Core\Database\Database;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * LogRepository test cases
 */
class LogRepositoryTest extends TestCase
{
    private LogRepository $repository;
    private Database $mockDb;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock database
        $this->mockDb = $this->createMock(Database::class);

        // Create repository with mocked database
        $this->repository = new LogRepository($this->mockDb);
    }

    #[Test]
    public function it_constructs_with_database_dependency(): void
    {
        // Arrange & Act
        $repository = new LogRepository($this->mockDb);

        // Assert
        $this->assertInstanceOf(LogRepository::class, $repository);
    }

    #[Test]
    public function it_gets_entries_without_filters(): void
    {
        // Arrange
        $expectedRecords = [
            (object)[
                'id' => 1,
                'user_id' => 5,
                'action' => 'login',
                'ip_address' => '192.168.1.1',
                'details' => 'User logged in',
                'created_at' => 1234567890,
                'username' => 'john_doe'
            ],
            (object)[
                'id' => 2,
                'user_id' => 3,
                'action' => 'logout',
                'ip_address' => '192.168.1.2',
                'details' => 'User logged out',
                'created_at' => 1234567891,
                'username' => 'jane_doe'
            ]
        ];

        $this->mockDb->expects($this->once())
            ->method('get_records_sql')
            ->with(
                $this->stringContains('SELECT l.*, u.username FROM logs l'),
                $this->callback(function($params) {
                    // Should have LIMIT and OFFSET at the end
                    return count($params) === 2 && $params[0] === 50 && $params[1] === 0;
                })
            )
            ->willReturn($expectedRecords);

        // Act
        $result = $this->repository->get_entries();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals('john_doe', $result[0]->username);
    }

    #[Test]
    public function it_gets_entries_with_user_id_filter(): void
    {
        // Arrange
        $filters = ['user_id' => 5];
        $expectedRecords = [
            (object)[
                'id' => 1,
                'user_id' => 5,
                'action' => 'login',
                'username' => 'john_doe',
                'created_at' => 1234567890
            ]
        ];

        $this->mockDb->expects($this->once())
            ->method('get_records_sql')
            ->with(
                $this->stringContains('AND l.user_id = ?'),
                $this->callback(function($params) {
                    return $params[0] === 5 && $params[1] === 50 && $params[2] === 0;
                })
            )
            ->willReturn($expectedRecords);

        // Act
        $result = $this->repository->get_entries($filters);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(5, $result[0]->user_id);
    }

    #[Test]
    public function it_gets_entries_with_action_filter(): void
    {
        // Arrange
        $filters = ['action' => 'login'];
        $expectedRecords = [];

        $this->mockDb->expects($this->once())
            ->method('get_records_sql')
            ->with(
                $this->stringContains('AND l.action LIKE ?'),
                $this->callback(function($params) {
                    return $params[0] === '%login%';
                })
            )
            ->willReturn($expectedRecords);

        // Act
        $result = $this->repository->get_entries($filters);

        // Assert
        $this->assertIsArray($result);
    }

    #[Test]
    public function it_gets_entries_with_date_from_filter(): void
    {
        // Arrange
        $filters = ['date_from' => 1234567890];
        $expectedRecords = [];

        $this->mockDb->expects($this->once())
            ->method('get_records_sql')
            ->with(
                $this->stringContains('AND l.created_at >= ?'),
                $this->callback(function($params) {
                    return $params[0] === 1234567890;
                })
            )
            ->willReturn($expectedRecords);

        // Act
        $result = $this->repository->get_entries($filters);

        // Assert
        $this->assertIsArray($result);
    }

    #[Test]
    public function it_gets_entries_with_date_to_filter(): void
    {
        // Arrange
        $filters = ['date_to' => 1234567999];
        $expectedRecords = [];

        $this->mockDb->expects($this->once())
            ->method('get_records_sql')
            ->with(
                $this->stringContains('AND l.created_at <= ?'),
                $this->callback(function($params) {
                    return $params[0] === 1234567999;
                })
            )
            ->willReturn($expectedRecords);

        // Act
        $result = $this->repository->get_entries($filters);

        // Assert
        $this->assertIsArray($result);
    }

    #[Test]
    public function it_gets_entries_with_multiple_filters(): void
    {
        // Arrange
        $filters = [
            'user_id' => 5,
            'action' => 'login',
            'date_from' => 1234567890,
            'date_to' => 1234567999
        ];
        $expectedRecords = [];

        $this->mockDb->expects($this->once())
            ->method('get_records_sql')
            ->with(
                $this->logicalAnd(
                    $this->stringContains('AND l.user_id = ?'),
                    $this->stringContains('AND l.action LIKE ?'),
                    $this->stringContains('AND l.created_at >= ?'),
                    $this->stringContains('AND l.created_at <= ?')
                ),
                $this->callback(function($params) {
                    return $params[0] === 5
                        && $params[1] === '%login%'
                        && $params[2] === 1234567890
                        && $params[3] === 1234567999
                        && $params[4] === 50
                        && $params[5] === 0;
                })
            )
            ->willReturn($expectedRecords);

        // Act
        $result = $this->repository->get_entries($filters);

        // Assert
        $this->assertIsArray($result);
    }

    #[Test]
    public function it_applies_pagination_correctly(): void
    {
        // Arrange
        $page = 2;
        $perpage = 25;
        $expectedRecords = [];

        $this->mockDb->expects($this->once())
            ->method('get_records_sql')
            ->with(
                $this->anything(),
                $this->callback(function($params) use ($perpage, $page) {
                    $expectedOffset = $page * $perpage; // 2 * 25 = 50
                    // Last two params should be LIMIT and OFFSET
                    $paramCount = count($params);
                    return $params[$paramCount - 2] === $perpage && $params[$paramCount - 1] === $expectedOffset;
                })
            )
            ->willReturn($expectedRecords);

        // Act
        $result = $this->repository->get_entries([], $page, $perpage);

        // Assert
        $this->assertIsArray($result);
    }

    #[Test]
    public function it_orders_entries_by_created_at_descending(): void
    {
        // Arrange
        $expectedRecords = [];

        $this->mockDb->expects($this->once())
            ->method('get_records_sql')
            ->with(
                $this->stringContains('ORDER BY l.created_at DESC'),
                $this->anything()
            )
            ->willReturn($expectedRecords);

        // Act
        $result = $this->repository->get_entries();

        // Assert
        $this->assertIsArray($result);
    }

    #[Test]
    public function it_counts_entries_without_filters(): void
    {
        // Arrange
        $expectedCount = 42;

        $this->mockDb->expects($this->once())
            ->method('count_records_sql')
            ->with(
                $this->stringContains('SELECT COUNT(*) FROM logs WHERE 1=1'),
                []
            )
            ->willReturn($expectedCount);

        // Act
        $result = $this->repository->count_entries();

        // Assert
        $this->assertEquals($expectedCount, $result);
    }

    #[Test]
    public function it_counts_entries_with_user_id_filter(): void
    {
        // Arrange
        $filters = ['user_id' => 10];
        $expectedCount = 5;

        $this->mockDb->expects($this->once())
            ->method('count_records_sql')
            ->with(
                $this->stringContains('AND user_id = ?'),
                [10]
            )
            ->willReturn($expectedCount);

        // Act
        $result = $this->repository->count_entries($filters);

        // Assert
        $this->assertEquals($expectedCount, $result);
    }

    #[Test]
    public function it_counts_entries_with_action_filter(): void
    {
        // Arrange
        $filters = ['action' => 'delete'];
        $expectedCount = 3;

        $this->mockDb->expects($this->once())
            ->method('count_records_sql')
            ->with(
                $this->stringContains('AND action LIKE ?'),
                ['%delete%']
            )
            ->willReturn($expectedCount);

        // Act
        $result = $this->repository->count_entries($filters);

        // Assert
        $this->assertEquals($expectedCount, $result);
    }

    #[Test]
    public function it_counts_entries_with_date_filters(): void
    {
        // Arrange
        $filters = [
            'date_from' => 1000000000,
            'date_to' => 2000000000
        ];
        $expectedCount = 100;

        $this->mockDb->expects($this->once())
            ->method('count_records_sql')
            ->with(
                $this->logicalAnd(
                    $this->stringContains('AND created_at >= ?'),
                    $this->stringContains('AND created_at <= ?')
                ),
                [1000000000, 2000000000]
            )
            ->willReturn($expectedCount);

        // Act
        $result = $this->repository->count_entries($filters);

        // Assert
        $this->assertEquals($expectedCount, $result);
    }

    #[Test]
    public function it_exports_entries_without_filters(): void
    {
        // Arrange
        $expectedRecords = [
            (object)[
                'id' => 1,
                'user_id' => 5,
                'action' => 'login',
                'ip_address' => '192.168.1.1',
                'details' => 'User logged in',
                'created_at' => 1234567890,
                'username' => 'john_doe'
            ]
        ];

        $this->mockDb->expects($this->once())
            ->method('get_records_sql')
            ->with(
                $this->logicalAnd(
                    $this->stringContains('SELECT l.*, u.username FROM logs l'),
                    $this->stringContains('ORDER BY l.created_at DESC'),
                    $this->logicalNot($this->stringContains('LIMIT'))
                ),
                []
            )
            ->willReturn($expectedRecords);

        // Act
        $result = $this->repository->export_entries();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    #[Test]
    public function it_exports_entries_with_filters(): void
    {
        // Arrange
        $filters = [
            'user_id' => 5,
            'action' => 'login',
            'date_from' => 1234567890,
            'date_to' => 1234567999
        ];
        $expectedRecords = [];

        $this->mockDb->expects($this->once())
            ->method('get_records_sql')
            ->with(
                $this->logicalAnd(
                    $this->stringContains('AND l.user_id = ?'),
                    $this->stringContains('AND l.action LIKE ?'),
                    $this->stringContains('AND l.created_at >= ?'),
                    $this->stringContains('AND l.created_at <= ?')
                ),
                [5, '%login%', 1234567890, 1234567999]
            )
            ->willReturn($expectedRecords);

        // Act
        $result = $this->repository->export_entries($filters);

        // Assert
        $this->assertIsArray($result);
    }

    #[Test]
    public function it_includes_username_in_query(): void
    {
        // Arrange
        $expectedRecords = [];

        $this->mockDb->expects($this->once())
            ->method('get_records_sql')
            ->with(
                $this->logicalAnd(
                    $this->stringContains('LEFT JOIN users u ON l.user_id = u.id'),
                    $this->stringContains('u.username')
                ),
                $this->anything()
            )
            ->willReturn($expectedRecords);

        // Act
        $result = $this->repository->get_entries();

        // Assert
        $this->assertIsArray($result);
    }

    #[Test]
    #[DataProvider('paginationProvider')]
    public function it_calculates_offset_correctly(int $page, int $perpage, int $expectedOffset): void
    {
        // Arrange
        $this->mockDb->expects($this->once())
            ->method('get_records_sql')
            ->with(
                $this->anything(),
                $this->callback(function($params) use ($perpage, $expectedOffset) {
                    $paramCount = count($params);
                    return $params[$paramCount - 2] === $perpage && $params[$paramCount - 1] === $expectedOffset;
                })
            )
            ->willReturn([]);

        // Act
        $this->repository->get_entries([], $page, $perpage);

        // Assert - expectations verified by mock
        $this->assertTrue(true);
    }

    /**
     * Data provider for pagination testing
     */
    public static function paginationProvider(): array
    {
        return [
            'first page' => [0, 50, 0],
            'second page' => [1, 50, 50],
            'third page' => [2, 50, 100],
            'custom perpage first' => [0, 25, 0],
            'custom perpage second' => [1, 25, 25],
            'large page' => [10, 100, 1000],
        ];
    }

    #[Test]
    public function it_handles_empty_filter_values(): void
    {
        // Arrange
        $filters = [
            'user_id' => 0,
            'action' => '',
            'date_from' => 0,
            'date_to' => 0
        ];

        $this->mockDb->expects($this->once())
            ->method('get_records_sql')
            ->with(
                $this->logicalNot(
                    $this->logicalOr(
                        $this->stringContains('AND l.user_id'),
                        $this->stringContains('AND l.action'),
                        $this->stringContains('AND l.created_at')
                    )
                ),
                $this->callback(function($params) {
                    // Should only have LIMIT and OFFSET
                    return count($params) === 2;
                })
            )
            ->willReturn([]);

        // Act
        $result = $this->repository->get_entries($filters);

        // Assert
        $this->assertIsArray($result);
    }

    #[Test]
    public function it_returns_empty_array_when_no_entries(): void
    {
        // Arrange
        $this->mockDb->expects($this->once())
            ->method('get_records_sql')
            ->willReturn([]);

        // Act
        $result = $this->repository->get_entries();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_returns_zero_when_no_entries_to_count(): void
    {
        // Arrange
        $this->mockDb->expects($this->once())
            ->method('count_records_sql')
            ->willReturn(0);

        // Act
        $result = $this->repository->count_entries();

        // Assert
        $this->assertEquals(0, $result);
    }

    #[Test]
    public function it_handles_large_result_sets(): void
    {
        // Arrange
        $largeResultSet = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeResultSet[] = (object)[
                'id' => $i,
                'user_id' => $i % 10,
                'action' => 'action_' . $i,
                'created_at' => time() + $i,
                'username' => 'user_' . ($i % 10)
            ];
        }

        $this->mockDb->expects($this->once())
            ->method('get_records_sql')
            ->willReturn($largeResultSet);

        // Act
        $result = $this->repository->get_entries([], 0, 1000);

        // Assert
        $this->assertCount(1000, $result);
    }

    #[Test]
    public function it_preserves_filter_parameters_in_count_query(): void
    {
        // Arrange
        $filters = ['user_id' => 5];

        $this->mockDb->expects($this->once())
            ->method('count_records_sql')
            ->with(
                $this->stringContains('WHERE 1=1'),
                $this->equalTo([5])
            )
            ->willReturn(10);

        // Act
        $result = $this->repository->count_entries($filters);

        // Assert
        $this->assertEquals(10, $result);
    }
}
