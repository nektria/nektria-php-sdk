<?php

declare(strict_types=1);

namespace Nektria\Exception;

class InsufficientCredentialsException extends NektriaException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Insufficient credentials.',
            status: 403,
        );
    }
}
