# Comprehensive troubleshooting script for Docker setup issues
# PowerShell script for Windows

Write-Host "=== Docker Setup Troubleshooting ===" -ForegroundColor Green

# Function to check if command exists
function Test-Command($cmdname) {
    return [bool](Get-Command -Name $cmdname -ErrorAction SilentlyContinue)
}

# Check Docker installation
Write-Host "Checking Docker installation..." -ForegroundColor Yellow
if (Test-Command "docker") {
    $dockerVersion = docker --version
    Write-Host "✓ Docker found: $dockerVersion" -ForegroundColor Green
    
    # Check if Docker daemon is running
    try {
        docker info | Out-Null
        Write-Host "✓ Docker daemon is running" -ForegroundColor Green
    } catch {
        Write-Host "✗ Docker daemon is not running" -ForegroundColor Red
        Write-Host "Please start Docker Desktop" -ForegroundColor Yellow
        exit 1
    }
} else {
    Write-Host "✗ Docker not found" -ForegroundColor Red
    Write-Host "Please install Docker Desktop from: https://www.docker.com/products/docker-desktop" -ForegroundColor Yellow
    exit 1
}

# Check Docker Compose
Write-Host "Checking Docker Compose..." -ForegroundColor Yellow
$composeCmd = ""
if (Test-Command "docker-compose") {
    $composeVersion = docker-compose --version
    Write-Host "✓ Docker Compose found: $composeVersion" -ForegroundColor Green
    $composeCmd = "docker-compose"
} elseif (Test-Command "docker") {
    try {
        $composeVersion = docker compose version
        Write-Host "✓ Docker Compose (plugin) found: $composeVersion" -ForegroundColor Green
        $composeCmd = "docker compose"
    } catch {
        Write-Host "✗ Docker Compose not found" -ForegroundColor Red
        Write-Host "Please install Docker Compose" -ForegroundColor Yellow
        exit 1
    }
}

# Check if we're in the right directory
Write-Host "Checking project files..." -ForegroundColor Yellow
if (Test-Path "docker-compose.yml") {
    Write-Host "✓ docker-compose.yml found" -ForegroundColor Green
} else {
    Write-Host "✗ docker-compose.yml not found" -ForegroundColor Red
    Write-Host "Please run this script from the project root directory" -ForegroundColor Yellow
    exit 1
}

if (Test-Path "composer.json") {
    Write-Host "✓ composer.json found" -ForegroundColor Green
} else {
    Write-Host "✗ composer.json not found" -ForegroundColor Red
    Write-Host "Please run this script from the project root directory" -ForegroundColor Yellow
    exit 1
}

# Fix composer.json if it still has larapack.io
Write-Host "Checking for larapack.io repository..." -ForegroundColor Yellow
$composerContent = Get-Content "composer.json" -Raw
if ($composerContent -match "larapack.io") {
    Write-Host "Found larapack.io repository, removing it..." -ForegroundColor Yellow
    
    # Backup composer.json
    Copy-Item "composer.json" "composer.json.backup" -Force
    Write-Host "✓ Backup created: composer.json.backup" -ForegroundColor Green
    
    # Remove larapack.io repository
    $composerObj = $composerContent | ConvertFrom-Json
    if ($composerObj.repositories -and $composerObj.repositories.hooks) {
        $composerObj.repositories.PSObject.Properties.Remove('hooks')
        $composerObj | ConvertTo-Json -Depth 10 | Set-Content "composer.json"
        Write-Host "✓ Removed larapack.io repository" -ForegroundColor Green
    }
} else {
    Write-Host "✓ No larapack.io repository found" -ForegroundColor Green
}

# Create .env file if it doesn't exist
Write-Host "Checking environment file..." -ForegroundColor Yellow
if (-not (Test-Path ".env")) {
    if (Test-Path ".env.docker") {
        Copy-Item ".env.docker" ".env"
        Write-Host "✓ Created .env from .env.docker" -ForegroundColor Green
    } else {
        Write-Host "✗ No .env or .env.docker file found" -ForegroundColor Red
        Write-Host "Creating basic .env file..." -ForegroundColor Yellow
        @"
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
"@ | Set-Content ".env"
        Write-Host "✓ Created basic .env file" -ForegroundColor Green
    }
} else {
    Write-Host "✓ .env file exists" -ForegroundColor Green
}

# Stop any existing containers
Write-Host "Stopping existing containers..." -ForegroundColor Yellow
if ($composeCmd -eq "docker-compose") {
    docker-compose down
} else {
    docker compose down
}

# Remove composer.lock to force fresh resolution
if (Test-Path "composer.lock") {
    Remove-Item "composer.lock" -Force
    Write-Host "✓ Removed composer.lock" -ForegroundColor Green
}

# Build and start containers
Write-Host "Building and starting containers..." -ForegroundColor Yellow
if ($composeCmd -eq "docker-compose") {
    docker-compose up -d --build
} else {
    docker compose up -d --build
}

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Containers started successfully" -ForegroundColor Green
} else {
    Write-Host "✗ Failed to start containers" -ForegroundColor Red
    Write-Host "Check the error messages above" -ForegroundColor Yellow
    exit 1
}

# Wait for containers to be ready
Write-Host "Waiting for containers to be ready..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

# Install/update composer dependencies
Write-Host "Installing composer dependencies..." -ForegroundColor Yellow
if ($composeCmd -eq "docker-compose") {
    docker-compose exec -T app composer install --no-interaction
} else {
    docker compose exec -T app composer install --no-interaction
}

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Composer dependencies installed" -ForegroundColor Green
} else {
    Write-Host "⚠ Composer install failed, trying update..." -ForegroundColor Yellow
    if ($composeCmd -eq "docker-compose") {
        docker-compose exec -T app composer update --no-interaction
    } else {
        docker compose exec -T app composer update --no-interaction
    }
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Composer dependencies updated" -ForegroundColor Green
    } else {
        Write-Host "✗ Composer operations failed" -ForegroundColor Red
        Write-Host "This might be due to network issues or dependency conflicts" -ForegroundColor Yellow
    }
}

# Generate application key
Write-Host "Generating application key..." -ForegroundColor Yellow
if ($composeCmd -eq "docker-compose") {
    docker-compose exec -T app php artisan key:generate --force
} else {
    docker compose exec -T app php artisan key:generate --force
}
Write-Host "✓ Application key generated" -ForegroundColor Green

# Set permissions
Write-Host "Setting file permissions..." -ForegroundColor Yellow
if ($composeCmd -eq "docker-compose") {
    docker-compose exec -T app chown -R www:www /var/www/storage
    docker-compose exec -T app chown -R www:www /var/www/bootstrap/cache
    docker-compose exec -T app chmod -R 775 /var/www/storage
    docker-compose exec -T app chmod -R 775 /var/www/bootstrap/cache
} else {
    docker compose exec -T app chown -R www:www /var/www/storage
    docker compose exec -T app chown -R www:www /var/www/bootstrap/cache
    docker compose exec -T app chmod -R 775 /var/www/storage
    docker compose exec -T app chmod -R 775 /var/www/bootstrap/cache
}
Write-Host "✓ File permissions set" -ForegroundColor Green

# Clear caches
Write-Host "Clearing application caches..." -ForegroundColor Yellow
if ($composeCmd -eq "docker-compose") {
    docker-compose exec -T app php artisan config:clear
    docker-compose exec -T app php artisan cache:clear
    docker-compose exec -T app php artisan view:clear
} else {
    docker compose exec -T app php artisan config:clear
    docker compose exec -T app php artisan cache:clear
    docker compose exec -T app php artisan view:clear
}
Write-Host "✓ Caches cleared" -ForegroundColor Green

Write-Host ""
Write-Host "=== Setup Complete ===" -ForegroundColor Green
Write-Host ""
Write-Host "Access points:" -ForegroundColor Cyan
Write-Host "• Application: http://localhost:8000" -ForegroundColor White
Write-Host "• PhpMyAdmin: http://localhost:8080" -ForegroundColor White
Write-Host "• MailHog: http://localhost:8025" -ForegroundColor White
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "1. Run migrations: $composeCmd exec app php artisan migrate" -ForegroundColor White
Write-Host "2. Check logs: $composeCmd logs -f app" -ForegroundColor White
Write-Host "3. Access container: $composeCmd exec app bash" -ForegroundColor White
Write-Host ""
Write-Host "Container root access:" -ForegroundColor Cyan
Write-Host "• Access container as root: $composeCmd exec --user root app bash" -ForegroundColor White
Write-Host "• The root password inside container is: root" -ForegroundColor White
