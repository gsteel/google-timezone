<?php

declare(strict_types=1);

namespace GSteel\GoogleTimezone\Test\Integration\Framework;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class TestHttpClient implements ClientInterface
{
    private RequestInterface|null $lastRequest   = null;
    private ResponseInterface|null $lastResponse = null;

    public function __construct(
        private ClientInterface $client,
    ) {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->lastRequest  = $request;
        $this->lastResponse = $this->client->sendRequest($request);

        return $this->lastResponse;
    }

    public function clearState(): void
    {
        $this->lastRequest = $this->lastResponse = null;
    }

    public function lastRequest(): RequestInterface|null
    {
        return $this->lastRequest;
    }

    public function lastResponse(): ResponseInterface|null
    {
        return $this->lastResponse;
    }
}
