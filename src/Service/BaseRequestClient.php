<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Dto\RequestResponse;
use Nektria\Exception\NektriaException;
use Nektria\Exception\RequestException;
use Nektria\Util\JsonUtil;
use Nektria\Util\StringUtil;
use Symfony\Component\HttpClient\HttpClient;
use Throwable;

readonly class BaseRequestClient extends AbstractService
{
    /**
     * @param array<string, string|int|bool|float> $data
     * @param array<string, string> $headers
     * @param array<string, string|bool|number> $options
     */
    public function delete(
        string $url,
        array $data = [],
        array $headers = [],
        array $options = [],
        ?bool $enableDebugFallback = null
    ): RequestResponse {
        return $this->request(
            'DELETE',
            $url,
            data: $data,
            headers: $headers,
            options: $options,
            enableDebugFallback: $enableDebugFallback,
        );
    }

    /**
     * @param mixed[] $data
     * @param array<string, string> $headers
     * @param array<string, string|bool|number> $options
     */
    public function file(
        string $url,
        string $filename,
        array $data = [],
        array $headers = [],
        array $options = [],
    ): RequestResponse {
        return $this->fileRequest(
            $url,
            $filename,
            data: $data,
            headers: $headers,
            options: $options,
        );
    }

    /**
     * @param mixed[] $data
     * @param array<string, string> $headers
     * @param array<string, string> $filenames
     * @param array<string, string|bool|number> $options
     */
    public function files(
        string $url,
        array $filenames,
        array $data = [],
        array $headers = [],
        array $options = [],
    ): RequestResponse {

        return $this->filesRequest(
            $url,
            $filenames,
            data: $data,
            headers: $headers,
            options: $options,
        );
    }

    /**
     * @param array<string, string|int|bool|float> $data
     * @param array<string, string> $headers
     * @param array<string, string|bool|number> $options
     */
    public function get(
        string $url,
        array $data = [],
        array $headers = [],
        array $options = [],
        ?bool $enableDebugFallback = null
    ): RequestResponse {
        return $this->request(
            'GET',
            $url,
            data: $data,
            headers: $headers,
            options: $options,
            enableDebugFallback: $enableDebugFallback,
        );
    }

    /**
     * @param mixed[] $data
     * @param array<string, string> $headers
     * @param array<string, string|bool|number> $options
     */
    public function patch(
        string $url,
        array $data = [],
        array $headers = [],
        array $options = [],
        bool $sendBodyAsObject = false,
        ?bool $enableDebugFallback = null
    ): RequestResponse {
        return $this->request(
            'PATCH',
            $url,
            data: $data,
            headers: $headers,
            options: $options,
            sendBodyAsObject: $sendBodyAsObject,
            enableDebugFallback: $enableDebugFallback,
        );
    }

    /**
     * @param mixed[] $data
     * @param array<string, string> $headers
     * @param array<string, string|bool|number> $options
     */
    public function post(
        string $url,
        array $data = [],
        array $headers = [],
        array $options = [],
        bool $sendBodyAsObject = false,
        ?bool $enableDebugFallback = null
    ): RequestResponse {
        return $this->request(
            'POST',
            $url,
            data: $data,
            headers: $headers,
            options: $options,
            sendBodyAsObject: $sendBodyAsObject,
            enableDebugFallback: $enableDebugFallback,
        );
    }

    /**
     * @param mixed[] $data
     * @param array<string, string> $headers
     * @param array<string, string|bool|number> $options
     */
    public function put(
        string $url,
        array $data = [],
        array $headers = [],
        array $options = [],
        bool $sendBodyAsObject = false,
        ?bool $enableDebugFallback = null
    ): RequestResponse {
        return $this->request(
            'PUT',
            $url,
            data: $data,
            headers: $headers,
            options: $options,
            sendBodyAsObject: $sendBodyAsObject,
            enableDebugFallback: $enableDebugFallback,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return [];
    }

    /**
     * @param mixed[] $data
     * @param array<string, string> $headers
     * @param array<string, string|bool|number> $options
     */
    private function fileRequest(
        string $url,
        string $filename,
        array $data = [],
        array $headers = [],
        array $options = [],
    ): RequestResponse {
        $body = fopen($filename, 'rb');
        if ($body === false) {
            throw new NektriaException('E_500', "Cannot open file {$filename}.");
        }

        $contentType = mime_content_type($filename);

        $defaultHeaders = $this->defaultHeaders();
        $defaultHeaders['Content-Type'] = $contentType;
        $defaultHeaders['Content-Length'] = filesize($filename);
        $headers = array_merge($defaultHeaders, $headers);

        $options['verify_peer'] = false;
        $options['verify_host'] = false;
        $options['headers'] = $headers;

        try {
            $params = '';
            foreach ($data as $key => $value) {
                if ($value === true) {
                    $value = 'true';
                } elseif ($value === false) {
                    $value = 'false';
                }
                if ($params !== '') {
                    $params .= '&';
                }
                $params .= "{$key}={$value}";
            }
            if ($params !== '') {
                $url .= "?{$params}";
            }

            $options['body'] = $body;

            $start = microtime(true);
            $client = HttpClient::create();
            $response = $client->request(
                'POST',
                $url,
                $options,
            );

            $content = $response->getContent(false);
            $status = $response->getStatusCode();
            $respHeaders = $response->getHeaders(false);

            $cookies = [];

            foreach ($response->getInfo()['response_headers'] as $header) {
                $headerParts = explode(':', $header);

                if ($headerParts[0] === 'Set-Cookie') {
                    $parts = explode(';', $headerParts[1]);
                    $cookie = explode('=', $parts[0]);
                    $cookies[StringUtil::trim($cookie[0])] = StringUtil::trim($cookie[1]);
                }
            }

            $response = new RequestResponse(
                'POST',
                $url,
                $status,
                $content,
                $headers,
                $respHeaders,
                $cookies
            );

            (microtime(true) - $start) * 1000;
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }

        if ($status >= 500) {
            $errorContent = $content;

            try {
                $errorContent = JsonUtil::decode($content);
            } catch (Throwable) {
            }

            $this->logService()->error([
                'method' => 'POST',
                'request' => $data,
                'response' => $errorContent,
                'status' => $status,
                'url' => $url,
            ], "POST {$url} failed with status {$status}");

            throw new RequestException($response);
        }

        if ($status >= 400) {
            $errorContent = $content;

            try {
                $errorContent = JsonUtil::decode($content);
            } catch (Throwable) {
            }

            $this->logService()->warning([
                'method' => 'POST',
                'request' => $data,
                'response' => $errorContent,
                'status' => $status,
                'url' => $url,
            ], "POST {$url} failed with status {$status}");

            throw new RequestException($response);
        }

        return $response;
    }

    /**
     * @param mixed[] $data
     * @param array<string, string> $headers
     * @param array<string, string> $filenames
     * @param array<string, string|bool|number> $options
     */
    private function filesRequest(
        string $url,
        array $filenames,
        array $data = [],
        array $headers = [],
        array $options = [],
    ): RequestResponse {
        $body = [];
        foreach ($filenames as $key => $filename) {
            $resource = fopen($filename, 'rb');
            if ($resource === false) {
                throw new NektriaException('E_500', "Cannot open file {$filename}.");
            }

            $body[$key] = $resource;
        }

        $headers = array_merge($this->defaultHeaders(), $headers);

        $options['verify_peer'] = false;
        $options['verify_host'] = false;
        $options['headers'] = $headers;

        try {
            $params = '';
            foreach ($data as $key => $value) {
                if ($value === true) {
                    $value = 'true';
                } elseif ($value === false) {
                    $value = 'false';
                }
                if ($params !== '') {
                    $params .= '&';
                }
                $params .= "{$key}={$value}";
                $body[$key] = $value;
            }
            if ($params !== '') {
                $url .= "?{$params}";
            }

            $options['body'] = $body;

            $client = HttpClient::create();
            $response = $client->request(
                'POST',
                $url,
                $options,
            );

            $cookies = [];

            foreach ($response->getInfo()['response_headers'] as $header) {
                $headerParts = explode(':', $header);

                if ($headerParts[0] === 'Set-Cookie') {
                    $parts = explode(';', $headerParts[1]);
                    $cookie = explode('=', $parts[0]);
                    $cookies[StringUtil::trim($cookie[0])] = StringUtil::trim($cookie[1]);
                }
            }

            $content = $response->getContent(false);
            $status = $response->getStatusCode();
            $respHeaders = $response->getHeaders(false);

            $response = new RequestResponse(
                'POST',
                $url,
                $status,
                $content,
                $headers,
                $respHeaders,
                $cookies
            );
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }

        if ($status >= 500) {
            $errorContent = $content;

            try {
                $errorContent = JsonUtil::decode($content);
            } catch (Throwable) {
            }

            $this->logService()->error([
                'method' => 'POST',
                'request' => $data,
                'response' => $errorContent,
                'status' => $status,
                'url' => $url,
            ], "POST {$url} failed with status {$status}");

            throw new RequestException($response);
        }

        if ($status >= 400) {
            $errorContent = $content;

            try {
                $errorContent = JsonUtil::decode($content);
            } catch (Throwable) {
            }

            $this->logService()->warning([
                'method' => 'POST',
                'request' => $data,
                'response' => $errorContent,
                'status' => $status,
                'url' => $url,
            ], "POST {$url} failed with status {$status}");

            throw new RequestException($response);
        }

        return $response;
    }

    /**
     * @param mixed[] $data
     * @param array<string, string> $headers
     * @param array<string, string|bool|number> $options
     */
    private function request(
        string $method,
        string $url,
        array $data = [],
        array $headers = [],
        array $options = [],
        bool $sendBodyAsObject = false,
        ?bool $enableDebugFallback = null
    ): RequestResponse {
        $body = JsonUtil::encode($data);
        $headers = array_merge($this->defaultHeaders(), $headers);

        $options['verify_peer'] = false;
        $options['verify_host'] = false;
        $options['headers'] = $headers;

        if ($sendBodyAsObject) {
            $body = $data;
        }

        try {
            if ($method === 'GET') {
                $params = '';
                foreach ($data as $key => $value) {
                    if ($value === true) {
                        $value = 'true';
                    } elseif ($value === false) {
                        $value = 'false';
                    }
                    if ($params !== '') {
                        $params .= '&';
                    }
                    $params .= "{$key}={$value}";
                }
                if ($params !== '') {
                    $url .= "?{$params}";
                }
            } else {
                $options['body'] = $body;
            }

            $start = microtime(true);
            $client = HttpClient::create();
            $response = $client->request(
                $method,
                $url,
                $options,
            );

            $content = $response->getContent(false);
            $status = $response->getStatusCode();
            $respHeaders = $response->getHeaders(false);

            $cookies = [];

            foreach ($response->getInfo()['response_headers'] as $header) {
                $headerParts = explode(':', $header);

                if ($headerParts[0] === 'Set-Cookie') {
                    $parts = explode(';', $headerParts[1]);
                    $cookie = explode('=', $parts[0]);
                    $cookies[StringUtil::trim($cookie[0])] = StringUtil::trim($cookie[1]);
                }
            }

            $response = new RequestResponse(
                $method,
                $url,
                $status,
                $content,
                $headers,
                $respHeaders,
                $cookies,
            );

            $end = (microtime(true) - $start) * 1000;
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }

        if ($enableDebugFallback ?? str_starts_with($url, 'https')) {
            try {
                $body = $response->json();
            } catch (Throwable) {
                $body = $response->body;
            }

            $this->logService()->debug([
                'method' => $response->method,
                'request' => $data,
                'requestHeaders' => $headers,
                'response' => $body,
                'responseHeaders' => $respHeaders,
                'status' => $response->status,
                'url' => $url,
                'duration' => $end
            ], "{$status} {$method} {$url}");
        }

        if ($status >= 500) {
            $errorContent = $content;

            try {
                $errorContent = JsonUtil::decode($content);
            } catch (Throwable) {
            }

            $this->logService()->error([
                'method' => $method,
                'request' => $data,
                'response' => $errorContent,
                'status' => $status,
                'url' => $url,
            ], "{$method} {$url} failed with status {$status}");

            throw new RequestException($response);
        }

        if ($status >= 400) {
            $errorContent = $content;

            try {
                $errorContent = JsonUtil::decode($content);
            } catch (Throwable) {
            }

            $this->logService()->warning([
                'method' => $method,
                'request' => $data,
                'response' => $errorContent,
                'status' => $status,
                'url' => $url,
            ], "{$method} {$url} failed with status {$status}");

            throw new RequestException($response);
        }

        return $response;
    }
}
