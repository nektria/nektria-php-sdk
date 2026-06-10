<?php

declare(strict_types=1);

namespace Nektria\Exception;

class InvalidRequestParamException extends NektriaRuntimeException
{
    public function __construct(string $field, string $mustBeType)
    {
        parent::__construct(
            errorCode: 'E_400',
            message: "Invalid field '{$field}', {$mustBeType} is required."
        );
    }
}
