<?php

declare(strict_types=1);

namespace Nektria\Exception;

class InvalidRequestParamException extends NektriaException
{
    public function __construct(string $field, string $mustBeType)
    {
        parent::__construct(
            message: "Invalid field '{$field}', {$mustBeType} is required.",
            status: 400,
        );
    }
}
