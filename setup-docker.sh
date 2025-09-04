#!/bin/bash

# Aspiscine ERP Docker Setup Script
# Bash script for Linux/Mac

echo "=== Aspiscine ERP Docker Setup ==="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
WHITE='\033[1;37m'
NC='\033[0m' # No Color

# Check if Docker is running
echo -e "${YELLOW}Checking Docker...${NC}"
if command -v docker &> /dev/null && command -v docker compose &> /dev/null; then
    echo -e "${GREEN}✓ Docker and Docker Compose are available${NC}"
else
    echo -e "${RED}✗ Docker or Docker Compose not found. Please install Docker.${NC}"
    exit 1
fi

# Check if .env file exists, if not copy from .env.docker
echo -e "${YELLOW}Setting up environment file...${NC}"
if [ ! -f ".env" ]; then
    if [ -f ".env.docker" ]; then
        cp .env.docker .env
        echo -e "${GREEN}✓ Copied .env.docker to .env${NC}"
    else
        echo -e "${RED}✗ .env.docker file not found${NC}"
        exit 1
    fi
else
    echo -e "${GREEN}✓ .env file already exists${NC}"
fi

# Stop any existing containers
echo -e "${YELLOW}Stopping existing containers...${NC}"
docker compose down

# Build and start containers
echo -e "${YELLOW}Building and starting Docker containers...${NC}"
if docker compose up -d --build; then
    echo -e "${GREEN}✓ Containers started successfully${NC}"
else
    echo -e "${RED}✗ Failed to start containers${NC}"
    exit 1
fi

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

# Update and install Composer dependencies
echo -e "${YELLOW}Updating and installing Composer dependencies...${NC}"
if docker compose exec -T app composer update --no-interaction --disable-tls --no-secure-http; then
    echo -e "${GREEN}✓ Composer dependencies updated and installed${NC}"
else
    echo -e "${YELLOW}⚠ Composer update failed, trying with relaxed security...${NC}"
    if docker compose exec -T app composer update --no-interaction --ignore-platform-reqs --disable-tls --no-secure-http; then
        echo -e "${GREEN}✓ Composer dependencies updated with relaxed security${NC}"
    else
        echo -e "${YELLOW}⚠ Update failed, trying install...${NC}"
        if docker compose exec -T app composer install --no-interaction --disable-tls --no-secure-http; then
            echo -e "${GREEN}✓ Composer dependencies installed${NC}"
        else
            echo -e "${RED}✗ Failed to install Composer dependencies${NC}"
        fi
    fi
fi

# Generate application key
echo -e "${YELLOW}Generating application key...${NC}"
if docker compose exec -T app php artisan key:generate --force; then
    echo -e "${GREEN}✓ Application key generated${NC}"
else
    echo -e "${RED}✗ Failed to generate application key${NC}"
fi

# Run database migrations
echo -e "${YELLOW}Running database migrations...${NC}"
if docker compose exec -T app php artisan migrate --force; then
    echo -e "${GREEN}✓ Database migrations completed${NC}"
else
    echo -e "${YELLOW}⚠ Database migrations failed (this might be expected if tables already exist)${NC}"
fi

# Clear caches
echo -e "${YELLOW}Clearing application caches...${NC}"
docker compose exec -T app php artisan config:clear
docker compose exec -T app php artisan cache:clear
docker compose exec -T app php artisan view:clear
echo -e "${GREEN}✓ Caches cleared${NC}"

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
echo -e "${CYAN}Useful commands:${NC}"
echo -e "${WHITE}• View logs: docker compose logs -f app${NC}"
echo -e "${WHITE}• Access shell: docker compose exec app bash${NC}"
echo -e "${WHITE}• Stop containers: docker compose down${NC}"
echo ""
echo -e "${CYAN}DPD Integration:${NC}"
echo -e "${WHITE}• Enhanced error handling and logging implemented${NC}"
echo -e "${WHITE}• Address validation added${NC}"
echo -e "${WHITE}• Comprehensive unit tests created${NC}"
echo -e "${WHITE}• Check logs for DPD operations: docker compose logs app | grep DPD${NC}"
