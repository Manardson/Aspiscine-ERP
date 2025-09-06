# Testing Setup

This project now has a **safe testing environment** that uses a separate test database to ensure your production data is never affected by tests.

## 🔒 Safety Features

- **Separate Test Database**: Tests run against `aspiscine_erp_test` database
- **Production Data Protected**: Your `aspiscine_erp` database is never touched during tests
- **Automatic Setup**: Test database is automatically created and maintained
- **Fresh Structure**: Test database structure is copied from production before each test run

## 🚀 Quick Start

### Run All Tests
```bash
docker compose exec app php artisan test
```

### Run Specific Test Classes
```bash
# DPD Address Transformation Tests
docker compose exec app php artisan test tests/Unit/DpdAddressTransformationTest.php

# DPD AWB Response Tests  
docker compose exec app php artisan test tests/Unit/DpdAwbResponseTest.php
```

### Using the Test Scripts

#### Linux/Mac/WSL
```bash
bash run-tests.sh
bash run-tests.sh tests/Unit/DpdAddressTransformationTest.php
```

#### Windows PowerShell
```powershell
powershell -ExecutionPolicy Bypass -File run-tests.ps1
powershell -ExecutionPolicy Bypass -File run-tests.ps1 tests/Unit/DpdAddressTransformationTest.php
```

## 📊 Database Information

### Production Database
- **Name**: `aspiscine_erp`
- **Status**: ✅ **PROTECTED** - Never affected by tests
- **Access**: Normal application operations

### Test Database  
- **Name**: `aspiscine_erp_test`
- **Status**: 🧪 Used only for testing
- **Structure**: Automatically synced from production
- **Data**: Fresh/empty for each test run

## 🔧 Configuration

The test setup is configured in:

- **`phpunit.xml`**: Test environment configuration
- **`config/database.php`**: Database connections (includes `mysql_testing`)
- **Test Classes**: Use `RefreshDatabase` trait safely

## 🛠️ Manual Test Database Setup

If you need to manually set up the test database:

```bash
# Create test database
docker compose exec -T db mysql -u root -proot_password -e "CREATE DATABASE IF NOT EXISTS aspiscine_erp_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Grant permissions
docker compose exec -T db mysql -u root -proot_password -e "GRANT ALL PRIVILEGES ON aspiscine_erp_test.* TO 'aspiscine_user'@'%';"

# Copy structure from production
docker compose exec -T db mysqldump -u root -proot_password --no-data --routines --triggers aspiscine_erp | docker compose exec -T db mysql -u root -proot_password aspiscine_erp_test
```

## ✅ Test Coverage

Current test suites:

### DPD Integration Tests
- **DpdAddressTransformationTest**: Tests address handling and AWB generation
- **DpdAwbResponseTest**: Tests API response processing

All tests are designed to:
- Run in isolation
- Not affect production data  
- Provide comprehensive coverage
- Be easily maintainable

## 🚨 Important Notes

1. **Always use the test database**: Tests automatically use `aspiscine_erp_test`
2. **Production is safe**: Your production database is never touched
3. **Fresh data**: Each test run starts with a clean database
4. **Structure sync**: Test database structure is kept in sync with production

## 🔍 Troubleshooting

### Test Database Issues
```bash
# Check if test database exists
docker compose exec -T db mysql -u root -proot_password -e "SHOW DATABASES LIKE 'aspiscine_erp_test';"

# Recreate test database
docker compose exec -T db mysql -u root -proot_password -e "DROP DATABASE IF EXISTS aspiscine_erp_test;"
bash setup-test-db.sh
```

### Permission Issues
```bash
# Reset permissions
docker compose exec -T db mysql -u root -proot_password -e "GRANT ALL PRIVILEGES ON aspiscine_erp_test.* TO 'aspiscine_user'@'%'; FLUSH PRIVILEGES;"
```
