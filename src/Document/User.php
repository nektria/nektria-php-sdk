<?php

declare(strict_types=1);

namespace Nektria\Document;

use Nektria\Dto\SocketInfo;
use Nektria\Service\ContextService;

readonly class User extends Document
{
    private SocketInfo $socketInfo;

    /**
     * @param string[] $warehouses
     */
    public function __construct(
        public string $id,
        public string $email,
        public string $name,
        public array $warehouses,
        public string $apiKey,
        public string $role,
        public string $tenantId,
        public Tenant $tenant,
        public ?string $dniNie,
        public ?string $aiThreadId,
    ) {
        parent::__construct();
        $this->socketInfo = new SocketInfo();
    }

    /**
     * @param string[] $allowedTopics
     */
    public function appendSockets(string $token, array $allowedTopics): void
    {
        $this->socketInfo->appendSockets($token, $allowedTopics);
    }

    protected function toArray(?ContextService $context): array
    {
        if ($context?->isPublic() === true) {
            return [
                'email' => $this->email,
                'id' => $this->id,
                'name' => $this->name,
                'role' => $this->role,
                'warehouses' => $this->warehouses,
            ];
        }

        if ($context !== null && $context->context() === ContextService::INTERNAL) {
            return [
                'allowedTopics' => $this->socketInfo->topics(),
                'dniNie' => $this->dniNie,
                'email' => $this->email,
                'name' => $this->name,
                'id' => $this->id,
                'language' => 'en',
                'role' => $this->role,
                'socketsToken' => $this->socketInfo->socketsToken(),
                'tenant' => $this->tenant->toArray($context),
                'warehouses' => $this->warehouses,
            ];
        }

        return [
            'aiThreadId' => $this->aiThreadId,
            'allowedTopics' => $this->socketInfo->topics(),
            'apiKey' => $this->apiKey,
            'dniNie' => $this->dniNie,
            'email' => $this->email,
            'id' => $this->id,
            'language' => 'en',
            'name' => $this->name,
            'role' => $this->role,
            'socketsToken' => $this->socketInfo->socketsToken(),
            'tenant' => $this->tenant->toArray($context),
            'warehouses' => $this->warehouses,
        ];
    }
}
