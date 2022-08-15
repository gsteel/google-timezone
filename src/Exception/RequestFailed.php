<?php

declare(strict_types=1);

namespace GSteel\GoogleTimezone\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

final class RequestFailed extends RuntimeException implements Exception
{
    /** @var RequestInterface|null */
    private $request;
    /** @var ResponseInterface|null */
    private $response;

    public static function withNetworkFailure(RequestInterface $request, Throwable $error): self
    {
        $exception = new self(
            'The request to Google’s timezone API failed due to a communication error',
            500,
            $error
        );

        $exception->request = $request;

        return $exception;
    }

    public static function withInvalidResponseBody(
        RequestInterface $request,
        ResponseInterface $response,
        Throwable $error
    ): self {
        $exception = new self(
            'The response received could not be decoded as a json payload',
            500,
            $error
        );

        $exception->request  = $request;
        $exception->response = $response;

        return $exception;
    }

    public function request(): ?RequestInterface
    {
        return $this->request;
    }

    public function response(): ?ResponseInterface
    {
        return $this->response;
    }
}
