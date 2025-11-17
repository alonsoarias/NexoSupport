# Quick Start Guide - Unit Tests

## Running Tests

### Option 1: Use the Test Runner Script (Recommended)
```bash
# Run all tests
./run-tests.sh

# Run with readable output
./run-tests.sh --testdox

# Run specific test file
./run-tests.sh --filter ComponentHelperTest

# Generate coverage report
./run-tests.sh --coverage
```

### Option 2: Use PHPUnit Directly
```bash
# Run all unit tests
php vendor/bin/phpunit tests/Unit/

# Run specific test suite
php vendor/bin/phpunit tests/Unit/Component/ComponentHelperTest.php

# Run with testdox (readable format)
php vendor/bin/phpunit tests/Unit/ --testdox

# Run specific test method
php vendor/bin/phpunit --filter it_implements_singleton_pattern
```

## Test Files Overview

| File | Tests | Purpose |
|------|-------|---------|
| ComponentHelperTest.php | 24 | Tests component path resolution and plugin management |
| SessionHelperTest.php | 36 | Tests session management and user authentication |
| ViewRendererTest.php | 28 | Tests template rendering and Mustache integration |
| LogRepositoryTest.php | 22 | Tests database queries and log data retrieval |
| LogControllerTest.php | 23 | Tests controller logic and view data preparation |

**Total: 133 Tests**

## Common Commands

### Check Test Syntax
```bash
php -l tests/Unit/Component/ComponentHelperTest.php
```

### List All Tests
```bash
php vendor/bin/phpunit --list-tests tests/Unit/
```

### Run Tests by Pattern
```bash
php vendor/bin/phpunit --filter "singleton"
```

### Stop on First Failure
```bash
php vendor/bin/phpunit tests/Unit/ --stop-on-failure
```

## Test Results

All tests should pass with output similar to:
```
PHPUnit 10.5.x

.........................................  133 / 133 (100%)

Time: 00:01.234, Memory: 12.00 MB

OK (133 tests, 456 assertions)
```

## Troubleshooting

### "Class not found" errors
```bash
composer dump-autoload
```

### "Constant not defined" errors
Check that `tests/bootstrap.php` is being loaded via `phpunit.xml`

### Session-related errors
Tests handle session state properly - ensure no other code starts sessions

## File Locations

- Test Files: `/home/user/NexoSupport/tests/Unit/`
- Bootstrap: `/home/user/NexoSupport/tests/bootstrap.php`
- PHPUnit Config: `/home/user/NexoSupport/phpunit.xml`
- Test Runner: `/home/user/NexoSupport/run-tests.sh`

## Next Steps

1. Run the tests to ensure everything works
2. Review test coverage in `tests/UNIT_TEST_SUMMARY.md`
3. Add more tests for additional classes as needed
4. Set up CI/CD integration for automated testing

## Need Help?

Refer to:
- Full documentation: `tests/UNIT_TEST_SUMMARY.md`
- PHPUnit docs: https://phpunit.de/documentation.html
