<?php

declare(strict_types=1);

namespace Nektria\Exception;

class InsufficientCredentialsException extends NektriaRuntimeException
{
    public function __construct()
    {
        parent::__construct(
            errorCode: 'E_403',
            message: 'Insufficient credentials.'
        );
    }
}
