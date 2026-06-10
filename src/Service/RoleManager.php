<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Exception\InsufficientCredentialsException;

use function in_array;

readonly class RoleManager extends AbstractService
{
    private const array HIERARCHY = [
        self::ROLE_SYSTEM => [
            self::ROLE_ADMIN,
            self::ROLE_USER,
            self::ROLE_API,
            self::ROLE_GRID_VIEWER,
            self::ROLE_WAREHOUSE_SUPERVISOR,
            self::ROLE_DRIVER,
            self::ROLE_TRAFFIC_CHIEF,
            self::ROLE_TRAFFIC_CHIEF_EXPRESS,
            self::ROLE_MARKETING,
        ],
        self::ROLE_ADMIN => [
            self::ROLE_SYSTEM,
            self::ROLE_API,
            self::ROLE_USER,
            self::ROLE_GRID_VIEWER,
            self::ROLE_WAREHOUSE_SUPERVISOR,
            self::ROLE_DRIVER,
            self::ROLE_TRAFFIC_CHIEF,
            self::ROLE_TRAFFIC_CHIEF_EXPRESS,
            self::ROLE_MARKETING,
        ],
        self::ROLE_DRIVER => [],
        self::ROLE_GRID_VIEWER => [],
        self::ROLE_TRAFFIC_CHIEF => [
            self::ROLE_TRAFFIC_CHIEF_EXPRESS,
            self::ROLE_DRIVER,
        ],
        self::ROLE_TRAFFIC_CHIEF_EXPRESS => [
            self::ROLE_TRAFFIC_CHIEF,
            self::ROLE_DRIVER,
        ],
        self::ROLE_USER => [
            self::ROLE_API,
            self::ROLE_GRID_VIEWER,
            self::ROLE_WAREHOUSE_SUPERVISOR,
            self::ROLE_DRIVER,
            self::ROLE_TRAFFIC_CHIEF,
            self::ROLE_TRAFFIC_CHIEF_EXPRESS,
            self::ROLE_MARKETING,
        ],
        self::ROLE_API => [
            self::ROLE_USER,
            self::ROLE_GRID_VIEWER,
            self::ROLE_WAREHOUSE_SUPERVISOR,
            self::ROLE_DRIVER,
            self::ROLE_TRAFFIC_CHIEF,
            self::ROLE_TRAFFIC_CHIEF_EXPRESS,
            self::ROLE_MARKETING,
        ],
        self::ROLE_WAREHOUSE_SUPERVISOR => [self::ROLE_GRID_VIEWER],
        self::ROLE_MARKETING => [],
    ];

    public const string ROLE_ADMIN = 'ROLE_ADMIN';

    public const string ROLE_ANY = 'ROLE_ANY';

    public const string ROLE_API = 'ROLE_API';

    public const string ROLE_DRIVER = 'ROLE_DRIVER';

    public const string ROLE_GRID_VIEWER = 'ROLE_GRID_VIEWER';

    public const string ROLE_MARKETING = 'ROLE_MARKETING';

    public const string ROLE_PUBLIC = 'ROLE_PUBLIC';

    public const string ROLE_SYSTEM = 'ROLE_SYSTEM';

    public const string ROLE_TRAFFIC_CHIEF = 'ROLE_TRAFFIC_CHIEF';

    public const string ROLE_TRAFFIC_CHIEF_EXPRESS = 'ROLE_TRAFFIC_CHIEF_EXPRESS';

    public const string ROLE_USER = 'ROLE_USER';

    public const string ROLE_WAREHOUSE_SUPERVISOR = 'ROLE_WAREHOUSE_SUPERVISOR';

    /**
     * @param string[] $targetRoles
     */
    public function canAtLeast(string $role, array $targetRoles): bool
    {
        if ($role === self::ROLE_ADMIN) {
            return true;
        }

        if ($role === self::ROLE_SYSTEM) {
            return true;
        }

        if (!isset(self::HIERARCHY[$role])) {
            return false;
        }

        if (in_array(self::ROLE_ANY, $targetRoles, true)) {
            return true;
        }

        if (in_array($role, $targetRoles, true)) {
            return true;
        }

        foreach ($targetRoles as $targetRole) {
            $roleIsGranted = $targetRole === $role || in_array($targetRole, self::HIERARCHY[$role], true);

            if ($roleIsGranted) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string[] $targetRoles
     */
    public function checkAtLeast(string $role, array $targetRoles): void
    {
        if ($role === self::ROLE_ADMIN) {
            return;
        }

        if ($role === self::ROLE_SYSTEM) {
            return;
        }

        if (!isset(self::HIERARCHY[$role])) {
            throw new InsufficientCredentialsException();
        }

        if (in_array(self::ROLE_ANY, $targetRoles, true)) {
            return;
        }

        if (in_array($role, $targetRoles, true)) {
            return;
        }

        foreach ($targetRoles as $targetRole) {
            $roleIsGranted = $targetRole === $role || in_array($targetRole, self::HIERARCHY[$role], true);

            if ($roleIsGranted) {
                return;
            }
        }

        throw new InsufficientCredentialsException();
    }

    /**
     * @return string[]
     */
    public function roles(): array
    {
        return array_keys(self::HIERARCHY);
    }
}
