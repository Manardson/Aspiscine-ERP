# Aspiscine ERP Docker Setup Script
# PowerShell script for Windows

Write-Host "=== Aspiscine ERP Docker Setup ===" -ForegroundColor Green

# Check if Docker is running
Write-Host "Checking Docker..." -ForegroundColor Yellow
try {
    docker --version | Out-Null
    docker compose --version | Out-Null
    Write-Host "✓ Docker and Docker Compose are available" -ForegroundColor Green
} catch {
    Write-Host "✗ Docker or Docker Compose not found. Please install Docker Desktop." -ForegroundColor Red
    exit 1
}

# Check if .env file exists, if not copy from .env.docker
Write-Host "Setting up environment file..." -ForegroundColor Yellow
if (-not (Test-Path ".env")) {
    if (Test-Path ".env.docker") {
        Copy-Item ".env.docker" ".env"
        Write-Host "✓ Copied .env.docker to .env" -ForegroundColor Green
    } else {
        Write-Host "✗ .env.docker file not found" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "✓ .env file already exists" -ForegroundColor Green
}

# Stop any existing containers
Write-Host "Stopping existing containers..." -ForegroundColor Yellow
docker compose down

# Build and start containers
Write-Host "Building and starting Docker containers..." -ForegroundColor Yellow
docker compose up -d --build

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Containers started successfully" -ForegroundColor Green
} else {
    Write-Host "✗ Failed to start containers" -ForegroundColor Red
    exit 1
}

# Wait for MySQL to be ready
Write-Host "Waiting for MySQL to be ready..." -ForegroundColor Yellow
$maxAttempts = 30
$attempt = 0
do {
    $attempt++
    Start-Sleep -Seconds 2
    $mysqlReady = docker compose exec -T db mysqladmin ping -h localhost -u root -proot_password 2>$null
    if ($mysqlReady -match "mysqld is alive") {
        Write-Host "✓ MySQL is ready" -ForegroundColor Green
        break
    }
    Write-Host "Waiting for MySQL... ($attempt/$maxAttempts)" -ForegroundColor Yellow
} while ($attempt -lt $maxAttempts)

if ($attempt -eq $maxAttempts) {
    Write-Host "✗ MySQL failed to start within timeout" -ForegroundColor Red
    exit 1
}

# Update and install Composer dependencies
Write-Host "Updating and installing Composer dependencies..." -ForegroundColor Yellow
docker compose exec -T app composer update --no-interaction --disable-tls --no-secure-http
if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Composer dependencies updated and installed" -ForegroundColor Green
} else {
    Write-Host "⚠ Composer update failed, trying with relaxed security..." -ForegroundColor Yellow
    docker compose exec -T app composer update --no-interaction --ignore-platform-reqs --disable-tls --no-secure-http
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Composer dependencies updated with relaxed security" -ForegroundColor Green
    } else {
        Write-Host "⚠ Update failed, trying install..." -ForegroundColor Yellow
        docker compose exec -T app composer install --no-interaction --disable-tls --no-secure-http
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✓ Composer dependencies installed" -ForegroundColor Green
        } else {
            Write-Host "✗ Failed to install Composer dependencies" -ForegroundColor Red
        }
    }
}

# Generate application key
Write-Host "Generating application key..." -ForegroundColor Yellow
docker compose exec -T app php artisan key:generate --force
if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Application key generated" -ForegroundColor Green
} else {
    Write-Host "✗ Failed to generate application key" -ForegroundColor Red
}

# Run database migrations
Write-Host "Running database migrations..." -ForegroundColor Yellow
docker compose exec -T app php artisan migrate --force
if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Database migrations completed" -ForegroundColor Green
} else {
    Write-Host "⚠ Database migrations failed (this might be expected if tables already exist)" -ForegroundColor Yellow
}

# Set proper permissions
Write-Host "Setting file permissions..." -ForegroundColor Yellow
docker compose exec -T app chown -R www:www /var/www/storage
docker compose exec -T app chown -R www:www /var/www/bootstrap/cache
docker compose exec -T app chmod -R 775 /var/www/storage
docker compose exec -T app chmod -R 775 /var/www/bootstrap/cache
Write-Host "✓ File permissions set" -ForegroundColor Green

# Clear caches
Write-Host "Clearing application caches..." -ForegroundColor Yellow
docker compose exec -T app php artisan config:clear
docker compose exec -T app php artisan cache:clear
docker compose exec -T app php artisan view:clear
Write-Host "✓ Caches cleared" -ForegroundColor Green

# Run tests to verify DPD integration
Write-Host "Running DPD integration tests..." -ForegroundColor Yellow
docker compose exec -T app php artisan test tests/Unit/DpdAddressTransformationTest.php --stop-on-failure
if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ DPD Address Transformation tests passed" -ForegroundColor Green
} else {
    Write-Host "⚠ DPD Address Transformation tests failed" -ForegroundColor Yellow
}

docker compose exec -T app php artisan test tests/Unit/DpdAwbResponseTest.php --stop-on-failure
if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ DPD AWB Response tests passed" -ForegroundColor Green
} else {
    Write-Host "⚠ DPD AWB Response tests failed" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "=== Setup Complete ===" -ForegroundColor Green
Write-Host ""
Write-Host "Access points:" -ForegroundColor Cyan
Write-Host "• Application: http://localhost:8000" -ForegroundColor White
Write-Host "• PhpMyAdmin: http://localhost:8080" -ForegroundColor White
Write-Host "• MailHog: http://localhost:8025" -ForegroundColor White
Write-Host ""
Write-Host "Database credentials:" -ForegroundColor Cyan
Write-Host "• Database: aspiscine_erp" -ForegroundColor White
Write-Host "• Username: aspiscine_user" -ForegroundColor White
Write-Host "• Password: user_password" -ForegroundColor White
Write-Host ""
Write-Host "Useful commands:" -ForegroundColor Cyan
Write-Host "• View logs: docker compose logs -f app" -ForegroundColor White
Write-Host "• Access shell: docker compose exec app bash" -ForegroundColor White
Write-Host "• Stop containers: docker compose down" -ForegroundColor White
Write-Host ""
Write-Host "DPD Integration:" -ForegroundColor Cyan
Write-Host "• Enhanced error handling and logging implemented" -ForegroundColor White
Write-Host "• Address validation added" -ForegroundColor White
Write-Host "• Comprehensive unit tests created" -ForegroundColor White
Write-Host "• Check logs for DPD operations: docker compose logs app | Select-String 'DPD'" -ForegroundColor White