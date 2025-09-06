#!/bin/bash

# Script to safely run tests with isolated test database
# This ensures tests never affect production data

echo "=== Running Tests Safely ==="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Check if Docker Compose is available
if command -v "docker compose" >/dev/null 2>&1; then
    COMPOSE_CMD="docker compose"
elif command -v "docker-compose" >/dev/null 2>&1; then
    COMPOSE_CMD="docker-compose"
else
    echo -e "${RED}✗ Docker Compose not found${NC}"
    exit 1
fi

# Ensure test database exists and is up to date
echo -e "${YELLOW}Ensuring test database is ready...${NC}"
$COMPOSE_CMD exec -T db mysql -u root -proot_password -e "CREATE DATABASE IF NOT EXISTS aspiscine_erp_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
$COMPOSE_CMD exec -T db mysql -u root -proot_password -e "GRANT ALL PRIVILEGES ON aspiscine_erp_test.* TO 'aspiscine_user'@'%';" 2>/dev/null
$COMPOSE_CMD exec -T db mysql -u root -proot_password -e "FLUSH PRIVILEGES;" 2>/dev/null

# Refresh test database structure from production
echo -e "${YELLOW}Refreshing test database structure...${NC}"
$COMPOSE_CMD exec -T db mysqldump -u root -proot_password --no-data --routines --triggers aspiscine_erp 2>/dev/null | $COMPOSE_CMD exec -T db mysql -u root -proot_password aspiscine_erp_test 2>/dev/null
echo -e "${GREEN}✓ Test database ready${NC}"

# Run tests
echo -e "${YELLOW}Running tests...${NC}"
if [ $# -eq 0 ]; then
    # No arguments, run all tests
    echo -e "${CYAN}Running all tests...${NC}"
    $COMPOSE_CMD exec app php artisan test
else
    # Run specific test files/classes passed as arguments
    echo -e "${CYAN}Running specific tests: $@${NC}"
    $COMPOSE_CMD exec app php artisan test "$@"
fi

TEST_EXIT_CODE=$?

if [ $TEST_EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}✓ All tests passed!${NC}"
else
    echo -e "${RED}✗ Some tests failed${NC}"
fi

echo ""
echo -e "${CYAN}Test database info:${NC}"
echo -e "${CYAN}• Database: aspiscine_erp_test${NC}"
echo -e "${CYAN}• Production database (aspiscine_erp) was NOT affected${NC}"
echo ""
echo -e "${YELLOW}To run tests manually:${NC}"
echo -e "${CYAN}docker compose exec app php artisan test${NC}"
echo -e "${CYAN}docker compose exec app php artisan test tests/Unit/DpdAddressTransformationTest.php${NC}"
echo -e "${CYAN}docker compose exec app php artisan test tests/Unit/DpdAwbResponseTest.php${NC}"

exit $TEST_EXIT_CODE
