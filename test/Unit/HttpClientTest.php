<?php

declare(strict_types=1);

namespace GSteel\GoogleTimezone\Test\Unit;

use DateTimeImmutable;
use GSteel\GoogleTimezone\Coordinates;
use GSteel\GoogleTimezone\Exception\AssertionFailed;
use GSteel\GoogleTimezone\Exception\RequestFailed;
use GSteel\GoogleTimezone\HttpClient;
use Http\Client\Exception\NetworkException;
use JsonException;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\UriFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

use function sprintf;

class HttpClientTest extends TestCase
{
    /** @var ClientInterface&MockObject */
    private ClientInterface $http;
    private HttpClient $client;
    private DateTimeImmutable $date;

    protected function setUp(): void
    {
        parent::setUp();
        $this->http   = $this->createMock(ClientInterface::class);
        $this->client = new HttpClient(
            'some_key',
            $this->http,
            new UriFactory(),
            new RequestFactory()
        );

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', '2020-01-01');
        self::assertInstanceOf(DateTimeImmutable::class, $date);
        $this->date = $date;
    }

    public function testTheApiKeyIsPresentInTheRequestUri(): void
    {
        $this->http->expects(self::once())
            ->method('sendRequest')
            ->with(self::callback(static function (RequestInterface $request): bool {
                $uri = (string) $request->getUri();
                self::assertStringContainsString('key=some_key', $uri);

                return true;
            }))->willReturn(new JsonResponse(['status' => 'OK']));

        $this->client->fetch(Coordinates::random(), $this->date);
    }

    public function testTheExpectedTimeStampIsPresentInTheRequestUri(): void
    {
        $expect = sprintf('timestamp=%d', $this->date->getTimestamp());
        $this->http->expects(self::once())
            ->method('sendRequest')
            ->with(self::callback(static function (RequestInterface $request) use ($expect): bool {
                $uri = (string) $request->getUri();
                self::assertStringContainsString($expect, $uri);

                return true;
            }))->willReturn(new JsonResponse(['status' => 'OK']));

        $this->client->fetch(Coordinates::random(), $this->date);
    }

    public function testTheExpectedCoordinatesArePresentInTheRequestUri(): void
    {
        $coordinates = Coordinates::fromDecimal(0.1, 0.1, 1);
        $this->http->expects(self::once())
            ->method('sendRequest')
            ->with(self::callback(static function (RequestInterface $request): bool {
                $uri = (string) $request->getUri();
                self::assertStringContainsString('location=0.1%2C0.1', $uri);

                return true;
            }))->willReturn(new JsonResponse(['status' => 'OK']));

        $this->client->fetch($coordinates, $this->date);
    }

    public function testTheLanguageIsPresentInTheRequestUri(): void
    {
        $expect = 'language=de';
        $this->http->expects(self::once())
            ->method('sendRequest')
            ->with(self::callback(static function (RequestInterface $request) use ($expect): bool {
                $uri = (string) $request->getUri();
                self::assertStringContainsString($expect, $uri);

                return true;
            }))->willReturn(new JsonResponse(['status' => 'OK']));

        $this->client->fetch(Coordinates::random(), $this->date, 'de');
    }

    public function testAnExceptionIsThrownWhenTheResponseBodyIsNotJson(): void
    {
        $this->http->expects(self::once())
            ->method('sendRequest')
            ->willReturn(new TextResponse('foo'));

        $this->expectException(RequestFailed::class);
        $this->expectExceptionMessage('The response received could not be decoded as a json payload');
        $this->expectExceptionCode(500);

        $this->client->fetch(Coordinates::random(), $this->date, 'de');
    }

    public function testAnExceptionIsThrownWhenTheResponseBodyExceedsAllowedJsonDepth(): void
    {
        $this->http->expects(self::once())
            ->method('sendRequest')
            ->willReturn(new JsonResponse(['foo' => ['bar' => 'baz']]));

        try {
            $this->client->fetch(Coordinates::random(), $this->date, 'de');
            self::fail('No exception was thrown');
        } catch (RequestFailed $error) {
            self::assertEquals('The response received could not be decoded as a json payload', $error->getMessage());
            self::assertInstanceOf(JsonException::class, $error->getPrevious());
            self::assertEquals(500, $error->getCode());
        }
    }

    public function testThatNetworkFailureWillCauseException(): void
    {
        $exception = new NetworkException('Foo', $this->createMock(RequestInterface::class));

        $this->http->expects(self::once())
            ->method('sendRequest')
            ->willThrowException($exception);

        $this->expectException(RequestFailed::class);
        $this->expectExceptionMessage('The request to Google’s timezone API failed due to a communication error');
        $this->expectExceptionCode(500);

        $this->client->fetch(Coordinates::random(), $this->date, 'de');
    }

    /** @return array<string, array{0: mixed}> */
    public function invalidStatusValues(): array
    {
        return [
            'Not String' => [1],
            'Unknown Code' => ['Foo'],
        ];
    }

    /**
     * @dataProvider invalidStatusValues
     */
    public function testAnExceptionIsThrownForAnInvalidStatus(mixed $status): void
    {
        $this->http->expects(self::once())
            ->method('sendRequest')
            ->willReturn(new JsonResponse(['status' => $status]));

        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessage('is not a valid status code');
        $this->expectExceptionCode(0);

        $this->client->fetch(Coordinates::random(), $this->date, 'de');
    }
}
