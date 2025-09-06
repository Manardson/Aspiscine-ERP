# PowerShell script to safely run tests with isolated test database
# This ensures tests never affect production data

Write-Host "=== Running Tests Safely ===" -ForegroundColor Cyan

# Ensure test database exists and is up to date
Write-Host "Ensuring test database is ready..." -ForegroundColor Yellow
docker compose exec -T db mysql -u root -proot_password -e "CREATE DATABASE IF NOT EXISTS aspiscine_erp_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>$null
docker compose exec -T db mysql -u root -proot_password -e "GRANT ALL PRIVILEGES ON aspiscine_erp_test.* TO 'aspiscine_user'@'%';" 2>$null
docker compose exec -T db mysql -u root -proot_password -e "FLUSH PRIVILEGES;" 2>$null

# Refresh test database structure from production
Write-Host "Refreshing test database structure..." -ForegroundColor Yellow
docker compose exec -T db mysqldump -u root -proot_password --no-data --routines --triggers aspiscine_erp 2>$null | docker compose exec -T db mysql -u root -proot_password aspiscine_erp_test 2>$null
Write-Host "✓ Test database ready" -ForegroundColor Green

# Run tests
Write-Host "Running tests..." -ForegroundColor Yellow
if ($args.Count -eq 0) {
    # No arguments, run all tests
    Write-Host "Running all tests..." -ForegroundColor Cyan
    docker compose exec app php artisan test
} else {
    # Run specific test files/classes passed as arguments
    Write-Host "Running specific tests: $args" -ForegroundColor Cyan
    docker compose exec app php artisan test @args
}

$testExitCode = $LASTEXITCODE

if ($testExitCode -eq 0) {
    Write-Host "✓ All tests passed!" -ForegroundColor Green
} else {
    Write-Host "✗ Some tests failed" -ForegroundColor Red
}

Write-Host ""
Write-Host "Test database info:" -ForegroundColor Cyan
Write-Host "• Database: aspiscine_erp_test" -ForegroundColor Cyan
Write-Host "• Production database (aspiscine_erp) was NOT affected" -ForegroundColor Cyan
Write-Host ""
Write-Host "To run tests manually:" -ForegroundColor Yellow
Write-Host "docker compose exec app php artisan test" -ForegroundColor Cyan
Write-Host "docker compose exec app php artisan test tests/Unit/DpdAddressTransformationTest.php" -ForegroundColor Cyan
Write-Host "docker compose exec app php artisan test tests/Unit/DpdAwbResponseTest.php" -ForegroundColor Cyan

exit $testExitCode
