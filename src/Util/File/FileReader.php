<?php

declare(strict_types=1);

namespace Nektria\Util\File;

use Generator;
use Nektria\Exception\NektriaException;

readonly class FileReader
{
    /** @var resource */
    protected mixed $resource;

    public function __construct(
        protected string $file,
    ) {
        $resource = fopen($file, 'r+b');
        if ($resource === false) {
            throw new NektriaException('Cannot open file');
        }

        $this->resource = $resource;
    }

    /**
     * @return Generator<string>
     */
    public function lines(): Generator
    {
        $row = fgets($this->resource);
        while ($row !== false) {
            yield $row;

            $row = fgets($this->resource);
        }

        return null;
    }
}
