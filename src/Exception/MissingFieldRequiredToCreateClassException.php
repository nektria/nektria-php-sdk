<?php

declare(strict_types=1);

namespace Nektria\Exception;

class MissingFieldRequiredToCreateClassException extends NektriaException
{
    public function __construct(string $resource, string $field)
    {
        parent::__construct(
            message: "Field '{$field}' is mandatory when creating a '{$resource}'.",
            status: 400,
        );
    }
}
