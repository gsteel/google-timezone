<?php

declare(strict_types=1);

namespace GSteel\GoogleTimezone\Test\Unit\Exception;

use Exception;
use GSteel\GoogleTimezone\Exception\RequestFailed;
use Http\Client\Exception\NetworkException;
use Laminas\Diactoros\Response\TextResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class RequestFailedTest extends TestCase
{
    public function testNetworkFailureHasExpectedValues(): void
    {
        $request  = $this->createMock(RequestInterface::class);
        $previous = new NetworkException('Bad News', $request);
        $error    = RequestFailed::withNetworkFailure($request, $previous);

        self::assertSame(500, $error->getCode());
        self::assertSame($request, $error->request());
        self::assertNull($error->response());
        self::assertSame($previous, $error->getPrevious());
    }

    public function testInvalidResponseHasExpectedValues(): void
    {
        $request  = $this->createMock(RequestInterface::class);
        $response = new TextResponse('Foo');
        $previous = new Exception('Bad News');
        $error    = RequestFailed::withInvalidResponseBody($request, $response, $previous);

        self::assertSame(500, $error->getCode());
        self::assertSame($request, $error->request());
        self::assertSame($response, $error->response());
        self::assertSame($previous, $error->getPrevious());
    }
}
