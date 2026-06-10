<?php

declare(strict_types=1);

namespace Nektria\Infrastructure;

use Nektria\Document\User;

/**
 * @extends SharedRedisCache<array{
 *     aiThreadId: string|null,
 *     apiKey: string,
 *     dniNie: string|null,
 *     email: string,
 *     id: string,
 *     name: string,
 *     role: string,
 *     tenantId: string,
 *     warehouses: string[],
 * }>
 */
class SharedUserV2Cache extends SharedRedisCache
{
    public function __construct(
        private readonly SharedTenantCache $sharedTenantCache,
        string $redisDsn,
        string $env
    ) {
        parent::__construct($redisDsn, $env);
    }

    public function read(string $key): ?User
    {
        $data = $this->getItem($key);

        if ($data === null) {
            return null;
        }

        if ($data['id'] === $key) {
            $tenant = $this->sharedTenantCache->read($data['tenantId']);

            if ($tenant === null) {
                return null;
            }

            $user = new User(
                id: $data['id'],
                email: $data['email'],
                name: $data['name'],
                warehouses: $data['warehouses'],
                apiKey: $data['apiKey'],
                role: $data['role'],
                tenantId: $tenant->id,
                tenant: $tenant,
                dniNie: $data['dniNie'],
                aiThreadId: $data['aiThreadId'] ?? null,
            );

            $this->save($key, $user);

            return $user;
        }

        $user = $this->read($data['id']);

        if ($user !== null) {
            $this->save($key, $user);
        }

        return $user;
    }

    public function remove(string $id): void
    {
        $this->removeItem($id);
    }

    public function save(string $key, User $user): void
    {
        $data = [
            'aiThreadId' => $user->aiThreadId,
            'apiKey' => $user->apiKey,
            'dniNie' => $user->dniNie,
            'email' => $user->email,
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
            'tenantId' => $user->tenant->id,
            'warehouses' => $user->warehouses,
        ];

        if ($key !== $user->id) {
            $this->setItem(
                $user->id,
                $data,
                1209600,
            );
        }

        $this->setItem(
            $key,
            $data,
            1209600,
        );
    }
}
