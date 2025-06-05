# Changelog

All notable changes to `brreg-api` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2025-06-05

### Added
- Initial release
- Support for searching entities by name
- Support for searching entities by organization number
- Support for batch lookup of multiple organization numbers
- Advanced search with multiple filters
- Non-profit organization search support
- Built-in caching with configurable TTL
- Rate limiting protection
- Structured data objects (EntityData, SearchResultData)
- Comprehensive error handling with custom exceptions
- Laravel Facade support
- Service provider for easy Laravel integration
- Extensive test coverage
- Complete documentation with usage examples

### Features
- **Search Methods**:
  - `searchByName()` - Fuzzy search by company name
  - `searchByOrganizationNumber()` - Lookup by 9-digit org number
  - `searchByOrganizationNumbers()` - Batch lookup multiple companies
  - `advancedSearch()` - Search with filters (location, form, employees, etc.)
  - `searchNonProfitByName()` - Search voluntary organizations
  - `getNonProfitByOrganizationNumber()` - Get specific non-profit

- **Data Handling**:
  - Structured EntityData objects with helper methods
  - SearchResultData with pagination and filtering capabilities
  - Address formatting and validation
  - Industry code handling

- **Infrastructure**:
  - Redis/file cache support with configurable TTL
  - HTTP retry mechanisms with exponential backoff
  - Input validation for Norwegian organization numbers
  - Comprehensive error handling and logging

- **Laravel Integration**:
  - Service provider for automatic registration
  - Facade for easy static access
  - Configuration file publishing
  - Dependency injection support
  - Artisan command examples

### Requirements
- PHP 8.1 or higher
- Laravel 10.0, 11.0, or 12.0
- Guzzle HTTP client 7.0+

[Unreleased]: https://github.com/egerstudios/brreg-api/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/egerstudios/brreg-api/releases/tag/v1.0.0