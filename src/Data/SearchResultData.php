<?php

namespace Egerstudios\BrregApi\Data;

use Illuminate\Support\Collection;

class SearchResultData
{
    public function __construct(
        public readonly Collection $entities,
        public readonly int $totalElements,
        public readonly int $totalPages,
        public readonly int $currentPage,
        public readonly int $pageSize,
        public readonly array $rawData = []
    ) {}

    public static function fromApiResponse(array $data): self
    {
        $entities = collect($data['_embedded']['enheter'] ?? $data['_embedded']['underenheter'] ?? [])
            ->map(fn($entityData) => EntityData::fromApiResponse($entityData));

        $pageInfo = $data['page'] ?? [];

        return new self(
            entities: $entities,
            totalElements: $pageInfo['totalElements'] ?? 0,
            totalPages: $pageInfo['totalPages'] ?? 0,
            currentPage: ($pageInfo['number'] ?? 0) + 1, // API uses 0-based, we use 1-based
            pageSize: $pageInfo['size'] ?? 20,
            rawData: $data
        );
    }

    public function hasResults(): bool
    {
        return $this->entities->isNotEmpty();
    }

    public function isEmpty(): bool
    {
        return $this->entities->isEmpty();
    }

    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    public function getEntitiesByOrganizationForm(string $organizationForm): Collection
    {
        return $this->entities->filter(function (EntityData $entity) use ($organizationForm) {
            return $entity->organizationForm === $organizationForm;
        });
    }

    public function getActiveEntities(): Collection
    {
        return $this->entities->filter(fn(EntityData $entity) => $entity->isActive());
    }

    public function getEntitiesWithWebsite(): Collection
    {
        return $this->entities->filter(fn(EntityData $entity) => $entity->hasWebsite());
    }

    public function getEntitiesWithEmail(): Collection
    {
        return $this->entities->filter(fn(EntityData $entity) => $entity->hasEmail());
    }

    public function sortByName(): Collection
    {
        return $this->entities->sortBy('name');
    }

    public function sortByEmployeeCount(): Collection
    {
        return $this->entities->sortByDesc('numberOfEmployees');
    }

    public function toArray(): array
    {
        return [
            'entities' => $this->entities->map(fn(EntityData $entity) => $entity->toArray())->all(),
            'pagination' => [
                'total_elements' => $this->totalElements,
                'total_pages' => $this->totalPages,
                'current_page' => $this->currentPage,
                'page_size' => $this->pageSize,
                'has_more_pages' => $this->hasMorePages(),
            ],
        ];
    }
}