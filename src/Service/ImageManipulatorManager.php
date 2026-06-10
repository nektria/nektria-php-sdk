<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Exception\NektriaException;
use Nektria\Util\File\ImageManipulator;
use Nektria\Util\File\JpegImageManipulator;

readonly class ImageManipulatorManager extends AbstractService
{
    public function getManipulator(string $filename): ImageManipulator
    {

        $type = mime_content_type($filename);
        if ($type === 'image/jpeg') {
            return new JpegImageManipulator($filename);
        }

        throw new NektriaException('E_500', "There is no manipulator for '{$type}' images.");
    }
}
