# Fix for larapack.io SSL certificate issue
# PowerShell script for Windows

Write-Host "=== Fixing Larapack.io SSL Issue ===" -ForegroundColor Green

# Backup original composer.json
Write-Host "Creating backup of composer.json..." -ForegroundColor Yellow
Copy-Item "composer.json" "composer.json.backup" -Force
Write-Host "✓ Backup created as composer.json.backup" -ForegroundColor Green

# Read composer.json content
$composerContent = Get-Content "composer.json" -Raw | ConvertFrom-Json

# Remove the problematic larapack repository
if ($composerContent.repositories -and $composerContent.repositories.hooks) {
    Write-Host "Removing larapack.io repository temporarily..." -ForegroundColor Yellow
    $composerContent.repositories.PSObject.Properties.Remove('hooks')
    Write-Host "✓ Larapack.io repository removed" -ForegroundColor Green
} else {
    Write-Host "✓ Larapack.io repository not found in composer.json" -ForegroundColor Green
}

# Save modified composer.json
$composerContent | ConvertTo-Json -Depth 10 | Set-Content "composer.json"
Write-Host "✓ Modified composer.json saved" -ForegroundColor Green

# Remove composer.lock to force fresh resolution
if (Test-Path "composer.lock") {
    Remove-Item "composer.lock" -Force
    Write-Host "✓ Removed composer.lock" -ForegroundColor Green
}

# Check if containers are running
Write-Host "Checking if containers are running..." -ForegroundColor Yellow
$containers = docker compose ps -q
if (-not $containers) {
    Write-Host "Starting containers..." -ForegroundColor Yellow
    docker compose up -d
    Start-Sleep -Seconds 10
}

# Clear composer cache
Write-Host "Clearing composer cache..." -ForegroundColor Yellow
docker compose exec -T app composer clear-cache
Write-Host "✓ Composer cache cleared" -ForegroundColor Green

# Update composer dependencies
Write-Host "Updating composer dependencies..." -ForegroundColor Yellow
docker compose exec -T app composer update --no-interaction
if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Composer dependencies updated successfully" -ForegroundColor Green
} else {
    Write-Host "⚠ Composer update failed, trying install..." -ForegroundColor Yellow
    docker compose exec -T app composer install --no-interaction
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Composer dependencies installed" -ForegroundColor Green
    } else {
        Write-Host "✗ Failed to install Composer dependencies" -ForegroundColor Red
        Write-Host "Restoring original composer.json..." -ForegroundColor Yellow
        Copy-Item "composer.json.backup" "composer.json" -Force
        exit 1
    }
}

# Set proper permissions
Write-Host "Setting file permissions..." -ForegroundColor Yellow
docker compose exec -T app chown -R www:www /var/www/storage
docker compose exec -T app chown -R www:www /var/www/bootstrap/cache
docker compose exec -T app chmod -R 775 /var/www/storage
docker compose exec -T app chmod -R 775 /var/www/bootstrap/cache
Write-Host "✓ File permissions set" -ForegroundColor Green

Write-Host ""
Write-Host "=== Larapack SSL Fix Complete ===" -ForegroundColor Green
Write-Host ""
Write-Host "Note: The larapack.io repository has been temporarily removed." -ForegroundColor Cyan
Write-Host "If you need packages from larapack.io, you may need to:" -ForegroundColor Cyan
Write-Host "1. Restore composer.json.backup" -ForegroundColor White
Write-Host "2. Configure SSL certificates properly" -ForegroundColor White
Write-Host "3. Or use alternative package sources" -ForegroundColor White
Write-Host ""
Write-Host "Application should now be accessible at:" -ForegroundColor Cyan
Write-Host "• http://localhost:8000" -ForegroundColor White
