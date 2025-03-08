# Translation Management Service

A high-performance API service for managing translations across multiple locales with tagging capabilities.

## Features

- Multi-locale translation management (en, fr, es, etc.)
- Tag-based organization (mobile, web, desktop)
- CRUD operations for translations
- Search by tags, keys, or content
- JSON export for frontend consumption
- Secure authentication
- Optimized for performance with Redis caching

## Requirements

- PHP 8.2+
- MySQL 8.0+
- Redis 6.0+ (for caching)
- Docker (optional, for containerized setup)

## Quick Start

### Using Docker

```bash
# Clone the repository
git clone https://github.com/yourusername/translation-management-service.git
cd translation-management-service

# Configure environment
cp .env.example .env

# Start containers
docker-compose up -d

# Generate test data (optional)
docker-compose exec app php artisan translations:generate 1000

## API Endpoints

### Authentication

- **Register**: `POST /api/register`
- **Login**: `POST /api/login`
- **Logout**: `POST /api/logout` (requires authentication)

### Translations

- **List**: `GET /api/translations`
- **Create**: `POST /api/translations`
- **View**: `GET /api/translations/{id}`
- **Update**: `PUT /api/translations/{id}`
- **Delete**: `DELETE /api/translations/{id}`

### Search

- **By Tag**: `GET /api/translations/search/tags/{tag}`
- **By Key**: `GET /api/translations/search/keys/{key}`
- **By Content**: `GET /api/translations/search/content/{content}`

### Export

- **Export**: `GET /api/translations/export/{locale?}`

## Testing

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run specific tests
php artisan test --filter=AuthTest
php artisan test --filter=TranslationTest
```

## Performance

The service is optimized for high performance:

- Response time under 200ms for all endpoints
- Efficient database indexing
- Caching for frequently accessed data
- Pagination for large datasets

## Security

- Token-based authentication with Laravel Sanctum
- Input validation for all endpoints
- Secure password handling
- Protected routes requiring authentication
