<?php

declare(strict_types=1);

namespace Nektria\Controller\Admin;

use Nektria\Controller\Controller;
use Nektria\Document\ArrayDocument;
use Nektria\Document\DocumentResponse;
use Nektria\Exception\DomainException;
use Nektria\Exception\InsufficientCredentialsException;
use Nektria\Infrastructure\ArrayDocumentReadModel;
use Nektria\Service\ContextService;
use Nektria\Util\Controller\Route;
use Nektria\Util\FileUtil;
use Nektria\Util\JsonUtil;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;

use const STR_PAD_LEFT;

#[Route('/api/admin/tools')]
readonly class ToolsController extends Controller
{
    #[Route('/debug', method: 'PATCH')]
    public function configureDebug(ContextService $contextService): JsonResponse
    {
        $enable = $this->requestData->retrieveBool('enable');
        $lifetime = $this->requestData->getInt('lifetime') ?? 3600;
        $projects = $this->requestData->retrieveStringArray('projects');

        $contextService->setDebugMode($enable, $projects, $lifetime);

        return $this->emptyResponse();
    }

    #[Route('/decrypt', method: 'PATCH')]
    public function decodeAllVariables(ContextService $contextService): JsonResponse
    {
        if (!$contextService->isLocalEnvironment()) {
            throw new InsufficientCredentialsException();
        }

        $pass = $this->requestData->getString('pass') ?? $contextService->project();

        return $this->documentResponse(new ArrayDocument(JsonUtil::decode(
            (string) base64_decode(
                (string) openssl_decrypt(
                    $this->requestData->retrieveString('hash'),
                    'AES-256-CBC',
                    $pass,
                    0,
                    str_pad($pass, 16, '0', STR_PAD_LEFT),
                ),
                true,
            ),
        )));
    }

    #[Route('/queues/{queue}', method: 'DELETE')]
    public function deleteAQueue(ArrayDocumentReadModel $arrayDocumentReadModel, string $queue): DocumentResponse
    {
        $arrayDocumentReadModel->deleteAQueue($queue);

        return $this->emptyResponse();
    }

    #[Route('/rabbit/delete', method: 'PATCH')]
    public function deleteARabbitQueue(): DocumentResponse
    {
        throw new DomainException('E_500', 'Not implemented');
    }

    #[Route('/console', method: 'PATCH')]
    public function executeAConsoleCommand(): Response
    {
        $command = $this->requestData->retrieveString('command');
        if (!str_starts_with($command, 'admin:') && !str_starts_with($command, 'sdk:')) {
            throw new InsufficientCredentialsException();
        }

        $args = $this->requestData->getArray('args') ?? [];
        $args[] = '--clean';
        $command = new Process(array_merge(['php', '-d', 'memory_limit=2G', '../bin/console', $command], $args));
        $command->setTimeout(600);
        $command->run();

        return $this->buildResponseForProcess($command);
    }

    #[Route('/rabbit-delays/enable', method: 'PATCH')]
    public function executeDisableRabbitDelays(): JsonResponse
    {
        throw new DomainException('E_500', 'Not implemented');
    }

    #[Route('/database/migrations', method: 'GET')]
    public function executeDoctrineMigrationStatus(): Response
    {
        $command = new Process(array_merge(['../bin/console', 'doctrine:migrations:sync-metadata-storage']));
        $command->run();

        $command = new Process(array_merge(['../bin/console', 'doctrine:migration:status']));
        $command->run();

        return $this->buildResponseForProcess($command);
    }

    #[Route('/database/schema', method: 'GET')]
    public function executeDoctrineSchemaUpdateDumpSql(): Response
    {
        $command = new Process(array_merge(['../bin/console', 'doctrine:schema:update', '--dump-sql']));
        $command->run();

        return $this->buildResponseForProcess($command);
    }

    #[Route('/rabbit-delays/enable', method: 'PATCH')]
    public function executeEnableRabbitDelays(): JsonResponse
    {
        throw new DomainException('E_500', 'Not implemented');
    }

    #[Route('/queues', method: 'GET')]
    public function getAllQueuesMessages(ArrayDocumentReadModel $arrayDocumentReadModel): DocumentResponse
    {
        $list = $arrayDocumentReadModel->readAllQueuesMessages();

        $queues = $list->classify('queue_name');
        $data = [];

        foreach ($queues as $name => $messages) {
            $data[$name] = $messages->data($this->context);
        }

        return $this->documentResponse(new ArrayDocument($data));
    }

    #[Route('/queues/{ref}', method: 'GET')]
    public function getAllQueuesValueMessages(
        string $ref,
        ArrayDocumentReadModel $arrayDocumentReadModel
    ): DocumentResponse {
        $list = $arrayDocumentReadModel->readValuesFromQueueMessages(
            queue: $ref,
            field: $this->requestData->retrieveString('field'),
        );

        return $this->documentResponse($list);
    }

    #[Route('/debug/status', method: 'PATCH')]
    public function getDebugConfigurationStatus(ContextService $contextService): JsonResponse
    {
        return new JsonResponse($contextService->debugModes(
            $this->requestData->retrieveStringArray('projects'),
        ));
    }

    #[Route('/rabbit/status', method: 'GET')]
    public function getRabbitStatus(): DocumentResponse
    {
        throw new DomainException('E_500', 'Not implemented');
    }

    #[Route('/crypt', method: 'GET')]
    public function readAllVariables(ContextService $contextService): Response
    {
        $lines = explode("\n", FileUtil::read('/app/.env'));
        $envs = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            $parts = explode('=', $line);
            $env = $parts[0];
            $envs[$env] = getenv()[$env] ?? $parts[1];
        }

        $result = openssl_encrypt(
            base64_encode(JsonUtil::encode($envs)),
            'AES-256-CBC',
            $contextService->project(),
            0,
            str_pad($contextService->project(), 16, '0', STR_PAD_LEFT),
        );

        return new Response((string) $result);
    }

    #[Route('/database/read', method: 'POST')]
    public function readFromDatabase(ArrayDocumentReadModel $readModel): DocumentResponse
    {
        return $this->documentResponse(
            $readModel->readCustom(
                table: $this->requestData->retrieveString('table'),
                order: $this->requestData->retrieveString('orderBy'),
                page: $this->requestData->getInt('page') ?? 1,
                limit: $this->requestData->getInt('limit') ?? 50,
                filters: $this->requestData->getArray('filters') ?? []
            ),
        );
    }

    private function buildResponseForProcess(Process $process): Response
    {
        $result = $process->getExitCode() === 0 ? "Sucessful\n\n" : "Failure\n\n";
        $result .= "OUTPUT\n\n";
        $result .= $process->getOutput();
        if ($process->getErrorOutput() !== '') {
            $result .= "\n\n";
            $result .= "ERROR\n\n";
            $result .= $process->getErrorOutput();
        }

        return new Response($result, Response::HTTP_OK, ['Content-Type' => 'text/plain']);
    }
}
