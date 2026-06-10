<?php

declare(strict_types=1);

namespace Nektria\Util\Doctrine\Type;

use Nektria\Dto\Address;
use Nektria\Util\JsonUtil;

/**
 * @extends JsonType<Address>
 */
class AddressType extends JsonType
{
    /**
     * @param Address $phpValue
     */
    protected function convertToDatabase($phpValue): string
    {
        return JsonUtil::encode([
            'addressLine1' => $phpValue->addressLine1,
            'addressLine2' => $phpValue->addressLine2,
            'postalCode' => $phpValue->postalCode,
            'city' => $phpValue->city,
            'countryCode' => $phpValue->countryCode,
            'elevator' => $phpValue->elevator,
            'latitude' => $phpValue->latitude,
            'longitude' => $phpValue->longitude,
        ]);
    }

    protected function convertToPhp(string $databaseValue): Address
    {
        $json = JsonUtil::decode($databaseValue);

        return new Address(
            addressLine1: $json['addressLine1'],
            addressLine2: $json['addressLine2'],
            elevator: $json['elevator'] ?? true,
            postalCode: $json['postalCode'],
            city: $json['city'],
            countryCode: $json['countryCode'],
            latitude: $json['latitude'] ?? 0.0,
            longitude: $json['longitude'] ?? 0.0,
        );
    }

    protected function getTypeName(): string
    {
        return 'json_address';
    }
}
