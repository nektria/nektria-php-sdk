<?php

declare(strict_types=1);

namespace Nektria\Exception;

use RuntimeException;
use Throwable;

abstract class NektriaRuntimeException extends RuntimeException
{
    public readonly string $errorCode;

    private bool $silent;

    public function __construct(
        string $errorCode,
        string $message,
        ?Throwable $previous = null,
        bool $silent = false
    ) {
        parent::__construct($message, 0, $previous);
        $this->silent = $silent;
        $this->errorCode = $errorCode;
    }

    public function makeSilent(): void
    {
        $this->silent = true;
    }

    public function silent(): bool
    {
        return $this->silent;
    }
}
