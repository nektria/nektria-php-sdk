<?php

declare(strict_types=1);

namespace Nektria\Util\File;

use Generator;

readonly class CsvFileReader extends FileReader
{
    public function __construct(
        string $file,
        private string $separator
    ) {
        parent::__construct($file);
    }

    /**
     * @return Generator<string[]>
     */
    public function csvLines(): Generator
    {
        $row = fgetcsv($this->resource, separator: $this->separator);
        while ($row !== false) {
            $fixRow = [];
            foreach ($row as $value) {
                $fixRow[] = $value ?? '';
            }

            yield $fixRow;

            $row = fgetcsv($this->resource, separator: $this->separator);
        }

        return null;
    }
}
