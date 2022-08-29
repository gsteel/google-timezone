<?php

declare(strict_types=1);

namespace GSteel\GoogleTimezone\Test\Integration\Framework;

use DateTimeImmutable;
use Fig\Http\Message\RequestMethodInterface as Method;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;

use function assert;
use function count;
use function is_callable;
use function json_encode;
use function ksort;
use function parse_str;
use function sprintf;

final class MockServer
{
    public const STATIC_REFERENCE_DATE = '2020-01-01';
    public const VALID_KEY             = 'valid_key';
    public const INVALID_KEY           = 'invalid_key';

    private LoopInterface $loop;
    private HttpServer $server;
    private SocketServer $socket;

    /** Seconds before the server shuts down automatically */
    private int $timeout = 2;

    /**
     * @var array<string, array{
     *     query: array<string, string>,
     *     method: string,
     *     body: string,
     *     type: string,
     *     code: int,
     *     bodyMatcher: callable|null
     * }>
     */
    private array $responses;

    public function __construct(int $port)
    {
        $this->seedResponses();
        $this->loop   = Loop::get();
        $this->server = new HttpServer($this->loop, function (RequestInterface $request): ResponseInterface {
            return $this->handleRequest($request);
        });
        $this->socket = new SocketServer(sprintf('0.0.0.0:%d', $port), [], $this->loop);
        $this->server->listen($this->socket);
    }

    public function start(): void
    {
        $this->loop->addTimer($this->timeout, function (): void {
            $this->stop();
        });
        $this->loop->run();
    }

    public function stop(): void
    {
        $this->loop->stop();
        $this->server->removeAllListeners();
        $this->socket->close();
    }

    private function handleRequest(RequestInterface $request): ResponseInterface
    {
        $data = $this->matchUri($request);

        return new Response($data['code'], ['Content-Type' => $data['type']], $data['body']);
    }

    /**
     * @return array{
     *     query: array<string, string>,
     *     method: string,
     *     body: string,
     *     type: string,
     *     code: int,
     *     bodyMatcher: callable|null
     * }
     */
    private function matchUri(RequestInterface $request): array
    {
        foreach ($this->responses as $data) {
            if ($request->getMethod() !== $data['method']) {
                continue;
            }

            parse_str($request->getUri()->getQuery(), $query);
            if (! $this->matchQueries($query, $data['query'])) {
                continue;
            }

            $body = (string) $request->getBody();
            if (is_callable($data['bodyMatcher']) && $data['bodyMatcher']($body) === false) {
                continue;
            }

            return $data;
        }

        return [
            'query' => [],
            'method' => 'GET',
            'body' => json_encode([
                'errorMessage' => sprintf('URI and Query did not match: %s', (string) $request->getUri()),
                'status' => 'UNKNOWN_ERROR',
            ]),
            'type' => 'application/json',
            'code' => 404,
            'bodyMatcher' => null,
        ];
    }

    private function seedResponses(): void
    {
        $timestamp = (string) self::staticReferenceDate()->getTimestamp();

        $this->responses = [
            'Invalid Key' => [
                'query' => [
                    'location' => '0.0,0.0',
                    'timestamp' => $timestamp,
                    'key' => self::INVALID_KEY,
                ],
                'method' => Method::METHOD_GET,
                'body' => <<<'JSON'
                    {
                       "errorMessage" : "The provided API key is invalid.",
                       "status" : "REQUEST_DENIED"
                    }
                    JSON,
                'type' => 'application/json',
                'code' => 200,
                'bodyMatcher' => null,
            ],
            'Missing Key' => [
                'query' => [
                    'location' => '0.0,0.0',
                    'timestamp' => $timestamp,
                ],
                'method' => Method::METHOD_GET,
                'body' => json_encode([
                    'errorMessage' => 'You must use an API key to authenticate each request to Google Maps Platform '
                                      . 'APIs. For additional information, please refer to '
                                      . 'http://g.co/dev/maps-no-account',
                    'status' => 'REQUEST_DENIED',
                ]),
                'type' => 'application/json',
                'code' => 200,
                'bodyMatcher' => null,
            ],
            'Successful Response' => [
                'query' => [
                    'location' => '0.0,0.0',
                    'timestamp' => $timestamp,
                    'key' => self::VALID_KEY,
                ],
                'method' => Method::METHOD_GET,
                'body' => json_encode([
                    'errorMessage' => null,
                    'status' => 'OK',
                    'dstOffset' => 3600,
                    'rawOffset' => -28800,
                    'timeZoneId' => 'America/Los_Angeles',
                    'timeZoneName' => 'Pacific Daylight Time',
                ]),
                'type' => 'application/json',
                'code' => 200,
                'bodyMatcher' => null,
            ],
            'With Localized Name' => [
                'query' => [
                    'location' => '0.0,0.0',
                    'timestamp' => $timestamp,
                    'key' => self::VALID_KEY,
                    'language' => 'es',
                ],
                'method' => Method::METHOD_GET,
                'body' => json_encode([
                    'errorMessage' => null,
                    'status' => 'OK',
                    'dstOffset' => 3600,
                    'rawOffset' => -28800,
                    'timeZoneId' => 'America/Los_Angeles',
                    'timeZoneName' => 'hora de verano del Pacífico',
                ]),
                'type' => 'application/json',
                'code' => 200,
                'bodyMatcher' => null,
            ],
        ];
    }

    /**
     * @param array<array-key, mixed> $query1
     * @param array<array-key, mixed> $query2
     *
     * @psalm-suppress MixedAssignment
     */
    private function matchQueries(array $query1, array $query2): bool
    {
        ksort($query1);
        ksort($query2);

        foreach ($query1 as $key => $value) {
            $match = $query2[$key] ?? null;
            if ($match === $value) {
                continue;
            }

            return false;
        }

        return count($query1) === count($query2);
    }

    public static function staticReferenceDate(): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', self::STATIC_REFERENCE_DATE);
        assert($date instanceof DateTimeImmutable);

        return $date;
    }
}
