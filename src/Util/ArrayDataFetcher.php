<?php

declare(strict_types=1);

namespace Nektria\Util;

use Nektria\Dto\Address;
use Nektria\Dto\Clock;
use Nektria\Dto\LocalClock;
use Nektria\Dto\Metadata;
use Nektria\Exception\InvalidRequestParamException;
use Nektria\Exception\MissingRequestParamException;
use Throwable;

use function count;
use function is_array;
use function is_bool;
use function is_object;
use function is_string;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

readonly class ArrayDataFetcher
{
    /**
     * @param mixed[] $data
     */
    public function __construct(protected readonly array $data)
    {
    }

    /**
     * @return mixed[]
     */
    public function data(): array
    {
        return $this->data;
    }

    public function getAddress(string $field): ?Address
    {
        if (!$this->hasField($field)) {
            return null;
        }

        $latitude = $this->getFloat("{$field}.latitude");
        $longitude = $this->getFloat("{$field}.longitude");

        if (
            $latitude === null
            || $longitude === null
            || $latitude === 0.0
            || $longitude === 0.0
        ) {
            $latitude = 0.0;
            $longitude = 0.0;
        }

        Validate::latitude($latitude);
        Validate::longitude($longitude);

        return new Address(
            addressLine1: $this->retrieveString("{$field}.addressLine1"),
            addressLine2: $this->getString("{$field}.addressLine2") ?? '',
            elevator: $this->getBool("{$field}.elevator") ?? true,
            postalCode: $this->retrieveString("{$field}.postalCode"),
            city: $this->retrieveString("{$field}.city"),
            countryCode: $this->retrieveString("{$field}.countryCode"),
            latitude: $latitude,
            longitude: $longitude,
        );
    }

    /**
     * @return mixed[]|null
     */
    public function getArray(string $field): ?array
    {
        $value = $this->getValue($field);

        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            throw new InvalidRequestParamException($field, 'array');
        }

        return $value;
    }

    public function getBool(string $field): ?bool
    {
        $value = $this->getValue($field);

        if ($value === null) {
            return null;
        }

        if ($value === 'true' || $value === 'false') {
            return $value === 'true';
        }

        if (is_bool($value)) {
            return $value;
        }

        throw new InvalidRequestParamException($field, 'bool');
    }

    public function getClock(string $field): ?Clock
    {
        $value = $this->getValue($field);

        if ($value === null) {
            return null;
        }

        try {
            return Clock::fromString($value)->setTimezone('UTC');
        } catch (Throwable) {
            throw new InvalidRequestParamException($field, 'datetime');
        }
    }

    public function getClockAsLocal(string $field, string $timezone): ?Clock
    {
        $value = $this->getClock($field);

        return $value?->replaceTimezone($timezone)->removeTimeZone();
    }

    public function getClockFromLocal(string $field, string $timezone): ?Clock
    {
        $value = $this->getLocalClock($field);

        if ($value === null) {
            return null;
        }

        return $value->toUTC($timezone);
    }

    public function getClockTz(string $field): ?Clock
    {
        return $this->getClock($field);
    }

    /**
     * @return float[]|null
     */
    public function getCoordinates(string $field): ?array
    {
        if ($this->hasField($field)) {
            return [
                $this->retrieveFloat("{$field}.latitude"),
                $this->retrieveFloat("{$field}.longitude"),
            ];
        }

        return null;
    }

    public function getDate(string $field): ?Clock
    {
        return $this->getClock($field);
    }

    public function getFloat(string $field): ?float
    {
        $value = $this->getValue($field);

        if ($value === null) {
            return null;
        }

        if (!is_numeric($value)) {
            throw new InvalidRequestParamException($field, 'float');
        }

        return (float) $value;
    }

    public function getId(string $field): ?string
    {
        $val = $this->getString($field);

        ValidateOpt::uuid4($val);

        return $val;
    }

    /**
     * @return string[]|null
     */
    public function getIdsCSV(string $field): ?array
    {
        $value = $this->getString($field);

        if ($value === null) {
            throw new MissingRequestParamException($field);
        }

        if ($value === '') {
            return [];
        }

        return explode(',', $value);
    }

    public function getInt(string $field): ?int
    {
        $value = $this->getValue($field);

        if ($value === null) {
            return null;
        }

        if (is_array($value) || is_object($value)) {
            throw new InvalidRequestParamException($field, 'int');
        }

        if (((string) (int) $value) !== (string) $value) {
            throw new InvalidRequestParamException($field, 'int');
        }

        return max(PHP_INT_MIN, min((int) $value, PHP_INT_MAX));
    }

    /**
     * @return int[]|null
     */
    public function getIntArray(string $field): ?array
    {
        if (!$this->hasField($field)) {
            return null;
        }

        $ret = [];
        $length = $this->retrieveLength($field);
        for ($i = 0; $i < $length; ++$i) {
            $ret[] = $this->retrieveInt("{$field}.{$i}");
        }

        return $ret;
    }

    public function getLength(string $field): int
    {
        $fieldParts = explode('.', $field);
        $currentValue = $this->data[$fieldParts[0]] ?? null;
        $length = count($fieldParts);

        if ($length === 1) {
            if ($currentValue === null) {
                return 0;
            }

            if (!is_array($currentValue)) {
                return 0;
            }

            return count($currentValue);
        }

        if (!is_array($currentValue)) {
            return 0;
        }

        for ($i = 1; $i < $length; ++$i) {
            $part = $fieldParts[$i];
            $currentValue = $currentValue[$part] ?? null;

            if (!is_array($currentValue)) {
                return 0;
            }

            if ($i === $length - 1) {
                return count($currentValue);
            }
        }

        return 0;
    }

    public function getLocalClock(string $field): ?LocalClock
    {
        $value = $this->getValue($field);

        if ($value === null) {
            return null;
        }

        try {
            return LocalClock::fromString($value)->setTimezone('UTC');
        } catch (Throwable) {
            throw new InvalidRequestParamException($field, 'datetime');
        }
    }

    public function getLocalDate(string $field): ?LocalClock
    {
        return $this->getLocalClock($field);
    }

    public function getMetadata(string $field): ?Metadata
    {
        $value = $this->getArray($field);

        if ($value === null) {
            return null;
        }

        return new Metadata($value);
    }

    public function getString(string $field): ?string
    {
        $value = $this->getValue($field);

        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new InvalidRequestParamException($field, 'string');
        }

        return StringUtil::trim($value);
    }

    /**
     * @return string[]|null
     */
    public function getStringArray(string $field): ?array
    {
        if (!$this->hasField($field)) {
            return null;
        }

        $ret = [];
        $length = $this->retrieveLength($field);
        for ($i = 0; $i < $length; ++$i) {
            $ret[] = $this->retrieveString("{$field}.{$i}");
        }

        return $ret;
    }

    public function hasField(string $field): bool
    {
        return $this->getValue($field) !== null;
    }

    public function retrieveAddress(string $field): Address
    {
        $value = $this->getAddress($field);

        if ($value === null) {
            throw new MissingRequestParamException($field);
        }

        return $value;
    }

    /**
     * @return mixed[]
     */
    public function retrieveArray(string $field): array
    {
        $value = $this->getArray($field);

        if ($value === null) {
            throw new MissingRequestParamException($field);
        }

        return $value;
    }

    public function retrieveBool(string $field): bool
    {
        $value = $this->getBool($field);

        if ($value === null) {
            throw new MissingRequestParamException($field);
        }

        return $value;
    }

    public function retrieveClock(string $field): Clock
    {
        $value = $this->getClock($field);

        if ($value === null) {
            throw new MissingRequestParamException($field);
        }

        return $value;
    }

    public function retrieveClockFromLocal(string $field, string $timezone): Clock
    {
        $value = $this->getClockFromLocal($field, $timezone);

        if ($value === null) {
            throw new MissingRequestParamException($field);
        }

        return $value;
    }

    public function retrieveClockTz(string $field): Clock
    {
        return $this->retrieveClock($field);
    }

    public function retrieveDate(string $field): Clock
    {
        return $this->retrieveClock($field);
    }

    public function retrieveFloat(string $field): float
    {
        $value = $this->getFloat($field);

        if ($value === null) {
            throw new MissingRequestParamException($field);
        }

        return $value;
    }

    public function retrieveId(string $field): string
    {
        $value = $this->retrieveString($field);

        Validate::uuid4($value);

        return $value;
    }

    /**
     * @return string[]
     */
    public function retrieveIdsCSV(string $field): array
    {
        $value = $this->getIdsCSV($field);

        if ($value === null) {
            throw new MissingRequestParamException($field);
        }

        return $value;
    }

    public function retrieveInt(string $field): int
    {
        $value = $this->getInt($field);

        if ($value === null) {
            throw new MissingRequestParamException($field);
        }

        return $value;
    }

    /**
     * @return int[]
     */
    public function retrieveIntArray(string $field): array
    {
        $value = $this->getIntArray($field);
        if ($value === null) {
            throw new MissingRequestParamException($field);
        }

        return $value;
    }

    public function retrieveLength(string $field): int
    {
        $fieldParts = explode('.', $field);
        $currentValue = $this->data[$fieldParts[0]] ?? null;
        $length = count($fieldParts);
        $acummulative = $fieldParts[0];

        if ($length === 1) {
            if ($currentValue === null) {
                throw new MissingRequestParamException($field);
            }

            if (!is_array($currentValue)) {
                throw new InvalidRequestParamException($acummulative, 'array');
            }

            return count($currentValue);
        }

        if (!is_array($currentValue)) {
            if (is_numeric($fieldParts[1])) {
                throw new InvalidRequestParamException($acummulative, 'array');
            }

            throw new InvalidRequestParamException($acummulative, 'object');
        }

        for ($i = 1; $i < $length; ++$i) {
            $part = $fieldParts[$i];
            $acummulative .= ".{$part}";

            $currentValue = $currentValue[$part] ?? null;

            if ($i === $length - 1) {
                if (!is_array($currentValue)) {
                    throw new InvalidRequestParamException($acummulative, 'array');
                }

                return count($currentValue);
            }

            if (!is_array($currentValue)) {
                if (is_numeric($fieldParts[$i + 1])) {
                    throw new InvalidRequestParamException($acummulative, 'array');
                }

                throw new InvalidRequestParamException($acummulative, 'object');
            }
        }

        return 0;
    }

    public function retrieveLocalClock(string $field): LocalClock
    {
        $value = $this->getLocalClock($field);

        if ($value === null) {
            throw new MissingRequestParamException($field);
        }

        return $value;
    }

    public function retrieveLocalDate(string $field): LocalClock
    {
        $value = $this->getLocalClock($field);

        if ($value === null) {
            throw new MissingRequestParamException($field);
        }

        return $value;
    }

    public function retrieveMetadata(string $field): Metadata
    {
        $value = $this->getMetadata($field);

        if ($value === null) {
            throw new MissingRequestParamException($field);
        }

        return $value;
    }

    public function retrieveString(string $field): string
    {
        $value = $this->getString($field);

        if ($value === null) {
            throw new MissingRequestParamException($field);
        }

        return $value;
    }

    /**
     * @return string[]
     */
    public function retrieveStringArray(string $field): array
    {
        $value = $this->getStringArray($field);
        if ($value === null) {
            throw new MissingRequestParamException($field);
        }

        return $value;
    }

    protected function getValue(string $field): mixed
    {
        if (!str_contains($field, '.')) {
            $value = $this->data[$field] ?? null;

            if ($value === null) {
                return null;
            }

            if (is_string($value)) {
                $value = StringUtil::trim($value);
            }

            if ($value === 'null') {
                return null;
            }

            if ($value === 'true') {
                return true;
            }

            if ($value === 'false') {
                return false;
            }

            return $value;
        }

        $fieldParts = explode('.', $field);
        $currentValue = $this->data[$fieldParts[0]] ?? null;
        $length = count($fieldParts);

        if (!is_array($currentValue)) {
            return null;
        }

        for ($i = 1; $i < $length; ++$i) {
            $part = $fieldParts[$i];

            if ($i === ($length - 1)) {
                $value = $currentValue[$part] ?? null;

                if ($value === null) {
                    return null;
                }

                if (is_string($value)) {
                    $value = StringUtil::trim($value);
                }

                if ($value === 'null') {
                    return null;
                }

                if ($value === 'true') {
                    return true;
                }

                if ($value === 'false') {
                    return false;
                }

                return $value;
            }

            $currentValue = $currentValue[$part] ?? null;

            if (!is_array($currentValue)) {
                return null;
            }
        }

        return null;
    }
}
