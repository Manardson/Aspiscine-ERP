#!/bin/bash

# Script to set up test database for unit tests
# This ensures tests run against a separate database and don't affect production data

echo "=== Setting up Test Database ==="

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

# Wait for MySQL to be ready
echo -e "${YELLOW}Waiting for MySQL to be ready...${NC}"
max_attempts=30
attempt=0
while [ $attempt -lt $max_attempts ]; do
    attempt=$((attempt + 1))
    sleep 2
    if $COMPOSE_CMD exec -T db mysqladmin ping -h localhost -u root -proot_password &> /dev/null; then
        echo -e "${GREEN}✓ MySQL is ready${NC}"
        break
    fi
    echo -e "${YELLOW}Waiting for MySQL... ($attempt/$max_attempts)${NC}"
done

if [ $attempt -eq $max_attempts ]; then
    echo -e "${RED}✗ MySQL failed to start within timeout${NC}"
    exit 1
fi

# Create test database
echo -e "${YELLOW}Creating test database...${NC}"
$COMPOSE_CMD exec -T db mysql -u root -proot_password -e "DROP DATABASE IF EXISTS aspiscine_erp_test;"
$COMPOSE_CMD exec -T db mysql -u root -proot_password -e "CREATE DATABASE aspiscine_erp_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
$COMPOSE_CMD exec -T db mysql -u root -proot_password -e "GRANT ALL PRIVILEGES ON aspiscine_erp_test.* TO 'aspiscine_user'@'%';"
$COMPOSE_CMD exec -T db mysql -u root -proot_password -e "FLUSH PRIVILEGES;"
echo -e "${GREEN}✓ Test database created${NC}"

# Copy structure from production database to test database
echo -e "${YELLOW}Copying database structure from production to test...${NC}"
$COMPOSE_CMD exec -T db mysqldump -u root -proot_password --no-data --routines --triggers aspiscine_erp > /tmp/schema.sql 2>/dev/null
if [ -f /tmp/schema.sql ] && [ -s /tmp/schema.sql ]; then
    $COMPOSE_CMD exec -T db mysql -u root -proot_password aspiscine_erp_test < /tmp/schema.sql
    rm -f /tmp/schema.sql
    echo -e "${GREEN}✓ Database structure copied${NC}"
else
    echo -e "${YELLOW}⚠ No existing production database found, will create fresh schema${NC}"
    # Run migrations on test database
    echo -e "${YELLOW}Running migrations on test database...${NC}"
    $COMPOSE_CMD exec -T app php artisan migrate --database=mysql --env=testing --force
    echo -e "${GREEN}✓ Test database migrations completed${NC}"
fi

echo -e "${GREEN}✓ Test database setup complete${NC}"
echo -e "${CYAN}Test database details:${NC}"
echo -e "${CYAN}• Database: aspiscine_erp_test${NC}"
echo -e "${CYAN}• Host: db (from container) / localhost:3306 (from host)${NC}"
echo -e "${CYAN}• Username: aspiscine_user${NC}"
echo -e "${CYAN}• Password: user_password${NC}"
echo ""
echo -e "${YELLOW}You can now run tests safely with:${NC}"
echo -e "${CYAN}docker compose exec app php artisan test${NC}"
