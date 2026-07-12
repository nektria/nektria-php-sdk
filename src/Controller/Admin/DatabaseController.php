<?php

declare(strict_types=1);

namespace Nektria\Controller\Admin;

use Nektria\Controller\Controller;
use Nektria\Document\DocumentResponse;
use Nektria\Infrastructure\ArrayDocumentReadModel;
use Nektria\Util\Controller\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;

#[Route('/api/admin/databases')]
readonly class DatabaseController extends Controller
{
    #[Route('/migrations', method: 'GET')]
    public function executeDoctrineMigrationStatus(): Response
    {
        $command = new Process(array_merge(['../bin/console', 'doctrine:migrations:sync-metadata-storage']));
        $command->run();

        $command = new Process(array_merge(['../bin/console', 'doctrine:migration:status']));
        $command->run();

        return $this->buildResponseForProcess($command);
    }

    #[Route('/schema', method: 'GET')]
    public function executeDoctrineSchemaUpdateDumpSql(): Response
    {
        $command = new Process(array_merge(['../bin/console', 'doctrine:schema:update', '--dump-sql']));
        $command->run();

        return $this->buildResponseForProcess($command);
    }

    #[Route('/read', method: 'POST')]
    public function readFromDatabase(ArrayDocumentReadModel $readModel): DocumentResponse
    {
        return $this->documentResponse(
            $readModel->readCustom(
                table: $this->requestData->retrieveString('table'),
                order: $this->requestData->retrieveString('orderBy'),
                page: $this->requestData->getInt('page') ?? 1,
                limit: $this->requestData->getInt('limit') ?? 50,
                filters: $this->requestData->getArray('filters') ?? [],
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
