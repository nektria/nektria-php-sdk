<?php

declare(strict_types=1);

namespace Nektria\Exception;

class MissingFieldRequiredToCreateClassException extends NektriaRuntimeException
{
    public function __construct(string $resource, string $field)
    {
        parent::__construct(
            errorCode: 'E_400',
            message: "Field '{$field}' is mandatory when creating a '{$resource}'."
        );
    }
}
