<?php

declare(strict_types=1);

namespace Nektria\Util\File;

use Nektria\Exception\NektriaException;

readonly class JpegImageManipulator implements ImageManipulator
{
    public function __construct(
        public string $filename
    ) {
    }

    public function resizeTo(string $destFile, int $maxSize): void
    {
        $image = imagecreatefromjpeg($this->filename);
        $data = getimagesize($this->filename);
        if ($data === false || $image === false) {
            throw new NektriaException('E_409', 'Invalid Jpeg image.');
        }

        $with = $data[0];
        $height = $data[1];

        $currentMaxSize = max($with, $height);
        $newWidth = max((int) ($maxSize * ($with / $currentMaxSize)), 1);
        $newHeight = max((int) ($maxSize * ($height / $currentMaxSize)), 1);

        $destImage = imagecreatetruecolor($newWidth, $newHeight);

        if ($destImage === false) {
            throw new NektriaException('E_409', 'Could not create image.');
        }

        imagecopyresampled($destImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $with, $height);
        imagejpeg($destImage, $destFile);
    }
}
