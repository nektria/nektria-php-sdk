<?php

declare(strict_types=1);

namespace Nektria\Infrastructure;

use Nektria\Document\Tenant;
use Nektria\Dto\TenantMetadata;

/**
 * @phpstan-type TenantMetadataArray mixed
 *
 * @extends SharedRedisCache<array{
 *     aiAssistantId: string|null,
 *     id: string,
 *     name: string,
 *     metadata: TenantMetadataArray,
 *     timezone: string|null,
 *     alias: string|null,
 *     countryCode: string|null,
 * }>
 */
class SharedTenantCache extends SharedRedisCache
{
    public function read(string $key): ?Tenant
    {
        $data = $this->getItem($key);
        if ($data === null) {
            return null;
        }

        return new Tenant(
            id: $data['id'],
            name: $data['name'],
            metadata: new TenantMetadata($data['metadata']),
            aiAssistantId: $data['aiAssistantId'] ?? null,
            timezone: $data['timezone'] ?? 'Europe/Madrid',
            alias: $data['alias'] ?? '',
            countryCode: $data['countryCode'] ?? 'ES',
        );
    }

    public function save(Tenant $tenant): void
    {
        $this->setItem(
            $tenant->id,
            [
                'aiAssistantId' => $tenant->aiAssistantId,
                'id' => $tenant->id,
                'name' => $tenant->name,
                'metadata' => $tenant->metadata->data(null),
                'timezone' => $tenant->timezone,
                'alias' => $tenant->alias,
                'countryCode' => $tenant->countryCode,
            ],
            1209600,
        );
    }
}
