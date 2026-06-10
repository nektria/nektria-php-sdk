<?php

declare(strict_types=1);

namespace Nektria\Util;

use JsonException;
use Nektria\Exception\NektriaException;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_UNICODE;

class JsonUtil
{
    public static function decode(string $data): mixed
    {
        try {
            return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw NektriaException::new($e);
        }
    }

    public static function encode(mixed $data, bool $pretty = false): string
    {
        $prettyFlag = $pretty ? JSON_PRETTY_PRINT : 0;

        try {
            return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR | $prettyFlag);
        } catch (JsonException $e) {
            throw NektriaException::new($e);
        }
    }

    /**
     * @return mixed[]
     */
    public static function file(string $file): array
    {
        return self::decode(FileUtil::read($file));
    }
}
