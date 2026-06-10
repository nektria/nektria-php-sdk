<?php

declare(strict_types=1);

namespace Nektria\Infrastructure;

use Nektria\Document\WarehouseSharedInfo;
use Nektria\Exception\ResourceNotFoundException;

/**
 * @extends SharedRedisCache<WarehouseSharedInfo>
 */
class SharedWarehouseCache extends SharedRedisCache
{
    public function read(string $id): WarehouseSharedInfo
    {
        $data = $this->getItem($id);

        if ($data === null) {
            throw new ResourceNotFoundException('Warehouse', $id);
        }

        $this->save($data);

        return $data;
    }

    public function remove(string $id): void
    {
        $this->removeItem($id);
    }

    public function save(WarehouseSharedInfo $warehouse): void
    {
        $this->setItem(
            $warehouse->id,
            $warehouse,
            1209600,
        );
    }
}
