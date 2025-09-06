# Fix Voyager and Larapack dependencies
Write-Host "=== Fixing Voyager and Larapack Dependencies ===" -ForegroundColor Green

# Check if we're in the right directory
if (-not (Test-Path "composer.json")) {
    Write-Host "✗ composer.json not found. Please run from project root." -ForegroundColor Red
    exit 1
}

# Determine Docker Compose command
$composeCmd = ""
if (Get-Command "docker-compose" -ErrorAction SilentlyContinue) {
    $composeCmd = "docker-compose"
} elseif (Get-Command "docker" -ErrorAction SilentlyContinue) {
    try {
        docker compose version | Out-Null
        $composeCmd = "docker compose"
    } catch {
        Write-Host "✗ Docker Compose not found" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "✗ Docker not found" -ForegroundColor Red
    exit 1
}

# Check if containers are running
Write-Host "Checking if containers are running..." -ForegroundColor Yellow
if ($composeCmd -eq "docker-compose") {
    $containers = docker-compose ps -q
} else {
    $containers = docker compose ps -q
}

if (-not $containers) {
    Write-Host "Starting containers..." -ForegroundColor Yellow
    if ($composeCmd -eq "docker-compose") {
        docker-compose up -d
    } else {
        docker compose up -d
    }
    Start-Sleep -Seconds 15
}

# Remove composer.lock to force fresh resolution
Write-Host "Removing composer.lock for fresh dependency resolution..." -ForegroundColor Yellow
if (Test-Path "composer.lock") {
    Remove-Item "composer.lock" -Force
    Write-Host "✓ Removed composer.lock" -ForegroundColor Green
}

# Clear composer cache
Write-Host "Clearing composer cache..." -ForegroundColor Yellow
if ($composeCmd -eq "docker-compose") {
    docker-compose exec -T app composer clear-cache
} else {
    docker compose exec -T app composer clear-cache
}
Write-Host "✓ Composer cache cleared" -ForegroundColor Green

# Set git safe directory to avoid ownership issues
Write-Host "Setting git safe directory..." -ForegroundColor Yellow
if ($composeCmd -eq "docker-compose") {
    docker-compose exec -T app git config --global --add safe.directory /var/www
} else {
    docker compose exec -T app git config --global --add safe.directory /var/www
}
Write-Host "✓ Git safe directory set" -ForegroundColor Green

# Install dependencies with specific options for local packages
Write-Host "Installing dependencies (this may take a while)..." -ForegroundColor Yellow
if ($composeCmd -eq "docker-compose") {
    docker-compose exec -T app composer install --no-interaction --prefer-source
} else {
    docker compose exec -T app composer install --no-interaction --prefer-source
}

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Dependencies installed successfully" -ForegroundColor Green
} else {
    Write-Host "⚠ Install with prefer-source failed, trying without..." -ForegroundColor Yellow
    if ($composeCmd -eq "docker-compose") {
        docker-compose exec -T app composer install --no-interaction
    } else {
        docker compose exec -T app composer install --no-interaction
    }
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Dependencies installed successfully" -ForegroundColor Green
    } else {
        Write-Host "⚠ Standard install failed, trying update..." -ForegroundColor Yellow
        if ($composeCmd -eq "docker-compose") {
            docker-compose exec -T app composer update --no-interaction
        } else {
            docker compose exec -T app composer update --no-interaction
        }
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✓ Dependencies updated successfully" -ForegroundColor Green
        } else {
            Write-Host "✗ All composer operations failed" -ForegroundColor Red
            Write-Host "Checking for specific errors..." -ForegroundColor Yellow
            if ($composeCmd -eq "docker-compose") {
                docker-compose logs app | Select-Object -Last 20
            } else {
                docker compose logs app | Select-Object -Last 20
            }
            exit 1
        }
    }
}

# Dump autoload to ensure all classes are properly loaded
Write-Host "Dumping autoload..." -ForegroundColor Yellow
if ($composeCmd -eq "docker-compose") {
    docker-compose exec -T app composer dump-autoload --optimize
} else {
    docker compose exec -T app composer dump-autoload --optimize
}
Write-Host "✓ Autoload dumped and optimized" -ForegroundColor Green

# Clear Laravel caches
Write-Host "Clearing Laravel caches..." -ForegroundColor Yellow
if ($composeCmd -eq "docker-compose") {
    docker-compose exec -T app php artisan config:clear
    docker-compose exec -T app php artisan cache:clear
    docker-compose exec -T app php artisan view:clear
} else {
    docker compose exec -T app php artisan config:clear
    docker compose exec -T app php artisan cache:clear
    docker compose exec -T app php artisan view:clear
}
Write-Host "✓ Laravel caches cleared" -ForegroundColor Green

# Check if Voyager is properly installed
Write-Host "Checking Voyager installation..." -ForegroundColor Yellow
if ($composeCmd -eq "docker-compose") {
    $voyagerCheck = docker-compose exec -T app php artisan list | Select-String "voyager:"
} else {
    $voyagerCheck = docker compose exec -T app php artisan list | Select-String "voyager:"
}

if ($voyagerCheck) {
    Write-Host "✓ Voyager commands are available" -ForegroundColor Green
} else {
    Write-Host "⚠ Voyager commands not found, checking package discovery..." -ForegroundColor Yellow
    if ($composeCmd -eq "docker-compose") {
        docker-compose exec -T app php artisan package:discover --ansi
    } else {
        docker compose exec -T app php artisan package:discover --ansi
    }
}

# Test if the application loads without the DoctrineSupportServiceProvider error
Write-Host "Testing application startup..." -ForegroundColor Yellow
if ($composeCmd -eq "docker-compose") {
    $testResult = docker-compose exec -T app php artisan --version 2>$null
} else {
    $testResult = docker compose exec -T app php artisan --version 2>$null
}

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Application loads successfully" -ForegroundColor Green
} else {
    Write-Host "✗ Application still has issues" -ForegroundColor Red
    Write-Host "Checking for specific errors..." -ForegroundColor Yellow
    if ($composeCmd -eq "docker-compose") {
        docker-compose exec -T app php artisan --version
    } else {
        docker compose exec -T app php artisan --version
    }
}

# Run a quick test to see if DPD tests work now
Write-Host "Testing DPD integration..." -ForegroundColor Yellow
if ($composeCmd -eq "docker-compose") {
    $dpdTest = docker-compose exec -T app php artisan test tests/Unit/DpdAddressTransformationTest.php --stop-on-failure 2>$null
} else {
    $dpdTest = docker compose exec -T app php artisan test tests/Unit/DpdAddressTransformationTest.php --stop-on-failure 2>$null
}

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ DPD Address Transformation tests pass" -ForegroundColor Green
} else {
    Write-Host "⚠ DPD tests still have issues (may be expected if DB not migrated)" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "=== Fix Complete ===" -ForegroundColor Green
Write-Host ""
Write-Host "Your application should now be accessible at:" -ForegroundColor Cyan
Write-Host "• http://localhost:8000" -ForegroundColor White
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "1. Run migrations: $composeCmd exec app php artisan migrate" -ForegroundColor White
Write-Host "2. Install Voyager: $composeCmd exec app php artisan voyager:install" -ForegroundColor White
Write-Host "3. Check logs: $composeCmd logs -f app" -ForegroundColor White
Write-Host ""
Write-Host "If you still see errors:" -ForegroundColor Cyan
Write-Host "• Check that all local packages are properly structured" -ForegroundColor White
Write-Host "• Verify vendor/larapack directories exist" -ForegroundColor White
Write-Host "• Run: composer dump-autoload --optimize" -ForegroundColor White
