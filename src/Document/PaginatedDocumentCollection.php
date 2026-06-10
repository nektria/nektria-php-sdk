<?php

declare(strict_types=1);

namespace Nektria\Document;

use Nektria\Service\ContextService;

/**
 * @template T of Document
 */
readonly class PaginatedDocumentCollection extends Document
{
    /**
     * @param NewDocumentCollection<T> $items
     */
    public function __construct(
        public NewDocumentCollection $items,
        public int $page,
        public int $pageSize,
        public int $totalItems,
    ) {
        parent::__construct();
    }

    protected function toArray(?ContextService $context): array
    {
        $data = $this->items->toArray($context);
        $data['info'] = [
            'page' => $this->page,
            'pageSize' => $this->pageSize,
            'totalItems' => $this->totalItems,
            'totalPages' => (int) ceil($this->totalItems / $this->pageSize),
        ];

        return $data;
    }
}
