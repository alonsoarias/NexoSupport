#!/bin/bash
# Unit Test Runner Script for Frankenstyle Migration Tests
# Usage: ./run-tests.sh [options]

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default values
FILTER=""
COVERAGE=false
TESTDOX=false
VERBOSE=false

# Script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -c|--coverage)
            COVERAGE=true
            shift
            ;;
        -t|--testdox)
            TESTDOX=true
            shift
            ;;
        -v|--verbose)
            VERBOSE=true
            shift
            ;;
        -f|--filter)
            FILTER="$2"
            shift 2
            ;;
        -h|--help)
            echo "Unit Test Runner for Frankenstyle Migration"
            echo ""
            echo "Usage: ./run-tests.sh [options]"
            echo ""
            echo "Options:"
            echo "  -c, --coverage     Generate code coverage report (requires Xdebug)"
            echo "  -t, --testdox      Display tests in readable format"
            echo "  -v, --verbose      Verbose output"
            echo "  -f, --filter FILE  Run specific test file"
            echo "  -h, --help         Show this help message"
            echo ""
            echo "Examples:"
            echo "  ./run-tests.sh                                    # Run all tests"
            echo "  ./run-tests.sh --testdox                          # Run with readable output"
            echo "  ./run-tests.sh --filter ComponentHelperTest       # Run specific test"
            echo "  ./run-tests.sh --coverage                         # Generate coverage report"
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            echo "Use -h or --help for usage information"
            exit 1
            ;;
    esac
done

# Print header
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Frankenstyle Migration - Unit Tests  ${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Check if PHPUnit is installed
if [ ! -f "vendor/bin/phpunit" ]; then
    echo -e "${RED}Error: PHPUnit not found. Installing dependencies...${NC}"
    composer install
    echo ""
fi

# Build PHPUnit command
PHPUNIT_CMD="php vendor/bin/phpunit"

if [ "$VERBOSE" = true ]; then
    PHPUNIT_CMD="$PHPUNIT_CMD --verbose"
fi

if [ "$TESTDOX" = true ]; then
    PHPUNIT_CMD="$PHPUNIT_CMD --testdox"
fi

if [ "$COVERAGE" = true ]; then
    echo -e "${YELLOW}Generating code coverage report...${NC}"
    PHPUNIT_CMD="$PHPUNIT_CMD --coverage-html coverage/"
fi

# Determine test path
TEST_PATH="tests/Unit/"
if [ -n "$FILTER" ]; then
    # Check if filter is a file or class name
    if [ -f "tests/Unit/Component/${FILTER}.php" ]; then
        TEST_PATH="tests/Unit/Component/${FILTER}.php"
    elif [ -f "tests/Unit/Session/${FILTER}.php" ]; then
        TEST_PATH="tests/Unit/Session/${FILTER}.php"
    elif [ -f "tests/Unit/View/${FILTER}.php" ]; then
        TEST_PATH="tests/Unit/View/${FILTER}.php"
    elif [ -f "tests/Unit/Report/${FILTER}.php" ]; then
        TEST_PATH="tests/Unit/Report/${FILTER}.php"
    else
        TEST_PATH="$FILTER"
    fi
    echo -e "${YELLOW}Running filtered tests: ${TEST_PATH}${NC}"
else
    echo -e "${YELLOW}Running all unit tests...${NC}"
fi

echo ""

# Run tests
$PHPUNIT_CMD "$TEST_PATH"

EXIT_CODE=$?

echo ""

# Print summary
if [ $EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}  All tests passed! ✓${NC}"
    echo -e "${GREEN}========================================${NC}"

    if [ "$COVERAGE" = true ]; then
        echo ""
        echo -e "${BLUE}Coverage report generated in: coverage/index.html${NC}"
    fi
else
    echo -e "${RED}========================================${NC}"
    echo -e "${RED}  Tests failed! ✗${NC}"
    echo -e "${RED}========================================${NC}"
fi

echo ""
echo -e "${BLUE}Test files:${NC}"
echo -e "  - ComponentHelperTest.php (24 tests)"
echo -e "  - SessionHelperTest.php   (36 tests)"
echo -e "  - ViewRendererTest.php    (28 tests)"
echo -e "  - LogRepositoryTest.php   (22 tests)"
echo -e "  - LogControllerTest.php   (23 tests)"
echo ""
echo -e "${BLUE}Total: 133 tests${NC}"
echo ""

exit $EXIT_CODE
