<?php

declare(strict_types=1);

namespace Nektria\Controller;

use Nektria\Document\ArrayDocument;
use Nektria\Document\Document;
use Nektria\Document\DocumentResponse;
use Nektria\Document\Tenant;
use Nektria\Document\User;
use Nektria\Exception\MissingRequestFileException;
use Nektria\Exception\MissingRequestParamException;
use Nektria\Infrastructure\BusInterface;
use Nektria\Infrastructure\SecurityServiceInterface;
use Nektria\Message\Command;
use Nektria\Message\Query;
use Nektria\Service\ContextService;
use Nektria\Service\ProcessRegistry;
use Nektria\Util\ArrayDataFetcher;
use Nektria\Util\File\FileReader;
use Nektria\Util\FileUtil;
use Nektria\Util\JsonUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class Controller
{
    protected Request $request;

    protected ArrayDataFetcher $requestData;

    public function __construct(
        protected SecurityServiceInterface $userService,
        protected ProcessRegistry $processRegistry,
        protected ContextService $context,
        protected BusInterface $bus,
        protected ContainerInterface $container,
        RequestStack $requestStack,
    ) {
        $this->request = $requestStack->getCurrentRequest() ?? new Request();
        $body = [];

        try {
            $data = [];
            $content = $this->request->getContent();
            if ($content === '') {
                $this->request->request->replace();
            } elseif ($content[0] === '[') {
                $data = JsonUtil::decode($content);
            } else {
                $data = JsonUtil::decode($content);
            }
            $body = $data;
        } catch (Throwable) {
        }
        $this->requestData = new ArrayDataFetcher(array_merge($this->request->query->all(), $body));
    }

    /**
     * @param array{
     *     currentTry: int,
     *     maxTries: int,
     *     interval: int,
     * }|null $retryOptions
     */
    protected function command(
        Command $command,
        ?string $transport = null,
        ?int $delayMs = null,
        ?array $retryOptions = null
    ): void {
        $this->bus->dispatchCommand($command, $transport, $delayMs, $retryOptions);
    }

    /**
     * @template T of Document
     * @param T $document
     */
    protected function documentResponse(Document $document, int $status = 200): DocumentResponse
    {
        return $this->response($document, $status);
    }

    /**
     * @param array{
     *     currentTry: int,
     *     maxTries: int,
     *     interval: int,
     * }|null $retryOptions
     */
    protected function emptyResponse(
        ?Command $command = null,
        ?string $transport = null,
        ?int $delayMs = null,
        ?array $retryOptions = null
    ): DocumentResponse {
        if ($command !== null) {
            $this->command($command, $transport, $delayMs, $retryOptions);
        }

        return new DocumentResponse(new ArrayDocument([]), $this->context, Response::HTTP_NO_CONTENT);
    }

    protected function getFile(string $field): ?string
    {
        /** @var UploadedFile|null $file */
        $file = $this->request->files->get($field);

        if ($file === null) {
            return null;
        }

        return $file->getRealPath();
    }

    protected function getFileReader(string $field, string $separator = ','): ?FileReader
    {
        /** @var UploadedFile|null $uploadedFile */
        $uploadedFile = $this->request->files->get($field);

        if ($uploadedFile === null) {
            return null;
        }

        $path = $uploadedFile->getRealPath();

        return match ($uploadedFile->getClientMimeType()) {
            'text/csv' => FileUtil::loadCsvReader($path, $separator),
            default => FileUtil::loadFileReader($path),
        };
    }

    /**
     * @template T of Document
     * @param Query<T> $query
     */
    protected function queryResponse(Query $query): DocumentResponse
    {
        return $this->documentResponse($this->bus->dispatchQuery($query));
    }

    protected function response(Document $document, int $status = 200): DocumentResponse
    {
        return new DocumentResponse($document, $this->context, $status);
    }

    protected function retrieveFile(string $field): string
    {
        $file = $this->getFile($field);

        if ($file === null) {
            throw new MissingRequestParamException($field);
        }

        return $file;
    }

    protected function retrieveFileReader(string $field, string $separator = ','): FileReader
    {
        $fileReader = $this->getFileReader($field, $separator);

        if ($fileReader === null) {
            throw new MissingRequestFileException($field);
        }

        return $fileReader;
    }

    protected function retrieveTenant(): Tenant
    {
        return $this->userService->retrieveCurrentUser()->tenant;
    }

    protected function retrieveTenantId(): string
    {
        return $this->retrieveUser()->tenantId;
    }

    protected function retrieveUser(): User
    {
        return $this->userService->retrieveCurrentUser();
    }
}
