<?php

declare(strict_types=1);

namespace Nektria\Infrastructure;

use Nektria\Document\ArrayDocument;
use Nektria\Document\Document;
use Nektria\Document\DocumentCollection;

/**
 * @extends ReadModel<ArrayDocument>
 */
class ArrayDocumentReadModel extends ReadModel
{
    public function deleteAQueue(string $queue): void
    {
        $this->getRawResult(
            '
                DELETE 
                FROM messenger_messages 
                WHERE queue_name ~ :queue
            ',
            [
                'queue' => $queue
            ]
        );
    }

    public function fixMigrations(): void
    {
        $this->getRawResult('
           ALTER TABLE doctrine_migration_versions ALTER executed_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE
        ');
    }

    /**
     * @return DocumentCollection<ArrayDocument>
     */
    public function readAllQueuesMessages(): DocumentCollection
    {
        return $this->getResults(
            <<<'SQL'
                SELECT 
                    replace((regexp_match(body, 'O:\d+:\\"(App\\\\Message\\\\[A-Za-z0-9\\\\]+)\\"'))[1],'\\', '\') 
                        AS class_name,
                    COUNT(*) as count,
                    queue_name,
                    SUM((delivered_at IS NOT NULL)::int) as in_process,
                    SUM((delivered_at IS NULL)::int) as pending
                FROM messenger_messages
                GROUP BY class_name, queue_name
                ORDER BY class_name ASC;
            SQL
        );
    }

    /**
     * @param array<string, string|int|float|bool|null> $filters
     * @return DocumentCollection<ArrayDocument>
     */
    public function readCustom(string $table, string $order, int $page, int $limit, array $filters): DocumentCollection
    {
        $table = str_replace([';', ' '], '', $table);
        $order = str_replace([';', ' '], '', $order);

        $query = '';
        foreach (array_keys($filters) as $filter) {
            $filter = str_replace([';', ' ', "'", '"'], '', $filter);
            $query .= " AND {$filter}=:{$filter}";
        }

        return $this->getResults("
            SELECT *
            FROM {$table}
            WHERE true {$query}
            ORDER BY {$order} DESC
            LIMIT :limit OFFSET :offset
        ", [...$filters, ...[
            'offset' => ($page - 1) * $limit,
            'limit' => $limit,
        ]]);
    }

    /**
     * @return DocumentCollection<ArrayDocument>
     */
    public function readMigrations(): DocumentCollection
    {
        return $this->getResults('
            SELECT *
            FROM doctrine_migration_versions
            ORDER BY version
        ');
    }

    /**
     * @return DocumentCollection<ArrayDocument>
     */
    public function readValuesFromQueueMessages(string $queue, string $field): DocumentCollection
    {
        return $this->getResults(
            <<<'SQL'
                SELECT
                    id,
                    queue_name,
                    replace((regexp_match(body, 'O:\d+:\\"(App\\\\Message\\\\[A-Za-z0-9\\\\]+)\\"'))[1],'\\', '\') 
                        AS class_name,
                    :field AS field,
                    (regexp_match(body, :body))[1] AS value
                FROM messenger_messages 
                WHERE queue_name ~ :queue
                ORDER BY id ASC
                LIMIT 100
            SQL,
            [
                'body' => $field . '\\\\";\\s*s:\\d+:\\\\"([^"]+)\\\\"',
                'queue' => $queue,
                'field' => $field,
            ]
        );
    }

    protected function buildDocument(array $params): Document
    {
        return new ArrayDocument($params);
    }

    protected function source(): string
    {
        return '';
    }
}
