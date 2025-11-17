#!/bin/bash
# Pre-Deployment Validation Script
# Frankenstyle Migration - NexoSupport
# Run this before deploying to production

set -e

echo "=========================================="
echo "PRE-DEPLOYMENT VALIDATION"
echo "Frankenstyle Migration - NexoSupport"
echo "=========================================="
echo ""

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Counters
PASSED=0
FAILED=0
WARNINGS=0

# Function to print status
print_status() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓${NC} $2"
        ((PASSED++))
    else
        echo -e "${RED}✗${NC} $2"
        ((FAILED++))
    fi
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
    ((WARNINGS++))
}

echo "1. CHECKING SYSTEM REQUIREMENTS"
echo "----------------------------------------"

# Check PHP version
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
PHP_MAJOR=$(php -r 'echo PHP_MAJOR_VERSION;')
PHP_MINOR=$(php -r 'echo PHP_MINOR_VERSION;')

if [ "$PHP_MAJOR" -ge 8 ] && [ "$PHP_MINOR" -ge 1 ]; then
    print_status 0 "PHP version: $PHP_VERSION (>= 8.1 required)"
else
    print_status 1 "PHP version: $PHP_VERSION (>= 8.1 REQUIRED)"
fi

# Check Composer
if command -v composer &> /dev/null; then
    COMPOSER_VERSION=$(composer --version | grep -oP '\d+\.\d+\.\d+' | head -1)
    print_status 0 "Composer installed: $COMPOSER_VERSION"
else
    print_status 1 "Composer not found"
fi

# Check required PHP extensions
echo ""
echo "2. CHECKING PHP EXTENSIONS"
echo "----------------------------------------"

check_extension() {
    if php -m | grep -q "^$1$"; then
        print_status 0 "Extension $1 loaded"
    else
        print_status 1 "Extension $1 NOT loaded"
    fi
}

check_extension "pdo"
check_extension "json"
check_extension "mbstring"
check_extension "openssl"
check_extension "curl"

echo ""
echo "3. CHECKING FILE STRUCTURE"
echo "----------------------------------------"

# Check critical directories exist
check_dir() {
    if [ -d "$1" ]; then
        print_status 0 "Directory exists: $1"
    else
        print_status 1 "Directory missing: $1"
    fi
}

check_dir "lib/classes"
check_dir "core"
check_dir "admin"
check_dir "auth"
check_dir "report"
check_dir "theme"
check_dir "var"
check_dir "tests"

echo ""
echo "4. CHECKING CRITICAL FILES"
echo "----------------------------------------"

# Check critical files exist
check_file() {
    if [ -f "$1" ]; then
        print_status 0 "File exists: $1"
    else
        print_status 1 "File missing: $1"
    fi
}

check_file "composer.json"
check_file "phpunit.xml"
check_file "run-tests.sh"
check_file "lib/classes/auth/AuthInterface.php"
check_file "lib/classes/component/ComponentHelper.php"
check_file "lib/classes/session/SessionHelper.php"
check_file "lib/classes/cache/Cache.php"
check_file "core/View/ViewRenderer.php"

echo ""
echo "5. CHECKING PERMISSIONS"
echo "----------------------------------------"

# Check var directory is writable
if [ -w "var" ]; then
    print_status 0 "var/ directory is writable"
else
    print_status 1 "var/ directory is NOT writable"
fi

# Check or create cache directories
mkdir -p var/cache/templates var/cache/components 2>/dev/null
if [ -w "var/cache" ]; then
    print_status 0 "var/cache/ directory is writable"
else
    print_status 1 "var/cache/ directory is NOT writable"
fi

echo ""
echo "6. VALIDATING PHP SYNTAX"
echo "----------------------------------------"

# Count PHP files
PHP_FILES=$(find . -name "*.php" \
    -not -path "./vendor/*" \
    -not -path "./var/*" \
    -type f | wc -l)

echo "Found $PHP_FILES PHP files to validate..."

# Check PHP syntax
SYNTAX_ERRORS=0
find . -name "*.php" \
    -not -path "./vendor/*" \
    -not -path "./var/*" \
    -type f -print0 | \
while IFS= read -r -d '' file; do
    if ! php -l "$file" > /dev/null 2>&1; then
        echo -e "${RED}Syntax error in: $file${NC}"
        ((SYNTAX_ERRORS++))
    fi
done

if [ $SYNTAX_ERRORS -eq 0 ]; then
    print_status 0 "All PHP files have valid syntax"
else
    print_status 1 "Found $SYNTAX_ERRORS files with syntax errors"
fi

echo ""
echo "7. CHECKING COMPOSER"
echo "----------------------------------------"

# Check if vendor directory exists
if [ -d "vendor" ]; then
    print_status 0 "vendor/ directory exists"
else
    print_warning "vendor/ directory missing - run 'composer install'"
fi

# Validate composer.json
if composer validate > /dev/null 2>&1; then
    print_status 0 "composer.json is valid"
else
    print_status 1 "composer.json validation failed"
fi

# Check critical dependencies
if [ -d "vendor/mustache" ]; then
    print_status 0 "Mustache library installed"
else
    print_status 1 "Mustache library NOT installed"
fi

if [ -d "vendor/phpunit" ]; then
    print_status 0 "PHPUnit installed"
else
    print_warning "PHPUnit NOT installed (only needed for development)"
fi

echo ""
echo "8. RUNNING UNIT TESTS"
echo "----------------------------------------"

if [ -f "run-tests.sh" ] && [ -x "run-tests.sh" ]; then
    echo "Running test suite..."
    if ./run-tests.sh > /tmp/test-output.txt 2>&1; then
        TEST_COUNT=$(grep -oP '\d+(?= tests?)' /tmp/test-output.txt | head -1)
        print_status 0 "All tests passed ($TEST_COUNT tests)"
    else
        print_status 1 "Some tests failed - check output"
        cat /tmp/test-output.txt
    fi
    rm -f /tmp/test-output.txt
else
    print_warning "Test runner not found or not executable"
fi

echo ""
echo "9. CHECKING GIT STATUS"
echo "----------------------------------------"

# Check if on correct branch
CURRENT_BRANCH=$(git branch --show-current 2>/dev/null || echo "unknown")
if [ "$CURRENT_BRANCH" = "claude/frankenstyle-refactor-012YT4YqF9imBYn4kATA1fUe" ]; then
    print_status 0 "On deployment branch: $CURRENT_BRANCH"
else
    print_warning "On branch: $CURRENT_BRANCH (expected: claude/frankenstyle-refactor-012YT4YqF9imBYn4kATA1fUe)"
fi

# Check for uncommitted changes
if git diff-index --quiet HEAD -- 2>/dev/null; then
    print_status 0 "No uncommitted changes"
else
    print_warning "Uncommitted changes detected"
fi

# Check if synced with remote
if git fetch origin 2>/dev/null; then
    LOCAL=$(git rev-parse @ 2>/dev/null)
    REMOTE=$(git rev-parse @{u} 2>/dev/null || echo "")

    if [ "$LOCAL" = "$REMOTE" ]; then
        print_status 0 "Branch is synced with remote"
    else
        print_warning "Branch is NOT synced with remote"
    fi
fi

echo ""
echo "10. CHECKING DOCUMENTATION"
echo "----------------------------------------"

check_doc() {
    if [ -f "$1" ]; then
        LINES=$(wc -l < "$1")
        print_status 0 "Documentation exists: $1 ($LINES lines)"
    else
        print_warning "Documentation missing: $1"
    fi
}

check_doc "PULL_REQUEST.md"
check_doc "DEPLOYMENT_GUIDE.md"
check_doc "docs/FRANKENSTYLE_PROJECT_COMPLETE.md"
check_doc "tests/UNIT_TEST_SUMMARY.md"

echo ""
echo "=========================================="
echo "VALIDATION SUMMARY"
echo "=========================================="
echo ""
echo -e "${GREEN}Passed:${NC} $PASSED"
echo -e "${RED}Failed:${NC} $FAILED"
echo -e "${YELLOW}Warnings:${NC} $WARNINGS"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ VALIDATION SUCCESSFUL${NC}"
    echo "System is ready for deployment!"
    echo ""
    echo "Next steps:"
    echo "1. Review DEPLOYMENT_GUIDE.md"
    echo "2. Create backup"
    echo "3. Deploy to production"
    exit 0
else
    echo -e "${RED}✗ VALIDATION FAILED${NC}"
    echo "Fix the issues above before deploying!"
    exit 1
fi
