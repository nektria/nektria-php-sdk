<?php

declare(strict_types=1);

namespace Nektria\Controller\Admin;

use Nektria\Controller\Controller;
use Nektria\Document\ArrayDocument;
use Nektria\Exception\InsufficientCredentialsException;
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

    #[Route('/debug/status', method: 'PATCH')]
    public function getDebugConfigurationStatus(ContextService $contextService): JsonResponse
    {
        return new JsonResponse($contextService->debugModes(
            $this->requestData->retrieveStringArray('projects'),
        ));
    }

    #[Route('/crypt', method: 'GET')]
    public function readAllVariables(ContextService $contextService): Response
    {
        $lines = explode("\n", FileUtil::read('/workspace/.env'));
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
