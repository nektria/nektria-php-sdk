<?php

declare(strict_types=1);

namespace Nektria\Document;

use Nektria\Dto\TenantMetadata;
use Nektria\Service\ContextService;

readonly class Tenant extends Document
{
    public function __construct(
        public string $id,
        public string $name,
        public TenantMetadata $metadata,
        public ?string $aiAssistantId,
        public string $timezone,
        public string $alias,
        public string $countryCode,
    ) {
        parent::__construct();
    }

    protected function toArray(?ContextService $context): array
    {
        if ($context?->context() === ContextService::ADMIN) {
            return [
                'aiAssistantId' => $this->aiAssistantId,
                'id' => $this->id,
                'name' => $this->name,
                'metadata' => $this->metadata->toArray($context),
                'timezone' => $this->timezone,
                'alias' => $this->alias,
                'countryCode' => $this->countryCode,
            ];
        }

        if ($context?->isPublic() === true) {
            return [
                'id' => $this->id,
                'name' => $this->name,
                'availableTags' => $this->metadata->availableTags(),
                'timezone' => $this->timezone,
            ];
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'metadata' => $this->metadata->toArray($context),
            'timezone' => $this->timezone,
            'alias' => $this->alias,
            'countryCode' => $this->countryCode,
        ];
    }
}
