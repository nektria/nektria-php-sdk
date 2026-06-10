<?php

declare(strict_types=1);

namespace Nektria\Infrastructure;

use Nektria\Service\AlertService;

use function count;

/**
 * @phpstan-import-type AlertMessage from AlertService
 * @extends SharedRedisCache<AlertMessage[]>
 */
class SharedDiscordCache extends SharedRedisCache
{
    /**
     * @param AlertMessage $message
     */
    public function addMessage(string $channel, array $message): void
    {
        $items = $this->read($channel);

        if (count($items) >= 100) {
            array_shift($items);
        }

        $items[] = $message;
        $this->setItem($channel, $items, 3600);
    }

    /**
     * @return AlertMessage[]
     */
    public function read(string $channel): array
    {
        return $this->getItem($channel) ?? [];
    }

    public function remove(string $channel): void
    {
        $this->removeItem($channel);
    }

    public function removeLastMessage(string $channel): void
    {
        $items = $this->read($channel);
        array_pop($items);
        $this->setItem($channel, $items, 3600);
    }
}
