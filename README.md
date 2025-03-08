# Translation Management Service

A high-performance API-driven service for managing translations across multiple locales with tagging capabilities.

## Features

- Store translations for multiple locales (e.g., en, fr, es)
- Tag translations for context (e.g., mobile, desktop, web)
- Create, update, view, and search translations by tags, keys, or content
- JSON export endpoint for frontend applications
- Token-based authentication
- High-performance optimized for large datasets
- Docker setup for easy deployment
- CDN support for global content delivery

## Performance Optimizations

This service is optimized for high performance with large datasets:

- **Response Time**: All endpoints are optimized to respond in under 200ms
- **Database Indexes**: Strategic indexes on frequently queried columns
- **Fulltext Search**: MySQL fulltext indexing for fast content searches
- **Caching**: Multi-level caching strategy with automatic cache invalidation
- **Cursor Pagination**: Memory-efficient pagination for large datasets
- **Eager Loading**: Automatic eager loading of relationships to prevent N+1 queries
- **Batch Processing**: Efficient batch operations for bulk data handling

## Performance Testing

The service includes tools for performance testing with large datasets:

```bash
# Generate 100,000 test translations across multiple locales
php artisan translations:generate 100000

# Generate 500,000 translations with specific locales
php artisan translations:generate 500000 --locales=en,fr,es,de,it,ja

# Run performance tests
php artisan test --filter=PerformanceTest
```

## Requirements

- PHP 8.2+
- MySQL 8.0+
- Redis (optional, for improved caching)
- Docker and Docker Compose (for containerized setup)

## Installation

### Using Docker

1. Clone the repository:
   ```
   git clone https://github.com/yourusername/translation-management-service.git
   cd translation-management-service
   ```

2. Copy the environment file:
   ```
   cp .env.example .env
   ```

3. Update the environment variables in the `.env` file:
   ```
   DB_CONNECTION=mysql
   DB_HOST=db
   DB_PORT=3306
   DB_DATABASE=translations
   DB_USERNAME=root
   DB_PASSWORD=root
   
   CACHE_DRIVER=redis
   REDIS_HOST=redis
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   ```

4. Build and start the Docker containers:
   ```
   docker-compose up -d
   ```

That's it! The Dockerfile automatically handles dependency installation, key generation, and database migrations.

5. (Optional) Generate test data:
   ```
   docker-compose exec app php artisan translations:generate 100000
   ```

### Manual Installation

1. Clone the repository:
   ```
   git clone https://github.com/yourusername/translation-management-service.git
   cd translation-management-service
   ```

2. Copy the environment file:
   ```
   cp .env.example .env
   ```

3. Update the environment variables in the `.env` file.

4. Install dependencies:
   ```
   composer install
   ```

5. Generate application key:
   ```
   php artisan key:generate
   ```

6. Run migrations:
   ```
   php artisan migrate
   ```

7. (Optional) Generate test data:
   ```
   php artisan translations:generate 100000
   ```

## API Documentation

### Authentication

- **Register**: `POST /api/register`
- **Login**: `POST /api/login`
- **Logout**: `POST /api/logout` (requires authentication)

### Translations

- **List Translations**: `GET /api/translations` (requires authentication)
- **Create Translation**: `POST /api/translations` (requires authentication)
- **View Translation**: `GET /api/translations/{id}` (requires authentication)
- **Update Translation**: `PUT /api/translations/{id}` (requires authentication)
- **Delete Translation**: `DELETE /api/translations/{id}` (requires authentication)

### Search

- **Search by Tag**: `GET /api/translations/search/tags/{tag}` (requires authentication)
- **Search by Key**: `GET /api/translations/search/keys/{key}` (requires authentication)
- **Search by Content**: `GET /api/translations/search/content/{content}` (requires authentication)

### Export

- **Export Translations**: `GET /api/translations/export/{locale?}` (public endpoint)

## Design Choices

### Database Schema

- **Translations Table**: Stores the translation key, value, and locale with appropriate indexes.
- **Tags Table**: Stores tag names.
- **Pivot Table**: Manages the many-to-many relationship between translations and tags.

### Performance Optimizations

1. **Indexing**: Key database columns are indexed for faster queries.
2. **Caching**: The export endpoint uses Redis caching to improve response times.
3. **Pagination**: All listing endpoints use pagination to handle large datasets efficiently.
4. **Chunking**: The data generation command uses chunking to handle large datasets without memory issues.
5. **Database Transactions**: All write operations use transactions to ensure data integrity.

### Security

1. **Token-based Authentication**: Using Laravel Sanctum for secure API authentication.
2. **Input Validation**: All user inputs are validated before processing.
3. **CSRF Protection**: Laravel's built-in CSRF protection for web routes.

### Scalability

1. **Docker Setup**: Easy deployment and scaling with Docker.
2. **Database Optimization**: Optimized database schema and queries for handling large datasets.
3. **Caching Strategy**: Efficient caching to reduce database load.

## Testing

The service includes comprehensive test coverage (>95%) with unit, feature, and performance tests:

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
php artisan test --testsuite=Performance

# Run tests with coverage report
php artisan test --coverage
```

### Test Structure

- **Unit Tests**: Test individual components in isolation
  - `tests/Unit/AuthServiceTest.php`: Tests for authentication service
  - `tests/Unit/TranslationServiceTest.php`: Tests for translation service
  - `tests/Unit/TagRepositoryTest.php`: Tests for tag repository
  - `tests/Unit/TranslationRepositoryTest.php`: Tests for translation repository

- **Feature Tests**: Test complete flows and API endpoints
  - `tests/Feature/AuthFlowTest.php`: Tests for authentication flow
  - `tests/Feature/TranslationFlowTest.php`: Tests for translation management flow
  - `tests/Feature/TranslationApiTest.php`: Tests for individual API endpoints

- **Performance Tests**: Test response times and scalability
  - `tests/Performance/PerformanceTest.php`: Tests for export endpoint performance

### Test Coverage

The test suite achieves >95% code coverage by testing:

- All service methods
- All repository methods
- All API endpoints
- All validation rules
- Error handling and edge cases
- Performance requirements

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
