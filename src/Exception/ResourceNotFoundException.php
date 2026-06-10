<?php

declare(strict_types=1);

namespace Nektria\Exception;

class ResourceNotFoundException extends NektriaRuntimeException
{
    public function __construct(string $resourceType, ?string $ref)
    {
        parent::__construct(
            errorCode: 'E_404',
            message: $ref === null
                ? "{$resourceType} not found."
                : "{$resourceType} '{$ref}' not found.",
        );
    }
}
