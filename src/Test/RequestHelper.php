<?php

declare(strict_types=1);

namespace Nektria\Test;

use Nektria\Dto\Clock;
use Nektria\Util\JsonUtil;
use Nektria\Util\StringUtil;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function count;

/**
 * @mixin TestCase
 */
trait RequestHelper
{
    private string $apiId = '';

    private string $requestArea = '';

    protected static function assertEmptyResponse(Response $response): void
    {
        self::assertResponseStatus(Response::HTTP_NO_CONTENT, $response);
    }

    protected static function assertResponseStatus(int $expected, Response $response): void
    {
        $responseContent = $response->getContent();

        if ($responseContent === false) {
            throw new RuntimeException('There is no response');
        }

        if (
            $response->getStatusCode() === Response::HTTP_NO_CONTENT
            || $response->getStatusCode() === Response::HTTP_OK
        ) {
            $decodedJson = [];
        } else {
            $decodedJson = JsonUtil::decode($responseContent);
        }

        self::assertSame($expected, $response->getStatusCode(), $decodedJson['message'] ?? '');
    }

    public function area(): string
    {
        return $this->requestArea;
    }

    public function initRequestHelper(): void
    {
        $this->requestArea = StringUtil::uuid4();
        $this->apiId = '';
    }

    public function resetArea(): void
    {
        $this->requestArea = StringUtil::uuid4();
    }

    protected function behaveAs(string $user): void
    {
        $this->apiId = $user;
    }

    protected function getLastResponse(): Response
    {
        return $this->client->getResponse();
    }

    /**
     * @return mixed[]
     */
    protected function getResponseContent(): array
    {
        $responseContent = $this->client->getResponse()->getContent();

        if ($responseContent === false) {
            throw new RuntimeException('There is no response');
        }

        try {
            return JsonUtil::decode($responseContent);
        } catch (Throwable) {
            return ['no-content'];
        }
    }

    protected function sendDelete(string $path): void
    {
        $path = $this->fillString($path);
        $headers = ['Content-Type' => 'application/json'];
        $headers['HTTP_X-Api-Id'] = $this->apiId;
        $this->client->request('DELETE', $path, [], [], $headers, '{}');
    }

    /**
     * @param array<string|bool|int|float|null> $body
     */
    protected function sendGet(string $path, array $body = []): void
    {
        $path = $this->fillString($path);
        $encodedJson = $this->fillString(JsonUtil::encode($body));
        $query = '';
        $first = true;
        if (count($body) > 0) {
            foreach ($body as $param => $value) {
                if ($first) {
                    $first = false;
                    $query .= '?';
                } else {
                    $query .= '&';
                }
                $query .= "{$param}={$value}";
            }
        }
        $query = $this->fillString($query);

        $headers = ['Content-Type' => 'application/json'];
        $headers['HTTP_X-Api-Id'] = $this->apiId;
        $this->client->request('GET', $path . $query, [], [], $headers, $encodedJson);
    }

    /**
     * @param mixed[] $body
     */
    protected function sendPatch(string $path, array $body): void
    {
        $path = $this->fillString($path);
        $encodedJson = $this->fillString(JsonUtil::encode($body));
        $headers = ['Content-Type' => 'application/json'];
        $headers['HTTP_X-Api-Id'] = $this->apiId;
        $this->client->request('PATCH', $path, $body, [], $headers, $encodedJson);
    }

    /**
     * @param mixed[] $body
     */
    protected function sendPost(string $path, array $body): void
    {
        $path = $this->fillString($path);
        $encodedJson = $this->fillString(JsonUtil::encode($body));
        $headers = ['Content-Type' => 'application/json'];
        $headers['HTTP_X-Api-Id'] = $this->apiId;
        $this->client->request('POST', $path, $body, [], $headers, $encodedJson);
    }

    /**
     * @param mixed[] $body
     */
    protected function sendPut(string $path, array $body): void
    {
        $path = $this->fillString($path);
        $encodedJson = $this->fillString(JsonUtil::encode($body));
        $headers = ['Content-Type' => 'application/json'];
        $headers['HTTP_X-Api-Id'] = $this->apiId;
        $this->client->request('PUT', $path, $body, [], $headers, $encodedJson);
    }

    private function fillString(string $text): string
    {
        return str_replace(
            [
                ':warehouseId',
                ':tenantId',
                ':date',
                ':area',
                ':id',
                ':string',
                ':test',
            ],
            [
                self::WAREHOUSE_ID,
                self::TENANT_ID,
                Clock::now()->dateString(),
                $this->requestArea,
                StringUtil::uuid4(),
                StringUtil::uuid4(),
                $this->getTestName(),
            ],
            $text,
        );
    }
}
