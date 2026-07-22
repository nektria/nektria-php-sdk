<?php

declare(strict_types=1);

namespace Nektria\Exception;

class ResourceNotFoundException extends NektriaException
{
    public function __construct(string $resourceType, ?string $ref)
    {
        parent::__construct(
            message: $ref === null
                ? "{$resourceType} not found."
                : "{$resourceType} '{$ref}' not found.",
            status: 404,
        );
    }
}
