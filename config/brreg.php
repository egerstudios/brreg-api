<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Brønnøysundregistrene API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Brønnøysundregistrene API client
    |
    */

    'base_url' => 'https://data.brreg.no',

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    |
    | Define the available API endpoints
    |
    */
    'endpoints' => [
        'entities' => '/enhetsregisteret/api/enheter',
        'entity' => '/enhetsregisteret/api/enheter/{orgnr}',
        'sub_entities' => '/enhetsregisteret/api/underenheter',
        'sub_entity' => '/enhetsregisteret/api/underenheter/{orgnr}',
        'non_profit' => '/frivillighetsregisteret/api/frivillige-organisasjoner',
        'non_profit_single' => '/frivillighetsregisteret/api/frivillige-organisasjoner/{orgnr}',
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for API responses
    |
    */
    'cache' => [
        'enabled' => env('BRREG_CACHE_ENABLED', true),
        'ttl' => env('BRREG_CACHE_TTL', 3600), // 1 hour in seconds
        'prefix' => 'brreg_api_',
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Settings
    |--------------------------------------------------------------------------
    |
    | Configure the HTTP client behavior
    |
    */
    'http' => [
        'timeout' => 30,
        'connect_timeout' => 10,
        'retry' => [
            'times' => 3,
            'sleep' => 1000, // milliseconds
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting to be respectful to the API
    |
    */
    'rate_limit' => [
        'enabled' => true,
        'requests_per_minute' => 60,
    ],
];