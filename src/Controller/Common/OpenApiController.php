<?php

declare(strict_types=1);

namespace Nektria\Controller\Common;

use Nektria\Controller\Controller;
use Nektria\Exception\ResourceNotFoundException;
use Nektria\Util\Controller\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[Route('/openapi')]
readonly class OpenApiController extends Controller
{
    #[Route(method: 'GET')]
    public function getOpenApiDocumentation(string $projectDir): BinaryFileResponse
    {
        $private = $this->requestData->getInt('private') === 1;
        $file = "{$projectDir}/openapi.yaml";
        if ($private) {
            $file = "{$projectDir}/openapi.private.yaml";
        }

        if (!file_exists($file)) {
            throw new ResourceNotFoundException('Documentation', null);
        }

        $response = new BinaryFileResponse($file);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'openapi.yaml');

        return $response;
    }
}
