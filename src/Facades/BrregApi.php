<?php

namespace Egerstudios\BrregApi\Facades;

use Illuminate\Support\Facades\Facade;
use Egerstudios\BrregApi\Services\BrregApiService;

/**
 * @method static \Egerstudios\BrregApi\Data\SearchResultData searchByName(string $name, array $options = [])
 * @method static \Egerstudios\BrregApi\Data\EntityData|null searchByOrganizationNumber(string $orgNumber)
 * @method static \Egerstudios\BrregApi\Data\SearchResultData searchByOrganizationNumbers(array $orgNumbers)
 * @method static \Egerstudios\BrregApi\Data\SearchResultData advancedSearch(array $filters)
 * @method static \Illuminate\Support\Collection searchNonProfitByName(string $name)
 * @method static array|null getNonProfitByOrganizationNumber(string $orgNumber)
 * @method static bool clearCache(string $pattern = null)
 */
class BrregApi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BrregApiService::class;
    }
}