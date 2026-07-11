<?php

declare(strict_types=1);

namespace Nektria\Controller\Admin;

use Nektria\Controller\Controller;
use Nektria\Document\ArrayDocument;
use Nektria\Document\DocumentResponse;
use Nektria\Infrastructure\ArrayDocumentReadModel;
use Nektria\Util\Controller\Route;

#[Route('/api/admin/queue')]
readonly class QueueController extends Controller
{
    #[Route('/queues/{queue}', method: 'DELETE')]
    public function deleteAQueue(ArrayDocumentReadModel $arrayDocumentReadModel, string $queue): DocumentResponse
    {
        $arrayDocumentReadModel->deleteAQueue($queue);

        return $this->emptyResponse();
    }

    #[Route('/queues', method: 'GET')]
    public function getAllQueuesMessages(ArrayDocumentReadModel $arrayDocumentReadModel): DocumentResponse
    {
        $list = $arrayDocumentReadModel->readAllQueuesMessages();

        $queues = $list->classify('queue_name');
        $data = [];

        foreach ($queues as $name => $messages) {
            $data[$name] = $messages->data($this->context);
        }

        return $this->documentResponse(new ArrayDocument($data));
    }

    #[Route('/queues/{ref}', method: 'GET')]
    public function getAllQueuesValueMessages(
        string $ref,
        ArrayDocumentReadModel $arrayDocumentReadModel
    ): DocumentResponse {
        $list = $arrayDocumentReadModel->readValuesFromQueueMessages(
            queue: $ref,
            field: $this->requestData->retrieveString('field'),
        );

        return $this->documentResponse($list);
    }
}
