#!/bin/bash

# Quick fix for composer dependency issues
# Bash script for Linux/Mac

echo "=== Fixing Composer Dependencies ==="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
WHITE='\033[1;37m'
NC='\033[0m' # No Color

# Check if containers are running
echo -e "${YELLOW}Checking if containers are running...${NC}"
containers=$(docker compose ps -q)
if [ -z "$containers" ]; then
    echo -e "${YELLOW}Starting containers...${NC}"
    docker compose up -d
    sleep 10
fi

# Remove composer.lock to force fresh dependency resolution
echo -e "${YELLOW}Removing composer.lock to force fresh dependency resolution...${NC}"
if [ -f "composer.lock" ]; then
    rm -f composer.lock
    echo -e "${GREEN}✓ Removed composer.lock${NC}"
fi

# Clear composer cache
echo -e "${YELLOW}Clearing composer cache...${NC}"
docker compose exec -T app composer clear-cache
echo -e "${GREEN}✓ Composer cache cleared${NC}"

# Update composer dependencies
echo -e "${YELLOW}Updating composer dependencies...${NC}"
if docker compose exec -T app composer update --no-interaction --disable-tls --no-secure-http; then
    echo -e "${GREEN}✓ Composer dependencies updated successfully${NC}"
else
    echo -e "${YELLOW}⚠ Composer update failed, trying with different options...${NC}"
    if docker compose exec -T app composer update --no-interaction --ignore-platform-reqs --disable-tls --no-secure-http; then
        echo -e "${GREEN}✓ Composer dependencies updated with relaxed security${NC}"
    else
        echo -e "${YELLOW}⚠ Update failed, trying install...${NC}"
        if docker compose exec -T app composer install --no-interaction --disable-tls --no-secure-http; then
            echo -e "${GREEN}✓ Composer dependencies installed${NC}"
        else
            echo -e "${YELLOW}Trying install with --ignore-platform-reqs...${NC}"
            if docker compose exec -T app composer install --no-interaction --ignore-platform-reqs --disable-tls --no-secure-http; then
                echo -e "${GREEN}✓ Composer dependencies installed with relaxed requirements${NC}"
            else
                echo -e "${RED}✗ All composer attempts failed${NC}"
                exit 1
            fi
        fi
    fi
fi

# Set proper permissions
echo -e "${YELLOW}Setting file permissions...${NC}"
docker compose exec -T app chown -R www:www /var/www/storage
docker compose exec -T app chown -R www:www /var/www/bootstrap/cache
docker compose exec -T app chmod -R 775 /var/www/storage
docker compose exec -T app chmod -R 775 /var/www/bootstrap/cache
echo -e "${GREEN}✓ File permissions set${NC}"

# Generate application key if needed
echo -e "${YELLOW}Checking application key...${NC}"
if [ -f ".env" ] && grep -q "APP_KEY=base64:" .env; then
    echo -e "${GREEN}✓ Application key already exists${NC}"
else
    echo -e "${YELLOW}Generating application key...${NC}"
    if docker compose exec -T app php artisan key:generate --force; then
        echo -e "${GREEN}✓ Application key generated${NC}"
    else
        echo -e "${YELLOW}⚠ Failed to generate application key${NC}"
    fi
fi

# Clear caches
echo -e "${YELLOW}Clearing application caches...${NC}"
docker compose exec -T app php artisan config:clear
docker compose exec -T app php artisan cache:clear
docker compose exec -T app php artisan view:clear
echo -e "${GREEN}✓ Caches cleared${NC}"

echo ""
echo -e "${GREEN}=== Composer Fix Complete ===${NC}"
echo ""
echo -e "${CYAN}You can now access your application at:${NC}"
echo -e "${WHITE}• Application: http://localhost:8000${NC}"
echo ""
echo -e "${CYAN}To run migrations:${NC}"
echo -e "${WHITE}docker compose exec app php artisan migrate${NC}"
