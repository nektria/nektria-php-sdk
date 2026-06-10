<?php

declare(strict_types=1);

namespace Nektria\Exception;

class MissingRequestFileException extends NektriaRuntimeException
{
    public function __construct(string $field)
    {
        parent::__construct(
            errorCode: 'E_400',
            message: "Missing file '{$field}'."
        );
    }
}
