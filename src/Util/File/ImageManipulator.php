<?php

declare(strict_types=1);

namespace Nektria\Util\File;

interface ImageManipulator
{
    public function resizeTo(string $destFile, int $maxSize): void;
}
