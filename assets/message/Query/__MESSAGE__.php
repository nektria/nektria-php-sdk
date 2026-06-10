<?php

declare(strict_types=1);

namespace App\Message\__ENTITY__;

use App\Document\__ENTITY__;
use Nektria\Message\Query;
use Nektria\Service\RoleManager;
use Nektria\Util\Annotation\RolesRequired;

/**
 * @implements Query<__ENTITY__>
 */
#[RolesRequired([RoleManager::ROLE_USER])]
readonly class __MESSAGE__ implements Query
{
    public function __construct(
        public string $__ENTITY_CC__Id,
    ) {
    }
}
