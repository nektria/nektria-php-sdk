<?php

declare(strict_types=1);

namespace Nektria\Exception;

class InvalidAuthorizationException extends NektriaException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Invalid credentials.',
            status: 401,
        );
    }
}
