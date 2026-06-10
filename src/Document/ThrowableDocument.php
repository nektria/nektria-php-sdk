<?php

declare(strict_types=1);

namespace Nektria\Document;

use InvalidArgumentException;
use Nektria\Exception\DomainException;
use Nektria\Exception\InsufficientCredentialsException;
use Nektria\Exception\InvalidAuthorizationException;
use Nektria\Exception\InvalidRequestParamException;
use Nektria\Exception\MissingFieldRequiredToCreateClassException;
use Nektria\Exception\MissingRequestParamException;
use Nektria\Exception\NektriaException;
use Nektria\Exception\NektriaRuntimeException;
use Nektria\Exception\RequestException;
use Nektria\Exception\ResourceNotFoundException;
use Nektria\Exception\TerminateException;
use Nektria\Service\ContextService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

readonly class ThrowableDocument extends Document
{
    public bool $silent;

    public int $status;

    public Throwable $throwable;

    private string $errorCode;

    public function __construct(
        Throwable $throwable
    ) {
        parent::__construct();
        $exception = $throwable;
        if ($exception instanceof NektriaException) {
            $this->silent = $exception->silent();
            $exception = $exception->realException();
        } else {
            $this->silent = false;
        }

        if ($exception instanceof DomainException) {
            $this->status = Response::HTTP_CONFLICT;
        } elseif ($exception instanceof \DomainException) {
            $this->status = Response::HTTP_CONFLICT;
        } elseif ($exception instanceof InvalidArgumentException) {
            $this->status = Response::HTTP_BAD_REQUEST;
        } elseif ($exception instanceof InsufficientCredentialsException) {
            $this->status = Response::HTTP_FORBIDDEN;
        } elseif ($exception instanceof InvalidAuthorizationException) {
            $this->status = Response::HTTP_UNAUTHORIZED;
        } elseif ($exception instanceof InvalidRequestParamException) {
            $this->status = Response::HTTP_BAD_REQUEST;
        } elseif ($exception instanceof MissingRequestParamException) {
            $this->status = Response::HTTP_BAD_REQUEST;
        } elseif ($exception instanceof MissingFieldRequiredToCreateClassException) {
            $this->status = Response::HTTP_BAD_REQUEST;
        } elseif ($exception instanceof ResourceNotFoundException) {
            $this->status = Response::HTTP_NOT_FOUND;
        } elseif ($exception instanceof HttpException) {
            $this->status = $exception->getStatusCode();
        } elseif ($exception instanceof RequestException) {
            $this->status = $exception->response()->status;
        } elseif ($exception instanceof TerminateException) {
            $this->status = Response::HTTP_CONFLICT;
        } else {
            $this->status = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        $this->errorCode = $exception instanceof NektriaRuntimeException
            ? $exception->errorCode
            : "E_{$this->status}";

        $this->throwable = $exception;
    }

    public function toDevArray(): mixed
    {
        $exception = $this->throwable;
        if ($exception instanceof NektriaException) {
            $exception = $exception->realException();
        }

        $message = $exception->getMessage();

        if ($this->status !== Response::HTTP_INTERNAL_SERVER_ERROR) {
            $message = $exception->getMessage();

            if ($exception instanceof RequestException) {
                try {
                    $message = $exception->response()->json()['message'];
                } catch (Throwable) {
                }
            }
        }

        return [
            'message' => $message,
            'type' => $exception::class,
            'file' => str_replace('/app/', '', $exception->getFile()),
            'line' => $exception->getLine(),
            'trace' => $this->trace(),
        ];
    }

    /**
     * @return array{
     *     file: string,
     *     line: int
     * }[]
     */
    public function trace(): array
    {
        $trace = $this->throwable->getTrace();
        $finalTrace = [];
        foreach ($trace as $item) {
            $file = $item['file'] ?? '';
            $line = $item['line'] ?? 0;
            if (str_starts_with($file, '/app/src')) {
                $finalTrace[] = [
                    'file' => str_replace('/app/', '', $file),
                    'line' => $line,
                ];
            }

            if (str_starts_with($file, '/app/vendor/nektria/php-sdk/src')) {
                $finalTrace[] = [
                    'file' => str_replace('/app/vendor/nektria/php-sdk/', '', $file),
                    'line' => $line,
                ];
            }
        }

        return $finalTrace;
    }

    protected function toArray(?ContextService $context): array
    {
        $exception = $this->throwable;
        if ($exception instanceof NektriaException) {
            $exception = $exception->realException();
        }

        $message = 'Internal Server Error';
        if ($context === null || $context->isPlayEnvironment()) {
            $message = $exception->getMessage();
        }

        if (
            $this->status !== Response::HTTP_INTERNAL_SERVER_ERROR
            || ($context !== null && $context->traceId() === '00000000-0000-4000-8000-000000000000')
        ) {
            $message = $exception->getMessage();
        }

        $data = [
            'errorCode' => $this->errorCode,
            'message' => $message,
        ];

        if (
            $context === null
            || $context->isPlayEnvironment()
            || $context->traceId() === '00000000-0000-4000-8000-000000000000'
        ) {
            $data['file'] = str_replace('/app/', '', $exception->getFile());
            $data['line'] = $exception->getLine();
            $data['trace'] = $this->trace();
        }

        return $data;
    }
}
