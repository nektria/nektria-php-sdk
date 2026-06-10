<?php

declare(strict_types=1);

namespace Nektria\Infrastructure;

/**
 * @extends SharedRedisCache<bool>
 */
class SharedInvalidCoordinatesCache extends SharedRedisCache
{
    public function delete(float $latitude, float $longitude): void
    {
        $this->removeItem("{$latitude},{$longitude}");
    }

    public function isInvalid(float $latitude, float $longitude): bool
    {
        return $this->getItem("{$latitude},{$longitude}") ?? true;
    }

    public function save(float $latitude, float $longitude, bool $isInvalid): void
    {
        $this->setItem("{$latitude},{$longitude}", $isInvalid, 86400);
    }
}
