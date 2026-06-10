<?php

declare(strict_types=1);

namespace Nektria\Infrastructure;

use Nektria\Document\Tenant;
use Nektria\Document\User;

interface SecurityServiceInterface
{
    public function authenticateApi(string $apiKey): void;

    public function authenticateSystem(string $tenantId): void;

    public function authenticateUser(string $apiKey): void;

    public function clearAuthentication(): void;

    public function currentTenant(): ?Tenant;

    public function currentUser(): ?User;

    public function retrieveCurrentTenant(): Tenant;

    public function retrieveCurrentUser(): User;

    /**
     * @param string[] $roles
     */
    public function validateRole(array $roles): void;
}
