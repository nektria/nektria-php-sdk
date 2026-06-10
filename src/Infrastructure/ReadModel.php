<?php

declare(strict_types=1);

namespace Nektria\Infrastructure;

use Doctrine\ORM\EntityManagerInterface;
use Nektria\Document\Document;
use Nektria\Document\DocumentCollection;
use Nektria\Document\NewDocumentCollection;
use Nektria\Document\PaginatedDocumentCollection;
use Nektria\Exception\NektriaException;
use Nektria\Util\StringUtil;
use Throwable;

use function count;
use function is_array;

/**
 * @template T of Document
 */
abstract class ReadModel
{
    private static int $defaultPageSize = 100;

    private EntityManagerInterface $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public static function setDefaultPageSize(int $pageSize): void
    {
        self::$defaultPageSize = $pageSize;
    }

    /**
     * @return string[]
     */
    public function groupResults(): array
    {
        return [];
    }

    public function manager(): EntityManagerInterface
    {
        return $this->manager;
    }

    /**
     * @param mixed[] $params
     * @return T
     */
    abstract protected function buildDocument(array $params): Document;

    /**
     * @param array<string, string|int|float|bool|string[]|null> $params
     * @return NewDocumentCollection<T>
     */
    protected function getNewResults(string $sql, array $params = []): NewDocumentCollection
    {
        $results = $this->getRawResults($sql, $params, $this->groupResults());
        $parsed = [];

        foreach ($results as $item) {
            $parsed[] = $this->buildDocument($item);
        }

        return new NewDocumentCollection($parsed);
    }

    /**
     * @param array<string, string|int|float|bool|string[]|null> $params
     * @return PaginatedDocumentCollection<T>
     */
    protected function getPaginatedResult(
        string $sql,
        ?int $page = null,
        ?int $limit = null,
        array $params = []
    ): PaginatedDocumentCollection {
        $page ??= 1;
        $limit ??= self::$defaultPageSize;
        $limit = min($limit, 999);
        $offset = ($page - 1) * $limit;

        $sql = StringUtil::trim($sql);
        if (!str_starts_with($sql, 'SELECT')) {
            $sql = "{$this->source()} {$sql}";
        }

        $sqls = explode('FROM', $sql);
        $sql = "{$sqls[0]}, COUNT(*) OVER() AS __total__ FROM {$sqls[1]} LIMIT :__limit__ OFFSET :__offset__";
        $params['__limit__'] = $limit;
        $params['__offset__'] = $offset;
        $results = $this->getRawResults($sql, $params, $this->groupResults());
        $parsed = [];

        foreach ($results as $item) {
            $parsed[] = $this->buildDocument($item);
        }

        return new PaginatedDocumentCollection(
            new NewDocumentCollection($parsed),
            $page,
            $limit,
            (int) ($results[0]['__total__'] ?? 0),
        );
    }

    /**
     * @param array<string, string|int|float|bool|null> $params
     * @param string[] $groupBy
     * @return mixed[]|null
     */
    protected function getRawResult(string $sql, array $params = [], array $groupBy = []): ?array
    {
        try {
            $results = $this->getRawResults($sql, $params, $groupBy);

            return $results[array_key_first($results) ?? ''] ?? null;
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }
    }

    /**
     * @param array<string, string|int|float|bool|string[]|null> $params
     * @param string[] $groupBy
     * @return mixed[]
     */
    protected function getRawResults(string $sql, array $params = [], array $groupBy = []): array
    {
        $sql = StringUtil::trim($sql);
        if (!str_starts_with($sql, 'SELECT')) {
            $sql = "{$this->source()} {$sql}";
        }

        $newParams = [];
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $value = "'" . implode("','", $value) . "'";
                $sql = str_replace(":$key", $value, $sql);
            } else {
                $newParams[$key] = $value;
            }
        }

        try {
            $query = $this->manager->getConnection()->prepare($sql);
            foreach ($newParams as $key => $value) {
                $query->bindValue($key, $value);
            }
            $results = $query->executeQuery()->fetchAllAssociative();

            if (count($groupBy) > 0) {
                $newResults = [];
                foreach ($results as $item) {
                    $key = '';
                    foreach ($groupBy as $group) {
                        $key .= $item[$group];
                    }

                    $newResults[$key] ??= [];
                    $newResults[$key][] = $item;
                }

                $results = $newResults;
            }

            return $results;
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }
    }

    /**
     * @param array<string, string|int|float|bool|null> $params
     * @return T|null
     */
    protected function getResult(string $sql, array $params = []): ?Document
    {
        $result = $this->getRawResult($sql, $params, $this->groupResults());

        if ($result === null) {
            return null;
        }

        return $this->buildDocument($result);
    }

    /**
     * @param array<string, string|int|float|bool|string[]|null> $params
     * @return DocumentCollection<T>
     */
    protected function getResults(string $sql, array $params = []): DocumentCollection
    {
        $results = $this->getRawResults($sql, $params, $this->groupResults());
        $parsed = [];

        foreach ($results as $item) {
            $parsed[] = $this->buildDocument($item);
        }

        return new DocumentCollection($parsed);
    }

    abstract protected function source(): string;
}
