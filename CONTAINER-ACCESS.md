# Container Access Guide

## Understanding Container vs Host System

The `RUN echo 'root:root' | chpasswd` line in the Dockerfile sets the root password **inside the Docker container**, not on your host system (Windows/Linux/Mac).

## How to Access Containers

### 1. Access Container as Regular User (www)
```bash
# Using docker-compose
docker-compose exec app bash

# Using docker compose (newer syntax)
docker compose exec app bash
```

### 2. Access Container as Root User
```bash
# Using docker-compose
docker-compose exec --user root app bash

# Using docker compose (newer syntax)
docker compose exec --user root app bash
```

### 3. Root Password Inside Container
- **Username**: `root`
- **Password**: `root`
- This only works **inside the container**, not on your host system

### 4. Switch to Root Inside Container
If you're already inside the container as the `www` user:
```bash
# Switch to root (will ask for password: root)
su root

# Or use sudo if available
sudo su
```

## Common Container Commands

### Check Running Containers
```bash
# List running containers
docker-compose ps
# or
docker compose ps
```

### View Container Logs
```bash
# View app container logs
docker-compose logs app
# or
docker compose logs app

# Follow logs in real-time
docker-compose logs -f app
# or
docker compose logs -f app
```

### Execute Commands in Container
```bash
# Run Laravel commands
docker-compose exec app php artisan migrate
docker-compose exec app php artisan cache:clear

# Or with newer syntax
docker compose exec app php artisan migrate
docker compose exec app php artisan cache:clear
```

### File Permissions Inside Container
```bash
# Fix storage permissions
docker-compose exec app chown -R www:www /var/www/storage
docker-compose exec app chmod -R 775 /var/www/storage

# Or as root
docker-compose exec --user root app chown -R www:www /var/www/storage
```

## Troubleshooting Access Issues

### If "docker: command not found"
1. **Install Docker Desktop**: https://www.docker.com/products/docker-desktop
2. **Restart your terminal** after installation
3. **Check PATH**: Make sure Docker is in your system PATH

### If Docker Compose Commands Fail
Try both syntaxes:
```bash
# Old syntax
docker-compose exec app bash

# New syntax (Docker Compose V2)
docker compose exec app bash
```

### If Container Won't Start
```bash
# Check container status
docker-compose ps

# View error logs
docker-compose logs app

# Rebuild containers
docker-compose down
docker-compose up -d --build
```

## Quick Setup Commands

### Complete Setup (Run from project root)
```bash
# Windows
.\troubleshoot-setup.ps1

# Linux/Mac
chmod +x troubleshoot-setup.sh
./troubleshoot-setup.sh
```

### Manual Setup Steps
```bash
# 1. Start containers
docker-compose up -d --build

# 2. Install dependencies
docker-compose exec app composer install

# 3. Generate app key
docker-compose exec app php artisan key:generate

# 4. Run migrations
docker-compose exec app php artisan migrate

# 5. Set permissions
docker-compose exec app chown -R www:www /var/www/storage
docker-compose exec app chmod -R 775 /var/www/storage
```

## Important Notes

- **Container root ≠ Host root**: The root password only works inside the Docker container
- **File changes**: Files you edit on your host system are reflected in the container (via volume mounts)
- **Database**: The MySQL database runs in a separate container with its own credentials
- **Persistence**: Data in volumes (database, storage) persists between container restarts

## Access Points After Setup

- **Application**: http://localhost:8000
- **PhpMyAdmin**: http://localhost:8080 (admin interface for database)
- **MailHog**: http://localhost:8025 (email testing interface)

## Database Access

### From Host System
- **Host**: localhost
- **Port**: 3306
- **Database**: aspiscine_erp
- **Username**: aspiscine_user
- **Password**: user_password
- **Root Password**: root_password

### From Inside App Container
- **Host**: db (container name)
- **Port**: 3306
- **Database**: aspiscine_erp
- **Username**: aspiscine_user
- **Password**: user_password
