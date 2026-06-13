<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Document\Document;
use Nektria\Document\ThrowableDocument;
use Nektria\Util\JsonUtil;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Throwable;

readonly class NewSocketService extends AbstractService
{
    public function __construct(
        private HubInterface $hub,
        private AlertService $alertService,
        private ?string $mercureHost,
        private ?string $mercureToken,
        private ?string $project,
    ) {
        parent::__construct();
    }

    public function publishSimpleMessage(string $topic, string $message): void
    {
        if ($this->mercureToken === 'none' || $this->mercureHost === 'none') {
            return;
        }

        try {
            $this->hub->publish(new Update(
                "{$this->project}_{$topic}",
                $message
            ));
        } catch (Throwable $e) {
            $this->alertService->sendThrowable(
                tenantName: $this->securityService()->retrieveCurrentTenant()->name,
                method: 'SOCKET',
                path: "{$this->project}_{$topic}",
                input: ['message' => $message],
                document: new ThrowableDocument($e),
            );
        }
    }

    public function publishToResource(string $id, string | Document $data, ?string $context = null): void
    {
        $payload = $data instanceof Document ? $data->data($this->contextService()) : $data;
        if ($context === null) {
            $context = (string) $data;
        }

        $this->publishSimpleMessage(
            $id,
            JsonUtil::encode([
                'context' => $context,
                'payload' => $payload,
            ])
        );
    }

    public function publishToTenant(string | Document $data, ?string $context = null): void
    {
        $payload = $data instanceof Document ? $data->data($this->contextService()) : $data;
        if ($context === null) {
            $context = (string) $data;
        }

        $this->publishSimpleMessage(
            $this->securityService()->retrieveCurrentTenant()->id,
            JsonUtil::encode([
                'context' => $context,
                'payload' => $payload,
            ])
        );
    }

    public function publishToUser(string | Document $data, ?string $context = null): void
    {
        $payload = $data instanceof Document ? $data->data($this->contextService()) : $data;
        if ($context === null) {
            $context = (string) $data;
        }

        $this->publishSimpleMessage(
            $this->securityService()->retrieveCurrentUser()->id,
            JsonUtil::encode([
                'context' => $context,
                'payload' => $payload,
            ])
        );
    }
}
