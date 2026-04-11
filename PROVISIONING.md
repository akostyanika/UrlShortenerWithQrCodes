# URL Shortener Service - Provisioning & Deployment Guide

This guide provides step-by-step instructions for deploying the URL Shortener Service using Docker and Docker Compose.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Project Structure](#project-structure)
3. [Environment Configuration](#environment-configuration)
4. [Building and Running Containers](#building-and-running-containers)
5. [Database Setup](#database-setup)
6. [Verifying Deployment](#verifying-deployment)
7. [Troubleshooting](#troubleshooting)

---

## Prerequisites

Before deploying the URL Shortener Service, ensure you have the following installed:

### Required Software

- **Docker**: Version 20.10 or higher
- **Docker Compose**: Version 2.0 or higher
- **Git**: For cloning the repository (optional)

### System Requirements

- **Operating System**: Linux, macOS, or Windows with WSL2
- **RAM**: Minimum 2GB available
- **Disk Space**: Minimum 5GB available
- **Ports**: Ports 8080 (HTTP) should be available

### Checking Prerequisites

```bash
# Check Docker version
docker --version

# Check Docker Compose version
docker compose --version

# Verify Docker is running
docker ps
```

---

## Project Structure

The project is organized as follows:

```
url-shortener/
├── docker-compose.yml          # Docker Compose configuration
├── Dockerfile                  # PHP-FPM container definition
├── nginx/
│   └── nginx.conf              # Nginx configuration
├── backend/
│   ├── composer.json            # PHP dependencies
│   ├── config/                 # Yii2 configuration
│   │   ├── db.php             # Database configuration
│   │   ├── web.php            # Web application config
│   │   └── params.php         # Application parameters
│   ├── controllers/            # API controllers
│   │   ├── ApiController.php  # Shorten URL endpoints
│   │   └── RedirectController.php # Redirection handler
│   ├── services/              # Business logic services
│   │   ├── interfaces/        # Service interfaces
│   │   ├── UrlValidationService.php
│   │   ├── ShortenerService.php
│   │   └── QrService.php
│   ├── models/                # ActiveRecord models
│   │   └── Link.php
│   ├── migrations/           # Database migrations
│   │   └── m230301_000001_create_links_table.php
│   └── web/
│       └── index.php          # Application entry point
└── frontend/
    ├── index.html             # SPA frontend
    └── app.js                 # Frontend JavaScript
```

---

## Environment Configuration

### Environment Variables

Create a `.env` file in the project root with the following variables:

```bash
# Database Configuration
DB_ROOT_PASSWORD=root_secret_pass
DB_NAME=urlshortener
DB_USER=app_user
DB_PASSWORD=app_secret_pass

# Application Configuration
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8080
HTTP_PORT=8080
```

### Using Default Values

If you don't create an `.env` file, the application will use the following defaults:

- Database Name: `urlshortener`
- Database User: `app_user`
- Database Password: `app_secret_pass`
- HTTP Port: `8080`

---

## Building and Running Containers

### Step 1: Navigate to Project Directory

```bash
cd url-shortener
```

### Step 2: Build Docker Images

Build the PHP backend container:

```bash
docker compose build
```

This will:
- Pull the PHP 8.5 FPM Alpine base image
- Install system dependencies
- Install PHP extensions
- Install Composer and PHP dependencies

### Step 3: Start the Containers

Start all services in detached mode:

```bash
docker compose up -d
```

### Step 4: Verify Container Status

Check that all containers are running:

```bash
docker compose ps
```

Expected output:

```
NAME                   IMAGE               COMMAND              STATUS
urlshortener_db        mariadb:10.11       "docker-entrypoint…"  Up (healthy)
urlshortener_backend   urlshortener_app    "/usr/bin/supervis…"  Up
urlshortener_webserver nginx:1.25-alpine   "/docker-entrypoint…" Up (healthy)
```

---

## Database Setup

### Step 1: Wait for Database Initialization

The database container needs time to initialize. Wait for it to be healthy:

```bash
docker compose logs db | grep "ready for connections"
```

### Step 2: Run Database Migrations

Execute the database migration to create the required tables:

```bash
# Run migrations inside the backend container
docker compose exec backend php /var/www/html/yii migrate --no-interactive
```

Expected output:

```
Yii Migration Tool (based on Yii v2.0.49)

Creating migration history table "migration" ...
Total new migrations: 1
    > m230301_000001_create_links_table ... done
Total applied migrations: 1
```

---

## Verifying Deployment

### Step 1: Test API Endpoint

Test the shorten URL API:

```bash
curl -X POST http://localhost:8080/api/shorten \
  -H "Content-Type: application/json" \
  -d '{"url":"https://www.google.com"}'
```

Expected response:

```json
{
  "status": "success",
  "code": 200,
  "data": {
    "short_code": "Ab3d9X",
    "short_url": "http://localhost:8080/Ab3d9X",
    "original_url": "https://www.google.com",
    "qr_code": "data:image/png;base64,...",
    "created_at": "2024-01-01 12:00:00"
  }
}
```

### Step 2: Test Redirection

Test the short URL redirection:

```bash
# Replace with your actual short code
curl -I http://localhost:8080/Ab3d9X
```

Expected response includes:

```
HTTP/1.1 301 Moved Permanently
Location: https://www.google.com
```

### Step 3: Access Web Interface

Open your browser and navigate to:

```
http://localhost:8080
```

You should see the URL Shortener interface.

### Step 4: Check Logs

View application logs:

```bash
# Backend logs
docker compose logs backend

# Nginx logs
docker compose logs webserver

# Database logs
docker compose logs db
```

---

## Troubleshooting

### Container Issues

#### Container Won't Start

Check container logs:

```bash
docker compose logs <container_name>
```

Common solutions:

- Ensure ports are not in use
- Check database credentials in `.env` file
- Verify volume permissions

#### Database Connection Failed

1. Check if database container is healthy:

```bash
docker compose ps db
```

2. Check database logs:

```bash
docker compose logs db
```

3. Verify connection settings in `backend/config/db.php`

#### Permission Denied Errors

Fix permissions:

```bash
docker compose exec backend chown -R www-data:www-data /var/www/html/runtime
```

### Application Issues

#### 404 on API Requests

Check Nginx configuration is being mounted correctly:

```bash
docker compose exec webserver cat /etc/nginx/nginx.conf
```

#### QR Code Not Generating

Ensure the Endroid QR Code library is installed:

```bash
docker compose exec backend composer show endroid/qr-code
```

#### Migration Failed

If migration fails, check database connection:

```bash
docker compose exec backend php /var/www/html/yii migrate --show-errors
```

### Network Issues

#### Can't Access Application

1. Check if port 8080 is available:

```bash
netstat -tlnp | grep 8080
```

2. Check Docker network:

```bash
docker network ls
docker network inspect urlshortener_app_network
```

---

## Stopping and Cleaning Up

### Stop Containers

```bash
docker compose stop
```

### Remove Containers

```bash
docker compose down
```

### Remove Volumes (Database Data)

```bash
docker compose down -v
```

### Full Clean Rebuild

```bash
# Stop and remove everything
docker compose down -v

# Rebuild containers
docker compose build --no-cache

# Start services
docker compose up -d
```

---

## Production Considerations

### Security Improvements

1. **Change Default Credentials**: Update all default passwords in `.env`
2. **Enable HTTPS**: Configure SSL/TLS certificates
3. **Environment**: Set `APP_ENV=production` and `APP_DEBUG=false`
4. **Firewall**: Restrict database port access

### Performance Tuning

1. **PHP-FPM Workers**: Adjust `pm.max_children` in `docker/supervisord.conf`
2. **Nginx Caching**: Add caching for static assets
3. **Database Connection Pooling**: Use pgsql connection pooling
4. **QR Code Caching**: Consider caching generated QR codes

### Backup Strategy

Regular backups of the database volume:

```bash
# Backup database
docker compose exec db mysqldump -u root -p urlshortener > backup.sql

# Restore database
docker compose exec -T db mysql -u root -p urlshortener < backup.sql
```

---

## Support

For issues and questions:

1. Check the logs: `docker compose logs`
2. Review the troubleshooting section
3. Check Docker and system logs

---

**End of Provisioning Guide**
