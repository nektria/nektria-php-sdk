<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Document\Tenant;
use Nektria\Document\User;
use Nektria\Dto\LocalClock;
use Nektria\Dto\UserContainer;
use Nektria\Exception\InvalidAuthorizationException;
use Nektria\Infrastructure\SecurityServiceInterface;
use Nektria\Infrastructure\SharedUserV2Cache;

readonly class SecurityService extends AbstractService implements SecurityServiceInterface
{
    protected UserContainer $userContainer;

    public function __construct(
        protected SharedUserV2Cache $sharedUserCache,
    ) {
        parent::__construct();
        $this->userContainer = new UserContainer();
    }

    public function authenticateApi(string $apiKey): void
    {
        $this->clearAuthentication();

        if ($apiKey === '') {
            throw new InvalidAuthorizationException();
        }

        $user = $this->sharedUserCache->read("API_{$apiKey}");
        if ($user === null) {
            $user = $this->sharedUserCache->read($apiKey);
        }

        $this->userContainer->setUser($user);

        if ($user === null) {
            throw new InvalidAuthorizationException();
        }

        $this->contextService()->addExtra('tenantId', $user->tenantId);
        $this->contextService()->addExtra('tenantName', $user->tenant->name);
        $this->contextService()->addExtra('userId', $user->id);
        LocalClock::defaultTimezone($user->tenant->timezone);
    }

    public function authenticateSystem(string $tenantId): void
    {
        $this->clearAuthentication();

        $user = $this->sharedUserCache->read("SYSTEM_{$tenantId}");
        if ($user === null) {
            $user = $this->sharedUserCache->read("ADMIN_{$tenantId}");
        }

        $this->userContainer->setUser($user);

        if ($user === null || $user->apiKey === '') {
            throw new InvalidAuthorizationException();
        }

        $this->contextService()->addExtra('tenantId', $user->tenantId);
        $this->contextService()->addExtra('tenantName', $user->tenant->name);
        $this->contextService()->addExtra('userId', $user->id);
        LocalClock::defaultTimezone($user->tenant->timezone);
    }

    public function authenticateUser(string $apiKey): void
    {
        $this->clearAuthentication();

        if ($apiKey === '') {
            throw new InvalidAuthorizationException();
        }

        $user = $this->sharedUserCache->read($apiKey);
        $this->userContainer->setUser($user);

        if ($user === null) {
            throw new InvalidAuthorizationException();
        }

        $this->contextService()->addExtra('tenantId', $user->tenantId);
        $this->contextService()->addExtra('tenantName', $user->tenant->name);
        $this->contextService()->addExtra('userId', $user->id);

        LocalClock::defaultTimezone($user->tenant->timezone);
    }

    public function clearAuthentication(?string $apiKey = null): void
    {
        if ($apiKey !== null) {
            $data = $this->sharedUserCache->read("API_{$apiKey}");
            if ($data === null) {
                throw new InvalidAuthorizationException();
            }

            $currentUser = $this->retrieveCurrentUser();

            if ($currentUser->id !== $data->id) {
                throw new InvalidAuthorizationException();
            }

            $this->sharedUserCache->remove("API_{$apiKey}");
        }

        $this->contextService()->addExtra('tenantId', null);
        $this->contextService()->addExtra('tenantName', null);
        $this->contextService()->addExtra('userId', null);
        LocalClock::defaultTimezone('UTC');
        $this->userContainer->setUser(null);
    }

    public function currentTenant(): ?Tenant
    {
        return $this->userContainer->user()?->tenant;
    }

    public function currentUser(): ?User
    {
        return $this->userContainer->user();
    }

    public function retrieveCurrentTenant(): Tenant
    {
        return $this->retrieveCurrentUser()->tenant;
    }

    public function retrieveCurrentUser(): User
    {
        $user = $this->currentUser();
        if ($user === null) {
            throw new InvalidAuthorizationException();
        }

        return $user;
    }

    /**
     * @param string[] $roles
     */
    public function validateRole(array $roles): void
    {
        if ($this->currentUser() === null) {
            $this->roleManager()->checkAtLeast(RoleManager::ROLE_ANY, $roles);
        } else {
            $this->roleManager()->checkAtLeast($this->currentUser()->role, $roles);
        }
    }
}
