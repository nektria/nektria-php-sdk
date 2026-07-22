<?php

declare(strict_types=1);

namespace Nektria\Exception;

class MissingRequestParamException extends NektriaException
{
    public function __construct(string $field)
    {
        parent::__construct(
            message: "Missing field '{$field}'.",
            status: 400,
        );
    }
}
