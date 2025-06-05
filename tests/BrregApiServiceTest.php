<?php

namespace Egerstudios\BrregApi\Tests;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;
use Egerstudios\BrregApi\BrregApiServiceProvider;
use Egerstudios\BrregApi\Exceptions\BrregApiException;
use Egerstudios\BrregApi\Services\BrregApiService;

class BrregApiServiceTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [BrregApiServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        Cache::flush();
    }

    /** @test */
    public function it_can_search_entities_by_name()
    {
        Http::fake([
            'data.brreg.no/enhetsregisteret/api/enheter*' => Http::response([
                '_embedded' => [
                    'enheter' => [
                        [
                            'organisasjonsnummer' => '923609016',
                            'navn' => 'Equinor ASA',
                            'organisasjonsform' => [
                                'beskrivelse' => 'Allmennaksjeselskap'
                            ],
                            'hjemmeside' => 'www.equinor.com',
                            'antallAnsatte' => 1000,
                        ]
                    ]
                ],
                'page' => [
                    'totalElements' => 1,
                    'totalPages' => 1,
                    'number' => 0,
                    'size' => 20
                ]
            ])
        ]);

        $service = app(BrregApiService::class);
        $results = $service->searchByName('Equinor');

        $this->assertTrue($results->hasResults());
        $this->assertEquals(1, $results->totalElements);
        $this->assertEquals('Equinor ASA', $results->entities->first()->name);
        $this->assertEquals('923609016', $results->entities->first()->organizationNumber);
    }

    /** @test */
    public function it_can_get_entity_by_organization_number()
    {
        Http::fake([
            'data.brreg.no/enhetsregisteret/api/enheter/923609016' => Http::response([
                'organisasjonsnummer' => '923609016',
                'navn' => 'Equinor ASA',
                'organisasjonsform' => [
                    'beskrivelse' => 'Allmennaksjeselskap'
                ],
                'hjemmeside' => 'www.equinor.com',
                'antallAnsatte' => 1000,
                'epostadresse' => 'contact@equinor.com',
                'telefon' => '51990000',
                'registrertIMvaregisteret' => true,
                'konkurs' => false,
                'underAvvikling' => false,
            ])
        ]);

        $service = app(BrregApiService::class);
        $entity = $service->searchByOrganizationNumber('923609016');

        $this->assertNotNull($entity);
        $this->assertEquals('Equinor ASA', $entity->name);
        $this->assertEquals('923609016', $entity->organizationNumber);
        $this->assertEquals('www.equinor.com', $entity->website);
        $this->assertTrue($entity->hasWebsite());
        $this->assertTrue($entity->hasEmail());
        $this->assertTrue($entity->hasPhone());
        $this->assertTrue($entity->isActive());
    }

    /** @test */
    public function it_returns_null_for_non_existent_organization_number()
    {
        Http::fake([
            'data.brreg.no/enhetsregisteret/api/enheter/999999999' => Http::response([], 404)
        ]);

        $service = app(BrregApiService::class);
        $entity = $service->searchByOrganizationNumber('999999999');

        $this->assertNull($entity);
    }

    /** @test */
    public function it_validates_organization_number_format()
    {
        $service = app(BrregApiService::class);

        $this->expectException(BrregApiException::class);
        $this->expectExceptionMessage('Invalid organization number format. Must be 9 digits.');

        $service->searchByOrganizationNumber('12345');
    }

    /** @test */
    public function it_can_search_multiple_organization_numbers()
    {
        Http::fake([
            'data.brreg.no/enhetsregisteret/api/enheter*' => Http::response([
                '_embedded' => [
                    'enheter' => [
                        [
                            'organisasjonsnummer' => '923609016',
                            'navn' => 'Equinor ASA',
                        ],
                        [
                            'organisasjonsnummer' => '974760673',
                            'navn' => 'DNB Bank ASA',
                        ]
                    ]
                ],
                'page' => [
                    'totalElements' => 2,
                    'totalPages' => 1,
                    'number' => 0,
                    'size' => 2
                ]
            ])
        ]);

        $service = app(BrregApiService::class);
        $results = $service->searchByOrganizationNumbers(['923609016', '974760673']);

        $this->assertTrue($results->hasResults());
        $this->assertEquals(2, $results->totalElements);
        $this->assertEquals(2, $results->entities->count());
    }

    /** @test */
    public function it_caches_search_results()
    {
        Http::fake([
            'data.brreg.no/enhetsregisteret/api/enheter*' => Http::response([
                '_embedded' => [
                    'enheter' => [
                        [
                            'organisasjonsnummer' => '923609016',
                            'navn' => 'Equinor ASA',
                        ]
                    ]
                ],
                'page' => [
                    'totalElements' => 1,
                    'totalPages' => 1,
                    'number' => 0,
                    'size' => 20
                ]
            ])
        ]);

        $service = app(BrregApiService::class);
        
        // First call should hit the API
        $results1 = $service->searchByName('Equinor');
        
        // Second call should hit the cache
        $results2 = $service->searchByName('Equinor');

        $this->assertEquals($results1->toArray(), $results2->toArray());
        
        // Verify only one HTTP request was made
        Http::assertSentCount(1);
    }

    /** @test */
    public function it_can_clear_cache()
    {
        Http::fake([
            'data.brreg.no/enhetsregisteret/api/enheter*' => Http::response([
                '_embedded' => [
                    'enheter' => []
                ],
                'page' => [
                    'totalElements' => 0,
                    'totalPages' => 0,
                    'number' => 0,
                    'size' => 20
                ]
            ])
        ]);

        $service = app(BrregApiService::class);
        
        // Make a request to populate cache
        $service->searchByName('Test');
        
        // Clear cache
        $result = $service->clearCache();
        
        $this->assertTrue($result);
        
        // Make another request - should hit API again
        $service->searchByName('Test');
        
        Http::assertSentCount(2);
    }

    /** @test */
    public function it_handles_api_errors_gracefully()
    {
        Http::fake([
            'data.brreg.no/enhetsregisteret/api/enheter*' => Http::response([], 500)
        ]);

        $service = app(BrregApiService::class);

        $this->expectException(BrregApiException::class);
        $this->expectExceptionMessage('API request failed with status: 500');

        $service->searchByName('Test');
    }

    /** @test */
    public function it_can_perform_advanced_search()
    {
        Http::fake([
            'data.brreg.no/enhetsregisteret/api/enheter*' => Http::response([
                '_embedded' => [
                    'enheter' => [
                        [
                            'organisasjonsnummer' => '923609016',
                            'navn' => 'Equinor ASA',
                            'organisasjonsform' => [
                                'beskrivelse' => 'Allmennaksjeselskap'
                            ],
                            'kommunenummer' => '0301',
                            'antallAnsatte' => 50,
                        ]
                    ]
                ],
                'page' => [
                    'totalElements' => 1,
                    'totalPages' => 1,
                    'number' => 0,
                    'size' => 20
                ]
            ])
        ]);

        $service = app(BrregApiService::class);
        $results = $service->advancedSearch([
            'organisasjonsform' => ['AS', 'ASA'],
            'kommunenummer' => ['0301'],
            'fraAntallAnsatte' => '10',
            'tilAntallAnsatte' => '100',
        ]);

        $this->assertTrue($results->hasResults());
        $this->assertEquals(1, $results->totalElements);
    }

    /** @test */
    public function entity_data_helper_methods_work_correctly()
    {
        $entityData = [
            'organisasjonsnummer' => '923609016',
            'navn' => 'Test Company',
            'hjemmeside' => 'www.test.com',
            'epostadresse' => 'test@test.com',
            'telefon' => '12345678',
            'konkurs' => false,
            'underAvvikling' => false,
            'forretningsadresse' => [
                'adresse' => ['Test Street 1'],
                'postnummer' => '0001',
                'poststed' => 'Oslo',
                'land' => 'Norge'
            ]
        ];

        $entity = \Egerstudios\BrregApi\Data\EntityData::fromApiResponse($entityData);

        $this->assertTrue($entity->hasWebsite());
        $this->assertTrue($entity->hasEmail());
        $this->assertTrue($entity->hasPhone());
        $this->assertTrue($entity->isActive());
        $this->assertEquals('Test Street 1, 0001, Oslo, Norge', $entity->getFullAddress('business'));
    }
}