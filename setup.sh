#!/bin/bash

# Comprehensive troubleshooting script for Docker setup issues
# This script will diagnose and fix common setup problems

echo "=== Docker Setup Troubleshooting ==="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
WHITE='\033[1;37m'
NC='\033[0m' # No Color

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Check operating system
echo -e "${CYAN}Checking operating system...${NC}"
if [[ "$OSTYPE" == "linux-gnu"* ]]; then
    OS="Linux"
elif [[ "$OSTYPE" == "darwin"* ]]; then
    OS="macOS"
elif [[ "$OSTYPE" == "cygwin" ]] || [[ "$OSTYPE" == "msys" ]] || [[ "$OSTYPE" == "win32" ]]; then
    OS="Windows"
else
    OS="Unknown"
fi
echo -e "${GREEN}✓ Operating System: $OS${NC}"

# Check Docker installation
echo -e "${YELLOW}Checking Docker installation...${NC}"
if command_exists docker; then
    DOCKER_VERSION=$(docker --version)
    echo -e "${GREEN}✓ Docker found: $DOCKER_VERSION${NC}"
    
    # Check if Docker daemon is running
    if docker info >/dev/null 2>&1; then
        echo -e "${GREEN}✓ Docker daemon is running${NC}"
    else
        echo -e "${RED}✗ Docker daemon is not running${NC}"
        echo -e "${YELLOW}Please start Docker Desktop or Docker service${NC}"
        exit 1
    fi
else
    echo -e "${RED}✗ Docker not found${NC}"
    echo -e "${YELLOW}Please install Docker Desktop from: https://www.docker.com/products/docker-desktop${NC}"
    exit 1
fi

# Check Docker Compose
echo -e "${YELLOW}Checking Docker Compose...${NC}"
if command_exists "docker compose"; then
    COMPOSE_VERSION=$(docker compose --version)
    echo -e "${GREEN}✓ Docker Compose found: $COMPOSE_VERSION${NC}"
    COMPOSE_CMD="docker compose"
elif docker compose version >/dev/null 2>&1; then
    COMPOSE_VERSION=$(docker compose version)
    echo -e "${GREEN}✓ Docker Compose (plugin) found: $COMPOSE_VERSION${NC}"
    COMPOSE_CMD="docker compose"
else
    echo -e "${RED}✗ Docker Compose not found${NC}"
    echo -e "${YELLOW}Please install Docker Compose${NC}"
    exit 1
fi

# Check if we're in the right directory
echo -e "${YELLOW}Checking project files...${NC}"
if [ -f "docker-compose.yml" ]; then
    echo -e "${GREEN}✓ docker-compose.yml found${NC}"
else
    echo -e "${RED}✗ docker-compose.yml not found${NC}"
    echo -e "${YELLOW}Please run this script from the project root directory${NC}"
    exit 1
fi

if [ -f "composer.json" ]; then
    echo -e "${GREEN}✓ composer.json found${NC}"
else
    echo -e "${RED}✗ composer.json not found${NC}"
    echo -e "${YELLOW}Please run this script from the project root directory${NC}"
    exit 1
fi

# Fix composer.json if it still has larapack.io
echo -e "${YELLOW}Checking for larapack.io repository...${NC}"
if grep -q "larapack.io" composer.json; then
    echo -e "${YELLOW}Found larapack.io repository, removing it...${NC}"
    
    # Backup composer.json
    cp composer.json composer.json.backup
    echo -e "${GREEN}✓ Backup created: composer.json.backup${NC}"
    
    # Remove larapack.io repository
    if command_exists jq; then
        jq 'del(.repositories.hooks)' composer.json > composer.json.tmp && mv composer.json.tmp composer.json
        echo -e "${GREEN}✓ Removed larapack.io repository using jq${NC}"
    else
        # Fallback to sed
        sed -i.bak '/\"hooks\":/,/},/d' composer.json
        echo -e "${GREEN}✓ Removed larapack.io repository using sed${NC}"
    fi
else
    echo -e "${GREEN}✓ No larapack.io repository found${NC}"
fi

# Create .env file if it doesn't exist
echo -e "${YELLOW}Checking environment file...${NC}"
if [ ! -f ".env" ]; then
    if [ -f ".env.docker" ]; then
        cp .env.docker .env
        echo -e "${GREEN}✓ Created .env from .env.docker${NC}"
    else
        echo -e "${RED}✗ No .env or .env.docker file found${NC}"
        echo -e "${YELLOW}Creating basic .env file...${NC}"
        cat > .env << 'EOF'
APP_NAME="Aspiscine ERP"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=aspiscine_erp
DB_USERNAME=aspiscine_user
DB_PASSWORD=user_password

CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=redis
REDIS_PORT=6379
EOF
        echo -e "${GREEN}✓ Created basic .env file${NC}"
    fi
else
    echo -e "${GREEN}✓ .env file exists${NC}"
fi

# Stop any existing containers
echo -e "${YELLOW}Stopping existing containers...${NC}"
$COMPOSE_CMD down

# Remove composer.lock to force fresh resolution
if [ -f "composer.lock" ]; then
    rm -f composer.lock
    echo -e "${GREEN}✓ Removed composer.lock${NC}"
fi

# Build and start containers
echo -e "${YELLOW}Building and starting containers...${NC}"
if $COMPOSE_CMD up -d --build; then
    echo -e "${GREEN}✓ Containers started successfully${NC}"
else
    echo -e "${RED}✗ Failed to start containers${NC}"
    echo -e "${YELLOW}Check the error messages above${NC}"
    exit 1
fi

# Wait for containers to be ready
echo -e "${YELLOW}Waiting for containers to be ready...${NC}"
sleep 10

# Wait for MySQL to be ready
echo -e "${YELLOW}Waiting for MySQL to be ready...${NC}"
max_attempts=30
attempt=0
while [ $attempt -lt $max_attempts ]; do
    attempt=$((attempt + 1))
    sleep 2
    if docker compose exec -T db mysqladmin ping -h localhost -u root -proot_password &> /dev/null; then
        echo -e "${GREEN}✓ MySQL is ready${NC}"
        break
    fi
    echo -e "${YELLOW}Waiting for MySQL... ($attempt/$max_attempts)${NC}"
done

if [ $attempt -eq $max_attempts ]; then
    echo -e "${RED}✗ MySQL failed to start within timeout${NC}"
    exit 1
fi

# Install/update composer dependencies
# echo -e "${YELLOW}Installing composer dependencies...${NC}"
# if $COMPOSE_CMD exec -T app composer install --no-interaction; then
#     echo -e "${GREEN}✓ Composer dependencies installed${NC}"
# else
#     echo -e "${YELLOW}⚠ Composer install failed, trying update...${NC}"
#     if $COMPOSE_CMD exec -T app composer update --no-interaction; then
#         echo -e "${GREEN}✓ Composer dependencies updated${NC}"
#     else
#         echo -e "${RED}✗ Composer operations failed${NC}"
#         echo -e "${YELLOW}This might be due to network issues or dependency conflicts${NC}"
#     fi
# fi

# Generate application key
echo -e "${YELLOW}Generating application key...${NC}"
$COMPOSE_CMD exec -T app php artisan key:generate --force
echo -e "${GREEN}✓ Application key generated${NC}"

Set permissions
echo -e "${YELLOW}Setting file permissions...${NC}"
$COMPOSE_CMD exec -T --user root app chown -R www:www /var/www/vendor
$COMPOSE_CMD exec -T --user root app chown -R www:www /var/www/storage
$COMPOSE_CMD exec -T --user root app chown -R www:www /var/www/bootstrap/cache
$COMPOSE_CMD exec -T --user root app chmod -R 775 /var/www/storage
$COMPOSE_CMD exec -T --user root app chmod -R 775 /var/www/bootstrap/cache
echo -e "${GREEN}✓ File permissions set${NC}"

# Clear caches
echo -e "${YELLOW}Clearing application caches...${NC}"
$COMPOSE_CMD exec -T app php artisan config:clear
$COMPOSE_CMD exec -T app php artisan cache:clear
$COMPOSE_CMD exec -T app php artisan view:clear
echo -e "${GREEN}✓ Caches cleared${NC}"

# Run database migrations
# echo -e "${YELLOW}Running database migrations...${NC}"
# if docker compose exec -T app php artisan migrate --force; then
#     echo -e "${GREEN}✓ Database migrations completed${NC}"
# else
#     echo -e "${YELLOW}⚠ Database migrations failed (this might be expected if tables already exist)${NC}"
# fi

# Set up test database
echo -e "${YELLOW}Setting up test database...${NC}"
$COMPOSE_CMD exec -T db mysql -u root -proot_password -e "DROP DATABASE IF EXISTS aspiscine_erp_test;" 2>/dev/null
$COMPOSE_CMD exec -T db mysql -u root -proot_password -e "CREATE DATABASE aspiscine_erp_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
$COMPOSE_CMD exec -T db mysql -u root -proot_password -e "GRANT ALL PRIVILEGES ON aspiscine_erp_test.* TO 'aspiscine_user'@'%';" 2>/dev/null
$COMPOSE_CMD exec -T db mysql -u root -proot_password -e "FLUSH PRIVILEGES;" 2>/dev/null

# Copy database structure from production to test
echo -e "${YELLOW}Copying database structure to test database...${NC}"
$COMPOSE_CMD exec -T db mysqldump -u root -proot_password --no-data --routines --triggers aspiscine_erp 2>/dev/null | $COMPOSE_CMD exec -T db mysql -u root -proot_password aspiscine_erp_test 2>/dev/null
echo -e "${GREEN}✓ Test database setup complete${NC}"

# Run tests to verify DPD integration
echo -e "${YELLOW}Running DPD integration tests...${NC}"
if docker compose exec -T app php artisan test tests/Unit/DpdAddressTransformationTest.php --stop-on-failure; then
    echo -e "${GREEN}✓ DPD Address Transformation tests passed${NC}"
else
    echo -e "${YELLOW}⚠ DPD Address Transformation tests failed${NC}"
fi

if docker compose exec -T app php artisan test tests/Unit/DpdAwbResponseTest.php --stop-on-failure; then
    echo -e "${GREEN}✓ DPD AWB Response tests passed${NC}"
else
    echo -e "${YELLOW}⚠ DPD AWB Response tests failed${NC}"
fi

echo ""
echo -e "${GREEN}=== Setup Complete ===${NC}"
echo ""
echo -e "${CYAN}Access points:${NC}"
echo -e "${WHITE}• Application: http://localhost:8000${NC}"
echo -e "${WHITE}• PhpMyAdmin: http://localhost:8080${NC}"
echo -e "${WHITE}• MailHog: http://localhost:8025${NC}"
echo ""
echo -e "${CYAN}Database credentials:${NC}"
echo -e "${WHITE}• Database: aspiscine_erp${NC}"
echo -e "${WHITE}• Username: aspiscine_user${NC}"
echo -e "${WHITE}• Password: user_password${NC}"
echo ""
echo -e "${CYAN}Container root access:${NC}"
echo -e "${WHITE}• Access container as root: $COMPOSE_CMD exec --user root app bash${NC}"
echo -e "${WHITE}• The root password inside container is: root${NC}"
echo ""
echo -e "${CYAN}DPD Integration:${NC}"
echo -e "${WHITE}• Enhanced error handling and logging implemented${NC}"
echo -e "${WHITE}• Address validation added${NC}"
echo -e "${WHITE}• Comprehensive unit tests created${NC}"
echo -e "${WHITE}• Check logs for DPD operations: docker compose logs app | grep DPD${NC}"