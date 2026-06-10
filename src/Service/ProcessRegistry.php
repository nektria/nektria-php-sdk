<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Dto\MutableMetadata;

readonly class ProcessRegistry extends AbstractService
{
    /**
     * @var MutableMetadata<string>
     */
    private MutableMetadata $metadata;

    public function __construct()
    {
        parent::__construct();
        $this->metadata = new MutableMetadata();
    }

    public function addValue(string $key, string | int | float | bool | null $value): void
    {
        $this->metadata->updateField($key, $value);
    }

    public function clear(): void
    {
        $this->metadata->clear();
    }

    /**
     * @return  MutableMetadata<string>
     */
    public function getMetadata(): MutableMetadata
    {
        return $this->metadata;
    }
}
