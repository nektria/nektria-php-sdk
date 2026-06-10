<?php

declare(strict_types=1);

namespace Nektria\Util\Annotation;

use Attribute;

#[Attribute]
class RolesRequired
{
    /** @var string[] */
    public readonly array $roles;

    /**
     * @param string[] $roles
     */
    public function __construct(array $roles)
    {
        $this->roles = $roles;
    }
}
