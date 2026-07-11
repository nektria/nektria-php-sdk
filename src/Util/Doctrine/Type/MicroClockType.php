<?php

declare(strict_types=1);

namespace Nektria\Util\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Nektria\Dto\Clock;

class MicroClockType extends Type
{
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Clock) {
            return $value->microDateTimeString();
        }

        return null;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Clock
    {
        if ($value === null) {
            return null;
        }

        return Clock::fromString($value);
    }

    public function getName(): string
    {
        return 'micro_clock';
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['precision'] = 6;

        return 'TIMESTAMP(0) WITHOUT TIME ZONE';
    }
}
