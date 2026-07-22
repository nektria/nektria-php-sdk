<?php

declare(strict_types=1);

namespace Nektria\Exception;

class MissingRequestFileException extends NektriaException
{
    public function __construct(string $field)
    {
        parent::__construct(
            message: "Missing file '{$field}'.",
            status: 400,
        );
    }
}
