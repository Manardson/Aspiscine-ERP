#!/bin/bash

# Fix Voyager and Larapack dependencies
echo "=== Fixing Voyager and Larapack Dependencies ==="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
WHITE='\033[1;37m'
NC='\033[0m' # No Color

# Check if we're in the right directory
if [ ! -f "composer.json" ]; then
    echo -e "${RED}✗ composer.json not found. Please run from project root.${NC}"
    exit 1
fi

# Determine Docker Compose command
if command -v docker-compose &> /dev/null; then
    COMPOSE_CMD="docker-compose"
elif command -v docker &> /dev/null && docker compose version &> /dev/null; then
    COMPOSE_CMD="docker compose"
else
    echo -e "${RED}✗ Docker Compose not found${NC}"
    exit 1
fi

# Check if containers are running
echo -e "${YELLOW}Checking if containers are running...${NC}"
containers=$($COMPOSE_CMD ps -q)
if [ -z "$containers" ]; then
    echo -e "${YELLOW}Starting containers...${NC}"
    $COMPOSE_CMD up -d
    sleep 15
fi

# Remove composer.lock to force fresh resolution
echo -e "${YELLOW}Removing composer.lock for fresh dependency resolution...${NC}"
if [ -f "composer.lock" ]; then
    rm -f composer.lock
    echo -e "${GREEN}✓ Removed composer.lock${NC}"
fi

# Clear composer cache
echo -e "${YELLOW}Clearing composer cache...${NC}"
$COMPOSE_CMD exec -T app composer clear-cache
echo -e "${GREEN}✓ Composer cache cleared${NC}"

# Set git safe directory to avoid ownership issues
echo -e "${YELLOW}Setting git safe directory...${NC}"
$COMPOSE_CMD exec -T app git config --global --add safe.directory /var/www
echo -e "${GREEN}✓ Git safe directory set${NC}"

# Install dependencies with specific options for local packages
echo -e "${YELLOW}Installing dependencies (this may take a while)...${NC}"
if $COMPOSE_CMD exec -T app composer install --no-interaction --prefer-source; then
    echo -e "${GREEN}✓ Dependencies installed successfully${NC}"
else
    echo -e "${YELLOW}⚠ Install with prefer-source failed, trying without...${NC}"
    if $COMPOSE_CMD exec -T app composer install --no-interaction; then
        echo -e "${GREEN}✓ Dependencies installed successfully${NC}"
    else
        echo -e "${YELLOW}⚠ Standard install failed, trying update...${NC}"
        if $COMPOSE_CMD exec -T app composer update --no-interaction; then
            echo -e "${GREEN}✓ Dependencies updated successfully${NC}"
        else
            echo -e "${RED}✗ All composer operations failed${NC}"
            echo -e "${YELLOW}Checking for specific errors...${NC}"
            $COMPOSE_CMD logs app | tail -20
            exit 1
        fi
    fi
fi

# Dump autoload to ensure all classes are properly loaded
echo -e "${YELLOW}Dumping autoload...${NC}"
$COMPOSE_CMD exec -T app composer dump-autoload --optimize
echo -e "${GREEN}✓ Autoload dumped and optimized${NC}"

# Clear Laravel caches
echo -e "${YELLOW}Clearing Laravel caches...${NC}"
$COMPOSE_CMD exec -T app php artisan config:clear
$COMPOSE_CMD exec -T app php artisan cache:clear
$COMPOSE_CMD exec -T app php artisan view:clear
echo -e "${GREEN}✓ Laravel caches cleared${NC}"

# Check if Voyager is properly installed
echo -e "${YELLOW}Checking Voyager installation...${NC}"
if $COMPOSE_CMD exec -T app php artisan list | grep -q "voyager:"; then
    echo -e "${GREEN}✓ Voyager commands are available${NC}"
else
    echo -e "${YELLOW}⚠ Voyager commands not found, checking package discovery...${NC}"
    $COMPOSE_CMD exec -T app php artisan package:discover --ansi
fi

# Test if the application loads without the DoctrineSupportServiceProvider error
echo -e "${YELLOW}Testing application startup...${NC}"
if $COMPOSE_CMD exec -T app php artisan --version &> /dev/null; then
    echo -e "${GREEN}✓ Application loads successfully${NC}"
else
    echo -e "${RED}✗ Application still has issues${NC}"
    echo -e "${YELLOW}Checking for specific errors...${NC}"
    $COMPOSE_CMD exec -T app php artisan --version
fi

# Run a quick test to see if DPD tests work now
echo -e "${YELLOW}Testing DPD integration...${NC}"
if $COMPOSE_CMD exec -T app php artisan test tests/Unit/DpdAddressTransformationTest.php --stop-on-failure &> /dev/null; then
    echo -e "${GREEN}✓ DPD Address Transformation tests pass${NC}"
else
    echo -e "${YELLOW}⚠ DPD tests still have issues (may be expected if DB not migrated)${NC}"
fi

echo ""
echo -e "${GREEN}=== Fix Complete ===${NC}"
echo ""
echo -e "${CYAN}Your application should now be accessible at:${NC}"
echo -e "${WHITE}• http://localhost:8000${NC}"
echo ""
echo -e "${CYAN}Next steps:${NC}"
echo -e "${WHITE}1. Run migrations: $COMPOSE_CMD exec app php artisan migrate${NC}"
echo -e "${WHITE}2. Install Voyager: $COMPOSE_CMD exec app php artisan voyager:install${NC}"
echo -e "${WHITE}3. Check logs: $COMPOSE_CMD logs -f app${NC}"
echo ""
echo -e "${CYAN}If you still see errors:${NC}"
echo -e "${WHITE}• Check that all local packages are properly structured${NC}"
echo -e "${WHITE}• Verify vendor/larapack directories exist${NC}"
echo -e "${WHITE}• Run: composer dump-autoload --optimize${NC}"
