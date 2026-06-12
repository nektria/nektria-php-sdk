<?php

declare(strict_types=1);

namespace Nektria\Util;

use Nektria\Util\File\CsvFileReader;
use Nektria\Util\File\FileReader;
use RuntimeException;

class FileUtil
{
    /**
     * @param string[][] $data
     */
    public static function createCsv(array $data, ?string $filename = null): string
    {
        $filename ??= '/tmp/' . StringUtil::uuid4();
        $path = $filename;
        touch($path);
        $temp = fopen($path, 'wb');

        if ($temp === false) {
            throw new RuntimeException('Cannot create temporary file');
        }

        foreach ($data as $fields) {
            fputcsv($temp, $fields);
        }

        fwrite($temp, '');
        fclose($temp);

        return $path;
    }

    public static function delete(string $file): void
    {
        if (unlink($file) === false) {
            throw new RuntimeException('Cannot delete file');
        }
    }

    public static function loadCsvReader(string $file, string $separator = ','): CsvFileReader
    {
        return new CsvFileReader($file, $separator);
    }

    public static function loadFileReader(string $file): FileReader
    {
        return new FileReader($file);
    }

    public static function read(string $file): string
    {
        $content = file_get_contents($file);
        if ($content === false) {
            throw new RuntimeException('Cannot read file');
        }

        return $content;
    }

    public static function tmpWrite(string $file, string $content): void
    {
        if (file_put_contents("/tmp/{$file}", $content) === false) {
            throw new RuntimeException("Cannot write file '{$file}'");
        }
    }

    public static function write(string $file, string $content): void
    {
        if (file_put_contents($file, $content) === false) {
            throw new RuntimeException("Cannot write file '{$file}'");
        }
    }
}
