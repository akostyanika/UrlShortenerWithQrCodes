# URL Shortener with QR Code Generator

## Project Overview

A full-stack URL shortening service with QR code generation capabilities. The application allows users to convert long URLs into short, shareable links and automatically generates QR codes for each shortened URL.

### Architecture

**Backend:**
- **Framework:** Yii2 (PHP 8.5)
- **Database:** MariaDB 10.11
- **QR Code Library:** Endroid QR Code v5.0
- **Architecture Pattern:** MVC with Service Layer (SOLID principles)

**Frontend:**
- **Type:** Single Page Application (SPA)
- **Framework:** Bootstrap 5 + Vanilla JavaScript
- **Styling:** Custom CSS with CSS variables

**Infrastructure:**
- **Web Server:** Nginx 1.25 (Alpine)
- **Containerization:** Docker + Docker Compose
- **Process Manager:** Supervisor

### Project Structure

```
url-shortener-with-qr/v0.4-minimax/
├── docker-compose.yml          # Docker Compose orchestration
├── Dockerfile                  # PHP-FPM backend image
├── PROVISIONING.md             # Deployment guide
├── backend/                    # Yii2 REST API
│   ├── composer.json           # PHP dependencies
│   ├── config/                 # Yii2 configurations
│   │   ├── db.php             # Database connection
│   │   ├── web.php            # Web app config + DI container
│   │   └── params.php         # App parameters
│   ├── controllers/            # API controllers
│   │   ├── ApiController.php  # /api/shorten, /api/info
│   │   └── RedirectController.php # Short URL redirect
│   ├── services/               # Business logic layer
│   │   ├── interfaces/        # Service contracts
│   │   ├── UrlValidationService.php
│   │   ├── ShortenerService.php
│   │   └── QrService.php
│   ├── models/                 # ActiveRecord models
│   │   └── Link.php           # Link entity
│   ├── migrations/             # Database migrations
│   └── web/
│       └── index.php           # Application entry point
├── frontend/                   # SPA frontend
│   ├── index.html              # Main page
│   └── app.js                  # Client-side logic
├── frontend-preview/           # Alternative frontend (if applicable)
├── nginx/
│   └── nginx.conf              # Nginx configuration
└── docker/
    └── supervisord.conf        # PHP-FPM process config
```

## Key Features

- **URL Shortening:** Generates 6-character alphanumeric short codes
- **QR Code Generation:** Base64-encoded PNG QR codes via Endroid library
- **URL Validation:** Syntax + optional accessibility checking
- **Click Tracking:** Records redirect clicks in database
- **Duplicate Detection:** Returns existing short code for duplicate URLs
- **Collision Handling:** Automatic retry with timestamp-based fallback
- **CORS Support:** Full cross-origin request handling
- **RESTful API:** JSON-based API with standardized responses

## API Endpoints

### POST `/api/shorten`
Shortens a URL and generates a QR code.

**Request:**
```json
{
  "url": "https://example.com/long-url"
}
```

**Response:**
```json
{
  "status": "success",
  "code": 200,
  "data": {
    "short_code": "Ab3d9X",
    "short_url": "http://localhost:8080/Ab3d9X",
    "original_url": "https://example.com/long-url",
    "qr_code": "data:image/png;base64,...",
    "created_at": "2024-01-01 12:00:00"
  }
}
```

### GET `/api/info?code=xxxxxx`
Retrieves information about a short URL.

**Response:**
```json
{
  "status": "success",
  "code": 200,
  "data": {
    "short_code": "Ab3d9X",
    "short_url": "http://localhost:8080/Ab3d9X",
    "original_url": "https://example.com/long-url"
  }
}
```

### GET `/{code}` (6 alphanumeric chars)
Redirects to the original URL (301).

## Building and Running

### Prerequisites
- Docker >= 20.10
- Docker Compose >= 2.0
- Available port: 8080 (configurable via `HTTP_PORT`)

### Quick Start

```bash
# 1. Build images
docker compose build

# 2. Start all services
docker compose up -d

# 3. Run database migrations
docker compose exec backend php /var/www/html/yii migrate --no-interactive

# 4. Access the application
# Frontend: http://localhost:8080
```

### Environment Variables

Create a `.env` file in the project root:

```bash
# Database
DB_ROOT_PASSWORD=root_secret_pass
DB_NAME=urlshortener
DB_USER=app_user
DB_PASSWORD=app_secret_pass

# Application
APP_ENV=production
APP_DEBUG=false
HTTP_PORT=8080
```

### Docker Services

| Service | Container Name | Port | Description |
|---------|---------------|------|-------------|
| db | urlshortener_db | 3306 (internal) | MariaDB 10.11 |
| backend | urlshortener_backend | 9000 (internal) | PHP-FPM + Yii2 |
| webserver | urlshortener_webserver | 8080 | Nginx |

### Common Commands

```bash
# View container status
docker compose ps

# View logs
docker compose logs backend
docker compose logs webserver
docker compose logs db

# Stop containers
docker compose stop

# Stop and remove containers
docker compose down

# Full clean rebuild
docker compose down -v && docker compose build --no-cache && docker compose up -d

# Run migrations
docker compose exec backend php /var/www/html/yii migrate --no-interactive
```

## Development Conventions

### Backend (PHP/Yii2)

- **PHP Version:** 8.5 (minimum 8.0)
- **Coding Standard:** PSR-4 autoloading, namespace `app\`
- **Architecture:** 
  - Controllers handle HTTP requests/responses
  - Services contain business logic (implementing interfaces)
  - Models are ActiveRecord entities
- **Dependency Injection:** Services injected via container definitions in `config/web.php`
- **Error Handling:** Try-catch with Yii logging, standardized API responses
- **Validation:** Service-layer validation with accessibility checking option

### Frontend

- **JavaScript:** Vanilla JS (no framework)
- **CSS:** Bootstrap 5 + custom CSS variables
- **Form Handling:** AJAX with fetch API
- **UI Feedback:** Loading spinners, toast notifications, error states

### Database

- **Migration Tool:** Yii2 migration system
- **Table:** `links` (id, original_url, short_code, clicks, created_at, updated_at)
- **Charset:** utf8mb4
- **Schema Cache:** Enabled (1 hour)

## Testing

```bash
# PHPUnit is available as dev dependency
docker compose exec backend vendor/bin/phpunit
```

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Database connection failed | Check `docker compose ps db` and ensure health status is "healthy" |
| 404 on API requests | Verify Nginx config is mounted correctly |
| Permission denied on runtime | `docker compose exec backend chown -R www-data:www-data /var/www/html/runtime` |
| QR code not generating | Check `composer show endroid/qr-code` inside backend container |
| Port 8080 in use | Set `HTTP_PORT` in `.env` to different value |

## Tech Stack Summary

| Layer | Technology | Version |
|-------|-----------|---------|
| Runtime | PHP | 8.5 |
| Framework | Yii2 | 2.0.49+ |
| Database | MariaDB | 10.11 |
| Web Server | Nginx | 1.25 (Alpine) |
| QR Codes | Endroid QR Code | 5.0 |
| Frontend CSS | Bootstrap | 5.3.2 |
| Frontend Icons | Bootstrap Icons | 1.11.1 |
| JS Library | jQuery | 3.7.1 |
