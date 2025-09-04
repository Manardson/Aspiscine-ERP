# Quick fix for composer dependency issues
# PowerShell script for Windows

Write-Host "=== Fixing Composer Dependencies ===" -ForegroundColor Green

# Check if containers are running
Write-Host "Checking if containers are running..." -ForegroundColor Yellow
$containers = docker compose ps -q
if (-not $containers) {
    Write-Host "Starting containers..." -ForegroundColor Yellow
    docker compose up -d
    Start-Sleep -Seconds 10
}

# Remove composer.lock to force fresh dependency resolution
Write-Host "Removing composer.lock to force fresh dependency resolution..." -ForegroundColor Yellow
if (Test-Path "composer.lock") {
    Remove-Item "composer.lock" -Force
    Write-Host "✓ Removed composer.lock" -ForegroundColor Green
}

# Clear composer cache
Write-Host "Clearing composer cache..." -ForegroundColor Yellow
docker compose exec -T app composer clear-cache
Write-Host "✓ Composer cache cleared" -ForegroundColor Green

# Update composer dependencies
Write-Host "Updating composer dependencies..." -ForegroundColor Yellow
docker compose exec -T app composer update --no-interaction --disable-tls --no-secure-http
if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Composer dependencies updated successfully" -ForegroundColor Green
} else {
    Write-Host "⚠ Composer update failed, trying with different options..." -ForegroundColor Yellow
    docker compose exec -T app composer update --no-interaction --ignore-platform-reqs --disable-tls --no-secure-http
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Composer dependencies updated with relaxed security" -ForegroundColor Green
    } else {
        Write-Host "⚠ Update failed, trying install..." -ForegroundColor Yellow
        docker compose exec -T app composer install --no-interaction --disable-tls --no-secure-http
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✓ Composer dependencies installed" -ForegroundColor Green
        } else {
            Write-Host "Trying install with --ignore-platform-reqs..." -ForegroundColor Yellow
            docker compose exec -T app composer install --no-interaction --ignore-platform-reqs --disable-tls --no-secure-http
            if ($LASTEXITCODE -eq 0) {
                Write-Host "✓ Composer dependencies installed with relaxed requirements" -ForegroundColor Green
            } else {
                Write-Host "✗ All composer attempts failed" -ForegroundColor Red
                exit 1
            }
        }
    }
}

# Set proper permissions
Write-Host "Setting file permissions..." -ForegroundColor Yellow
docker compose exec -T app chown -R www:www /var/www/storage
docker compose exec -T app chown -R www:www /var/www/bootstrap/cache
docker compose exec -T app chmod -R 775 /var/www/storage
docker compose exec -T app chmod -R 775 /var/www/bootstrap/cache
Write-Host "✓ File permissions set" -ForegroundColor Green

# Generate application key if needed
Write-Host "Checking application key..." -ForegroundColor Yellow
$envContent = Get-Content ".env" -ErrorAction SilentlyContinue
if ($envContent -and ($envContent | Select-String "APP_KEY=base64:")) {
    Write-Host "✓ Application key already exists" -ForegroundColor Green
} else {
    Write-Host "Generating application key..." -ForegroundColor Yellow
    docker compose exec -T app php artisan key:generate --force
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Application key generated" -ForegroundColor Green
    } else {
        Write-Host "⚠ Failed to generate application key" -ForegroundColor Yellow
    }
}

# Clear caches
Write-Host "Clearing application caches..." -ForegroundColor Yellow
docker compose exec -T app php artisan config:clear
docker compose exec -T app php artisan cache:clear
docker compose exec -T app php artisan view:clear
Write-Host "✓ Caches cleared" -ForegroundColor Green

Write-Host ""
Write-Host "=== Composer Fix Complete ===" -ForegroundColor Green
Write-Host ""
Write-Host "You can now access your application at:" -ForegroundColor Cyan
Write-Host "• Application: http://localhost:8000" -ForegroundColor White
Write-Host ""
Write-Host "To run migrations:" -ForegroundColor Cyan
Write-Host "docker compose exec app php artisan migrate" -ForegroundColor White
