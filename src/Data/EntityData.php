<?php

namespace Egerstudios\BrregApi\Data;

class EntityData
{
    public function __construct(
        public readonly string $organizationNumber,
        public readonly string $name,
        public readonly ?string $organizationForm,
        public readonly ?array $businessAddress,
        public readonly ?array $postalAddress,
        public readonly ?bool $registeredInVAT,
        public readonly ?array $industryCode1,
        public readonly ?array $industryCode2,
        public readonly ?array $industryCode3,
        public readonly ?string $website,
        public readonly ?int $numberOfEmployees,
        public readonly ?string $establishmentDate,
        public readonly ?string $registrationDate,
        public readonly ?bool $isBankrupt,
        public readonly ?bool $underLiquidation,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $mobile,
        public readonly array $rawData = []
    ) {}

    public static function fromApiResponse(array $data): self
    {
        return new self(
            organizationNumber: $data['organisasjonsnummer'] ?? '',
            name: $data['navn'] ?? '',
            organizationForm: $data['organisasjonsform']['beskrivelse'] ?? null,
            businessAddress: self::formatAddress($data['forretningsadresse'] ?? null),
            postalAddress: self::formatAddress($data['postadresse'] ?? null),
            registeredInVAT: $data['registrertIMvaregisteret'] ?? null,
            industryCode1: self::formatIndustryCode($data['naeringskode1'] ?? null),
            industryCode2: self::formatIndustryCode($data['naeringskode2'] ?? null),
            industryCode3: self::formatIndustryCode($data['naeringskode3'] ?? null),
            website: $data['hjemmeside'] ?? null,
            numberOfEmployees: $data['antallAnsatte'] ?? null,
            establishmentDate: $data['stiftelsesdato'] ?? null,
            registrationDate: $data['registreringsdatoEnhetsregisteret'] ?? null,
            isBankrupt: $data['konkurs'] ?? null,
            underLiquidation: $data['underAvvikling'] ?? null,
            email: $data['epostadresse'] ?? null,
            phone: $data['telefon'] ?? null,
            mobile: $data['mobil'] ?? null,
            rawData: $data
        );
    }

    protected static function formatAddress(?array $address): ?array
    {
        if (!$address) {
            return null;
        }

        return [
            'address' => $address['adresse'] ?? null,
            'postal_code' => $address['postnummer'] ?? null,
            'postal_area' => $address['poststed'] ?? null,
            'municipality' => $address['kommune'] ?? null,
            'municipality_number' => $address['kommunenummer'] ?? null,
            'country' => $address['land'] ?? null,
            'country_code' => $address['landkode'] ?? null,
        ];
    }

    protected static function formatIndustryCode(?array $industryCode): ?array
    {
        if (!$industryCode) {
            return null;
        }

        return [
            'code' => $industryCode['kode'] ?? null,
            'description' => $industryCode['beskrivelse'] ?? null,
        ];
    }

    public function toArray(): array
    {
        return [
            'organization_number' => $this->organizationNumber,
            'name' => $this->name,
            'organization_form' => $this->organizationForm,
            'business_address' => $this->businessAddress,
            'postal_address' => $this->postalAddress,
            'registered_in_vat' => $this->registeredInVAT,
            'industry_code_1' => $this->industryCode1,
            'industry_code_2' => $this->industryCode2,
            'industry_code_3' => $this->industryCode3,
            'website' => $this->website,
            'number_of_employees' => $this->numberOfEmployees,
            'establishment_date' => $this->establishmentDate,
            'registration_date' => $this->registrationDate,
            'is_bankrupt' => $this->isBankrupt,
            'under_liquidation' => $this->underLiquidation,
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
        ];
    }

    public function hasWebsite(): bool
    {
        return !empty($this->website);
    }

    public function hasEmail(): bool
    {
        return !empty($this->email);
    }

    public function hasPhone(): bool
    {
        return !empty($this->phone) || !empty($this->mobile);
    }

    public function isActive(): bool
    {
        return !$this->isBankrupt && !$this->underLiquidation;
    }

    public function getFullAddress(string $type = 'business'): ?string
    {
        $address = $type === 'business' ? $this->businessAddress : $this->postalAddress;
        
        if (!$address) {
            return null;
        }

        $parts = array_filter([
            is_array($address['address']) ? implode(', ', $address['address']) : $address['address'],
            $address['postal_code'],
            $address['postal_area'],
            $address['country']
        ]);

        return implode(', ', $parts);
    }
}