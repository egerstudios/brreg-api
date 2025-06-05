<?php

namespace Egerstudios\BrregApi\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Cache\Repository as Cache;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Egerstudios\BrregApi\Data\EntityData;
use Egerstudios\BrregApi\Data\SearchResultData;
use Egerstudios\BrregApi\Exceptions\BrregApiException;

class BrregApiService
{
    protected array $config;
    protected Cache $cache;
    protected string $baseUrl;

    public function __construct(array $config, Cache $cache)
    {
        $this->config = $config;
        $this->cache = $cache;
        $this->baseUrl = $config['base_url'];
    }

    /**
     * Search for entities by name
     */
    public function searchByName(string $name, array $options = []): SearchResultData
    {
        $params = array_merge([
            'navn' => $name,
            'navnMetodeForSoek' => 'FORTLOEPENDE',
            'size' => $options['size'] ?? 20,
            'page' => $options['page'] ?? 0,
        ], $options);

        $cacheKey = $this->getCacheKey('search_name', $params);
        
        if ($this->isCacheEnabled() && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        try {
            $response = $this->makeRequest('GET', $this->config['endpoints']['entities'], $params);
            $result = SearchResultData::fromApiResponse($response);
            
            if ($this->isCacheEnabled()) {
                $this->cache->put($cacheKey, $result, $this->config['cache']['ttl']);
            }
            
            return $result;
        } catch (\Exception $e) {
            throw new BrregApiException("Failed to search entities by name: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Search for entities by organization number
     */
    public function searchByOrganizationNumber(string $orgNumber): ?EntityData
    {
        // Validate organization number format (9 digits)
        if (!preg_match('/^\d{9}$/', $orgNumber)) {
            throw new BrregApiException("Invalid organization number format. Must be 9 digits.");
        }

        $cacheKey = $this->getCacheKey('entity', ['orgnr' => $orgNumber]);
        
        if ($this->isCacheEnabled() && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        try {
            $endpoint = str_replace('{orgnr}', $orgNumber, $this->config['endpoints']['entity']);
            $response = $this->makeRequest('GET', $endpoint);
            
            if (empty($response)) {
                return null;
            }
            
            $result = EntityData::fromApiResponse($response);
            
            if ($this->isCacheEnabled()) {
                $this->cache->put($cacheKey, $result, $this->config['cache']['ttl']);
            }
            
            return $result;
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), '404')) {
                return null;
            }
            throw new BrregApiException("Failed to fetch entity by organization number: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Search for multiple organization numbers
     */
    public function searchByOrganizationNumbers(array $orgNumbers): SearchResultData
    {
        // Validate all organization numbers
        foreach ($orgNumbers as $orgNumber) {
            if (!preg_match('/^\d{9}$/', $orgNumber)) {
                throw new BrregApiException("Invalid organization number format: {$orgNumber}. Must be 9 digits.");
            }
        }

        $params = [
            'organisasjonsnummer' => implode(',', $orgNumbers),
            'size' => count($orgNumbers),
        ];

        $cacheKey = $this->getCacheKey('search_multiple', $params);
        
        if ($this->isCacheEnabled() && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        try {
            $response = $this->makeRequest('GET', $this->config['endpoints']['entities'], $params);
            $result = SearchResultData::fromApiResponse($response);
            
            if ($this->isCacheEnabled()) {
                $this->cache->put($cacheKey, $result, $this->config['cache']['ttl']);
            }
            
            return $result;
        } catch (\Exception $e) {
            throw new BrregApiException("Failed to search entities by organization numbers: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Advanced search with multiple filters
     */
    public function advancedSearch(array $filters): SearchResultData
    {
        $cacheKey = $this->getCacheKey('advanced_search', $filters);
        
        if ($this->isCacheEnabled() && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        try {
            $response = $this->makeRequest('GET', $this->config['endpoints']['entities'], $filters);
            $result = SearchResultData::fromApiResponse($response);
            
            if ($this->isCacheEnabled()) {
                $this->cache->put($cacheKey, $result, $this->config['cache']['ttl']);
            }
            
            return $result;
        } catch (\Exception $e) {
            throw new BrregApiException("Failed to perform advanced search: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Search non-profit organizations by name
     */
    public function searchNonProfitByName(string $name): Collection
    {
        $cacheKey = $this->getCacheKey('nonprofit_search', ['name' => $name]);
        
        if ($this->isCacheEnabled() && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        try {
            // First get all non-profit organizations (they don't have name search)
            $response = $this->makeRequest('GET', $this->config['endpoints']['non_profit'], [
                'size' => 500 // Adjust based on needs
            ]);
            
            // Filter by name on our side
            $results = collect($response['_embedded']['frivilligeOrganisasjoner'] ?? [])
                ->filter(function ($org) use ($name) {
                    return stripos($org['navn'] ?? '', $name) !== false;
                });
            
            if ($this->isCacheEnabled()) {
                $this->cache->put($cacheKey, $results, $this->config['cache']['ttl']);
            }
            
            return $results;
        } catch (\Exception $e) {
            throw new BrregApiException("Failed to search non-profit organizations: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get non-profit organization by organization number
     */
    public function getNonProfitByOrganizationNumber(string $orgNumber): ?array
    {
        if (!preg_match('/^\d{9}$/', $orgNumber)) {
            throw new BrregApiException("Invalid organization number format. Must be 9 digits.");
        }

        $cacheKey = $this->getCacheKey('nonprofit', ['orgnr' => $orgNumber]);
        
        if ($this->isCacheEnabled() && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        try {
            $endpoint = str_replace('{orgnr}', $orgNumber, $this->config['endpoints']['non_profit_single']);
            $response = $this->makeRequest('GET', $endpoint);
            
            if ($this->isCacheEnabled() && !empty($response)) {
                $this->cache->put($cacheKey, $response, $this->config['cache']['ttl']);
            }
            
            return $response ?: null;
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), '404')) {
                return null;
            }
            throw new BrregApiException("Failed to fetch non-profit organization: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Clear cache for specific keys or all
     */
    public function clearCache(?string $pattern = null): bool
    {
        if ($pattern) {
            // This is a simplified implementation - you might want to use Redis patterns
            return $this->cache->forget($this->config['cache']['prefix'] . $pattern);
        }
        
        // Clear all cache with our prefix (simplified - implement based on your cache driver)
        return $this->cache->flush();
    }

    /**
     * Make HTTP request to the API
     */
    protected function makeRequest(string $method, string $endpoint, array $params = []): array
    {
        $url = $this->baseUrl . $endpoint;
        
        try {
            $response = Http::timeout($this->config['http']['timeout'])
                ->connectTimeout($this->config['http']['connect_timeout'])
                ->retry(
                    $this->config['http']['retry']['times'],
                    $this->config['http']['retry']['sleep']
                )
                ->when($method === 'GET', function ($http) use ($params) {
                    return $http->get($url, $params);
                })
                ->when($method === 'POST', function ($http) use ($params) {
                    return $http->post($url, $params);
                });

            if ($response->failed()) {
                throw new BrregApiException("API request failed with status: " . $response->status());
            }

            return $response->json();
        } catch (RequestException $e) {
            Log::error('Brreg API request failed', [
                'url' => $url,
                'method' => $method,
                'params' => $params,
                'error' => $e->getMessage(),
            ]);
            
            throw new BrregApiException("HTTP request failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Generate cache key
     */
    protected function getCacheKey(string $type, array $params = []): string
    {
        return $this->config['cache']['prefix'] . $type . '_' . md5(serialize($params));
    }

    /**
     * Check if caching is enabled
     */
    protected function isCacheEnabled(): bool
    {
        return $this->config['cache']['enabled'];
    }
}