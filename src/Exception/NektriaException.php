<?php

declare(strict_types=1);

namespace Nektria\Exception;

use Throwable;

class NektriaException extends NektriaRuntimeException
{
    public static function new(Throwable $e): self
    {
        if ($e instanceof self) {
            return $e;
        }

        return new self('E_500', $e->getMessage(), $e);
    }

    public static function parse(Throwable $e): Throwable
    {
        if ($e instanceof self) {
            return $e->realException();
        }

        return $e;
    }

    public function realException(): Throwable
    {
        $current = $this;
        while ($current instanceof self && $current->getPrevious() !== null) {
            $current = $current->getPrevious();
        }

        return $current;
    }
}
