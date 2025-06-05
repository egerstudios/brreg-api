# Brønnøysundregistrene API Laravel Package

A Laravel package for consuming the Brønnøysundregistrene (Norwegian Business Register) open API. This package provides easy access to search for Norwegian companies and organizations by name or organization number with built-in caching support.

## Features

- ✅ Search companies by name with fuzzy matching
- ✅ Lookup companies by organization number
- ✅ Batch lookup for multiple organization numbers
- ✅ Advanced search with multiple filters
- ✅ Non-profit organization search
- ✅ Built-in caching with configurable TTL
- ✅ Rate limiting protection
- ✅ Structured data objects with helper methods
- ✅ Laravel 10, 11, and 12 support
- ✅ Comprehensive error handling
- ✅ Facade and service injection support

## Installation

Install the package via Composer:

```bash
composer require your-vendor/brreg-api
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=brreg-config
```

## Configuration

The package comes with sensible defaults, but you can customize the configuration in `config/brreg.php`:

```php
return [
    'base_url' => 'https://data.brreg.no',
    
    'cache' => [
        'enabled' => env('BRREG_CACHE_ENABLED', true),
        'ttl' => env('BRREG_CACHE_TTL', 3600), // 1 hour
        'prefix' => 'brreg_api_',
    ],
    
    'http' => [
        'timeout' => 30,
        'connect_timeout' => 10,
        'retry' => [
            'times' => 3,
            'sleep' => 1000,
        ],
    ],
    
    'rate_limit' => [
        'enabled' => true,
        'requests_per_minute' => 60,
    ],
];
```

You can also set these environment variables in your `.env` file:

```env
BRREG_CACHE_ENABLED=true
BRREG_CACHE_TTL=3600
```

## Usage

### Using the Facade

```php
use Egerstudios\BrregApi\Facades\BrregApi;

// Search by company name
$results = BrregApi::searchByName('Equinor');

// Get specific company by organization number
$company = BrregApi::searchByOrganizationNumber('923609016');

// Advanced search with filters
$results = BrregApi::advancedSearch([
    'organisasjonsform' => ['AS', 'ASA'],
    'kommunenummer' => ['0301'], // Oslo
    'fraAntallAnsatte' => '10',
    'tilAntallAnsatte' => '100',
]);
```

### Using Service Injection

```php
use Egerstudios\BrregApi\Services\BrregApiService;

class CompanyController extends Controller
{
    public function __construct(private BrregApiService $brregApi) {}

    public function search(string $name)
    {
        $results = $this->brregApi->searchByName($name, [
            'size' => 20,
            'page' => 0
        ]);

        return response()->json($results->toArray());
    }
}
```

## Examples

### Basic Company Search

```php
use Egerstudios\BrregApi\Facades\BrregApi;

// Search for companies containing "Equinor"
$results = BrregApi::searchByName('Equinor');

if ($results->hasResults()) {
    foreach ($results->entities as $entity) {
        echo "Company: {$entity->name}\n";
        echo "Org Number: {$entity->organizationNumber}\n";
        echo "Website: {$entity->website}\n";
        echo "Employees: {$entity->numberOfEmployees}\n";
        echo "Active: " . ($entity->isActive() ? 'Yes' : 'No') . "\n";
        echo "Business Address: " . $entity->getFullAddress('business') . "\n";
        echo "---\n";
    }
    
    echo "Total results: {$results->totalElements}\n";
    echo "Current page: {$results->currentPage} of {$results->totalPages}\n";
}
```

### Lookup by Organization Number

```php
// Get specific company by organization number (9 digits)
$entity = BrregApi::searchByOrganizationNumber('923609016');

if ($entity) {
    return [
        'name' => $entity->name,
        'org_number' => $entity->organizationNumber,
        'organization_form' => $entity->organizationForm,
        'website' => $entity->website,
        'email' => $entity->email,
        'phone' => $entity->phone,
        'employees' => $entity->numberOfEmployees,
        'industry' => $entity->industryCode1['description'] ?? null,
        'business_address' => $entity->getFullAddress('business'),
        'postal_address' => $entity->getFullAddress('postal'),
        'is_active' => $entity->isActive(),
        'registered_in_vat' => $entity->registeredInVAT,
        'establishment_date' => $entity->establishmentDate,
    ];
} else {
    return ['error' => 'Company not found'];
}
```

### Multiple Organization Numbers

```php
$orgNumbers = ['923609016', '974760673', '988077917'];
$results = BrregApi::searchByOrganizationNumbers($orgNumbers);

$companies = $results->entities->map(function($entity) {
    return [
        'org_number' => $entity->organizationNumber,
        'name' => $entity->name,
        'status' => $entity->isActive() ? 'active' : 'inactive',
        'employees' => $entity->numberOfEmployees,
        'industry' => $entity->industryCode1['description'] ?? 'N/A',
    ];
});
```

### Advanced Search with Filters

```php
// Search for AS/ASA companies in Oslo with 10-100 employees
$results = BrregApi::advancedSearch([
    'organisasjonsform' => ['AS', 'ASA'], // Limited companies
    'kommunenummer' => ['0301'], // Oslo municipality
    'fraAntallAnsatte' => '10',
    'tilAntallAnsatte' => '100',
    'registrertIMvaregisteret' => true, // Registered in VAT
    'size' => 50
]);

// Filter results further
$activeWithWebsites = $results->getActiveEntities()
    ->filter(fn($entity) => $entity->hasWebsite());

foreach ($activeWithWebsites as $entity) {
    echo "{$entity->name} - {$entity->website}\n";
}
```

### Non-Profit Organizations

```php
// Search non-profit organizations
$nonprofits = BrregApi::searchNonProfitByName('Røde Kors');

foreach ($nonprofits as $org) {
    echo "Organization: " . ($org['navn'] ?? 'N/A') . "\n";
    echo "Org Number: {$org['organisasjonsnummer']}\n";
    echo "Status: " . ($org['frivilligOrganisasjonsstatus'] ?? 'N/A') . "\n";
}

// Get specific non-profit by organization number
$nonprofit = BrregApi::getNonProfitByOrganizationNumber('971035854');
if ($nonprofit) {
    echo "Name: " . ($nonprofit['navn'] ?? 'N/A') . "\n";
    echo "ICNPO Categories: " . implode(', ', 
        collect($nonprofit['icnpoKategorier'] ?? [])->pluck('navn')->toArray()
    ) . "\n";
}
```

### Working with Search Results

```php
$results = BrregApi::searchByName('Tech');

// Check if we have results
if ($results->isEmpty()) {
    echo "No companies found\n";
    return;
}

// Get companies by organization form
$limitedCompanies = $results->getEntitiesByOrganizationForm('Allmennaksjeselskap');

// Get only active companies
$activeCompanies = $results->getActiveEntities();

// Get companies with websites
$companiesWithWebsites = $results->getEntitiesWithWebsite();

// Sort by employee count
$sortedByEmployees = $results->sortByEmployeeCount();

// Pagination info
echo "Showing page {$results->currentPage} of {$results->totalPages}\n";
echo "Total companies: {$results->totalElements}\n";

if ($results->hasMorePages()) {
    echo "More pages available\n";
}
```

### Error Handling

```php
use Egerstudios\BrregApi\Exceptions\BrregApiException;

try {
    $entity = BrregApi::searchByOrganizationNumber('invalid-number');
} catch (BrregApiException $e) {
    echo "API Error: " . $e->getMessage() . "\n";
    // Log the error or handle it appropriately
}

// Validate organization number format
try {
    $entity = BrregApi::searchByOrganizationNumber('12345'); // Invalid: not 9 digits
} catch (BrregApiException $e) {
    echo $e->getMessage(); // "Invalid organization number format. Must be 9 digits."
}
```

### Cache Management

```php
// Clear specific cache patterns
BrregApi::clearCache('search_name');

// Clear all package cache
BrregApi::clearCache();

// Force fresh data (will still cache the new result)
$results = BrregApi::searchByName('test');
```

### Laravel Integration Examples

#### Controller Example

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Egerstudios\BrregApi\Facades\BrregApi;
use Egerstudios\BrregApi\Exceptions\BrregApiException;

class CompanyController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:2',
            'size' => 'integer|min:1|max:100',
            'page' => 'integer|min:0',
        ]);

        try {
            $results = BrregApi::searchByName($request->name, [
                'size' => $request->input('size', 20),
                'page' => $request->input('page', 0),
            ]);

            return response()->json([
                'success' => true,
                'data' => $results->toArray(),
            ]);
        } catch (BrregApiException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(string $orgNumber)
    {
        try {
            $entity = BrregApi::searchByOrganizationNumber($orgNumber);
            
            if (!$entity) {
                return response()->json([
                    'success' => false,
                    'error' => 'Company not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $entity->toArray(),
            ]);
        } catch (BrregApiException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
```

#### Artisan Command Example

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Egerstudios\BrregApi\Facades\BrregApi;

class LookupCompanyCommand extends Command
{
    protected $signature = 'company:lookup {orgNumber : The organization number}';
    protected $description = 'Lookup company information by organization number';

    public function handle()
    {
        $orgNumber = $this->argument('orgNumber');
        
        $this->info("Looking up company with organization number: {$orgNumber}");
        
        try {
            $entity = BrregApi::searchByOrganizationNumber($orgNumber);
            
            if (!$entity) {
                $this->error('Company not found');
                return 1;
            }
            
            $this->table(['Field', 'Value'], [
                ['Name', $entity->name],
                ['Organization Number', $entity->organizationNumber],
                ['Organization Form', $entity->organizationForm],
                ['Website', $entity->website ?: 'N/A'],
                ['Email', $entity->email ?: 'N/A'],
                ['Phone', $entity->phone ?: 'N/A'],
                ['Employees', $entity->numberOfEmployees ?: 'N/A'],
                ['Business Address', $entity->getFullAddress('business') ?: 'N/A'],
                ['Active', $entity->isActive() ? 'Yes' : 'No'],
                ['Registered in VAT', $entity->registeredInVAT ? 'Yes' : 'No'],
            ]);
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return 1;
        }
    }
}
```

## API Reference

### BrregApiService Methods

| Method | Description | Parameters | Returns |
|--------|-------------|------------|---------|
| `searchByName()` | Search entities by name | `string $name, array $options = []` | `SearchResultData` |
| `searchByOrganizationNumber()` | Get entity by org number | `string $orgNumber` | `EntityData\|null` |
| `searchByOrganizationNumbers()` | Get multiple entities | `array $orgNumbers` | `SearchResultData` |
| `advancedSearch()` | Search with filters | `array $filters` | `SearchResultData` |
| `searchNonProfitByName()` | Search non-profits | `string $name` | `Collection` |
| `getNonProfitByOrganizationNumber()` | Get non-profit by org number | `string $orgNumber` | `array\|null` |
| `clearCache()` | Clear cache | `?string $pattern = null` | `bool` |

### EntityData Properties

| Property | Type | Description |
|----------|------|-------------|
| `organizationNumber` | `string` | 9-digit organization number |
| `name` | `string` | Company name |
| `organizationForm` | `?string` | Legal form (AS, ASA, etc.) |
| `businessAddress` | `?array` | Business address details |
| `postalAddress` | `?array` | Postal address details |
| `registeredInVAT` | `?bool` | VAT registration status |
| `industryCode1` | `?array` | Primary industry code |
| `website` | `?string` | Company website |
| `numberOfEmployees` | `?int` | Number of employees |
| `email` | `?string` | Email address |
| `phone` | `?string` | Phone number |

### EntityData Helper Methods

| Method | Description | Returns |
|--------|-------------|---------|
| `isActive()` | Check if company is active | `bool` |
| `hasWebsite()` | Check if has website | `bool` |
| `hasEmail()` | Check if has email | `bool` |
| `hasPhone()` | Check if has phone | `bool` |
| `getFullAddress()` | Get formatted address | `?string` |
| `toArray()` | Convert to array | `array` |

## Available Search Filters

When using `advancedSearch()`, you can use these filters:

| Filter | Type | Description | Example |
|--------|------|-------------|---------|
| `navn` | `string` | Company name | `'Equinor'` |
| `organisasjonsnummer` | `array` | Org numbers | `['923609016', '974760673']` |
| `organisasjonsform` | `array` | Legal forms | `['AS', 'ASA']` |
| `kommunenummer` | `array` | Municipality numbers | `['0301', '1101']` |
| `fraAntallAnsatte` | `string` | Min employees | `'10'` |
| `tilAntallAnsatte` | `string` | Max employees | `'100'` |
| `registrertIMvaregisteret` | `bool` | VAT registered | `true` |
| `registrertIForetaksregisteret` | `bool` | Business register | `true` |
| `naeringskode` | `array` | Industry codes | `['62.010', '70.220']` |
| `hjemmeside` | `string` | Website | `'www.example.com'` |

## Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email security@Egerstudios.com instead of using the issue tracker.

## Credits

- [Your Name](https://github.com/yourusername)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.