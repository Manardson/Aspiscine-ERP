#!/bin/bash

# Fix for larapack.io SSL certificate issue
# Bash script for Linux/Mac

echo "=== Fixing Larapack.io SSL Issue ==="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
WHITE='\033[1;37m'
NC='\033[0m' # No Color

# Backup original composer.json
echo -e "${YELLOW}Creating backup of composer.json...${NC}"
cp composer.json composer.json.backup
echo -e "${GREEN}✓ Backup created as composer.json.backup${NC}"

# Remove the problematic larapack repository using jq if available, otherwise sed
echo -e "${YELLOW}Removing larapack.io repository temporarily...${NC}"
if command -v jq &> /dev/null; then
    # Use jq for clean JSON manipulation
    jq 'del(.repositories.hooks)' composer.json > composer.json.tmp && mv composer.json.tmp composer.json
    echo -e "${GREEN}✓ Larapack.io repository removed using jq${NC}"
else
    # Fallback to sed for basic removal
    sed -i.bak '/\"hooks\":/,/},/d' composer.json
    echo -e "${GREEN}✓ Larapack.io repository removed using sed${NC}"
fi

# Remove composer.lock to force fresh resolution
if [ -f "composer.lock" ]; then
    rm -f composer.lock
    echo -e "${GREEN}✓ Removed composer.lock${NC}"
fi

# Check if containers are running
echo -e "${YELLOW}Checking if containers are running...${NC}"
containers=$(docker compose ps -q)
if [ -z "$containers" ]; then
    echo -e "${YELLOW}Starting containers...${NC}"
    docker compose up -d
    sleep 10
fi

# Clear composer cache
echo -e "${YELLOW}Clearing composer cache...${NC}"
docker compose exec -T app composer clear-cache
echo -e "${GREEN}✓ Composer cache cleared${NC}"

# Update composer dependencies
echo -e "${YELLOW}Updating composer dependencies...${NC}"
if docker compose exec -T app composer update --no-interaction; then
    echo -e "${GREEN}✓ Composer dependencies updated successfully${NC}"
else
    echo -e "${YELLOW}⚠ Composer update failed, trying install...${NC}"
    if docker compose exec -T app composer install --no-interaction; then
        echo -e "${GREEN}✓ Composer dependencies installed${NC}"
    else
        echo -e "${RED}✗ Failed to install Composer dependencies${NC}"
        echo -e "${YELLOW}Restoring original composer.json...${NC}"
        cp composer.json.backup composer.json
        exit 1
    fi
fi

# Set proper permissions
echo -e "${YELLOW}Setting file permissions...${NC}"
docker compose exec -T app chown -R www:www /var/www/storage
docker compose exec -T app chown -R www:www /var/www/bootstrap/cache
docker compose exec -T app chmod -R 775 /var/www/storage
docker compose exec -T app chmod -R 775 /var/www/bootstrap/cache
echo -e "${GREEN}✓ File permissions set${NC}"

echo ""
echo -e "${GREEN}=== Larapack SSL Fix Complete ===${NC}"
echo ""
echo -e "${CYAN}Note: The larapack.io repository has been temporarily removed.${NC}"
echo -e "${CYAN}If you need packages from larapack.io, you may need to:${NC}"
echo -e "${WHITE}1. Restore composer.json.backup${NC}"
echo -e "${WHITE}2. Configure SSL certificates properly${NC}"
echo -e "${WHITE}3. Or use alternative package sources${NC}"
echo ""
echo -e "${CYAN}Application should now be accessible at:${NC}"
echo -e "${WHITE}• http://localhost:8000${NC}"
