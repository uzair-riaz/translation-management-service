# Translation Management Service

A high-performance API service for managing translations across multiple locales with tagging capabilities.

## 📋 Overview

This service provides a robust API for managing translations across different languages with organizational tagging features, designed for high performance and scalability.

## ✨ Features

- **Multi-locale Support**: Manage translations in multiple languages (en, fr, es, etc.)
- **Tag Organization**: Categorize translations with tags (mobile, web, desktop)
- **Complete CRUD Operations**: Create, read, update, and delete translations
- **Advanced Search**: Find translations by tags, keys, or content
- **Export Functionality**: JSON export for frontend consumption
- **Security**: Secure authentication and protected routes
- **Performance**: Optimized with Redis caching for fast response times

## 🔧 Technical Requirements

- PHP 8.2+
- MySQL 8.0+
- Redis 6.0+ (for caching)
- Docker (optional, for containerized setup)

## 🚀 Quick Start

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
```

## 🔌 API Endpoints

### Authentication

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/register` | POST | Register a new user |
| `/api/login` | POST | Login to the service |
| `/api/logout` | POST | Logout (requires authentication) |

### Translations

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/translations` | GET | List all translations |
| `/api/translations` | POST | Create a new translation |
| `/api/translations/{id}` | GET | View a specific translation |
| `/api/translations/{id}` | PUT | Update a translation |
| `/api/translations/{id}` | DELETE | Delete a translation |

### Search

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/translations/search/tags/{tag}` | GET | Search by tag |
| `/api/translations/search/keys/{key}` | GET | Search by key |
| `/api/translations/search/content/{content}` | GET | Search by content |

### Export

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/translations/export/{locale?}` | GET | Export translations (optionally by locale) |

## 🧪 Testing

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

## ⚡ Performance

The service is optimized for high performance:

- Response time under 200ms for all endpoints
- Efficient database indexing
- Caching for frequently accessed data
- Pagination for large datasets

## 🔒 Security

- Token-based authentication with Laravel Sanctum
- Input validation for all endpoints
- Secure password handling
- Protected routes requiring authentication
