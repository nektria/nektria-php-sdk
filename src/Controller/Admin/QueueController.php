<?php

declare(strict_types=1);

namespace Nektria\Controller\Admin;

use Nektria\Controller\Controller;
use Nektria\Document\ArrayDocument;
use Nektria\Document\DocumentResponse;
use Nektria\Infrastructure\ArrayDocumentReadModel;
use Nektria\Util\Controller\Route;

#[Route('/api/admin/queues')]
readonly class QueueController extends Controller
{
    #[Route('/{queue}', method: 'DELETE')]
    public function deleteAQueue(ArrayDocumentReadModel $arrayDocumentReadModel, string $queue): DocumentResponse
    {
        $arrayDocumentReadModel->deleteAQueue($queue);

        return $this->emptyResponse();
    }

    #[Route('/{queue}/{field}/{value}', method: 'DELETE')]
    public function deleteAQueueWithValue(
        ArrayDocumentReadModel $arrayDocumentReadModel,
        string $queue,
        string $field,
        string $value,
    ): DocumentResponse {
        $arrayDocumentReadModel->deleteQueuesFromFieldAndValue(
            $queue,
            field: $field,
            value: $value,
        );

        return $this->emptyResponse();
    }

    #[Route('', method: 'GET')]
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

    #[Route('/{ref}', method: 'GET')]
    public function getAllQueuesValueMessages(
        string $ref,
        ArrayDocumentReadModel $arrayDocumentReadModel,
    ): DocumentResponse {
        $list = $arrayDocumentReadModel->readValuesFromQueueMessages(
            queue: $ref,
            field: $this->requestData->retrieveString('field'),
        );

        return $this->documentResponse($list);
    }
}
