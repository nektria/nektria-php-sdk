<?php

declare(strict_types=1);

namespace Nektria\Dto;

use Nektria\Document\Document;
use Nektria\Service\ContextService;
use Nektria\Util\StringUtil;
use Nektria\Util\Validate;
use Symfony\Component\String\Slugger\AsciiSlugger;

readonly class Address extends Document
{
    public function __construct(
        public string $addressLine1,
        public string $addressLine2,
        public bool $elevator,
        public string $postalCode,
        public string $city,
        public string $countryCode,
        public float $latitude,
        public float $longitude,
    ) {
        parent::__construct();
        // Validate::notEmpty($this->addressLine1);
        // Validate::notEmpty($this->postalCode);
        // Validate::notEmpty($this->city);
        // Validate::minLength($this->countryCode, 2);
        // Validate::maxLength($this->countryCode, 2);
        Validate::latitude($this->latitude);
        Validate::longitude($this->longitude);
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== 0.0 && $this->longitude !== 0.0;
    }

    public function normalize(): string
    {
        $addressLine1 = StringUtil::capitalize($this->addressLine1);
        $addressLine2 = StringUtil::capitalize($this->addressLine2);
        $postalCode = $this->postalCode;
        $city = StringUtil::capitalize($this->city);
        $countryCode = strtoupper($this->countryCode);

        return StringUtil::trim("{$addressLine1}, {$addressLine2}, ({$postalCode}) {$city} {$countryCode}");
    }

    public function shortSlug(): string
    {
        if ($this->addressLine1 === '') {
            return '';
        }

        $slugger = new AsciiSlugger();

        return strtolower($slugger->slug(
            StringUtil::trim(
                "{$this->addressLine1} {$this->postalCode} {$this->city} {$this->countryCode}",
            ),
        )->toString());
    }

    public function slug(): string
    {
        if ($this->addressLine1 === '') {
            return '';
        }

        $slugger = new AsciiSlugger();

        return strtolower($slugger->slug(
            StringUtil::trim(
                "{$this->addressLine1} {$this->addressLine2} {$this->postalCode} {$this->city} {$this->countryCode}",
            ),
        )->toString());
    }

    /**
     * @return array{
     *     addressLine1: string,
     *     addressLine2: string,
     *     elevator: bool,
     *     postalCode: string,
     *     city: string,
     *     countryCode: string,
     *     latitude: float,
     *     longitude: float,
     * }
     */
    protected function toArray(?ContextService $context): array
    {
        return [
            'addressLine1' => $this->addressLine1,
            'addressLine2' => $this->addressLine2,
            'elevator' => $this->elevator,
            'postalCode' => $this->postalCode,
            'city' => $this->city,
            'countryCode' => $this->countryCode,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
