<?php

declare(strict_types=1);

namespace GSteel\GoogleTimezone\Test\Integration;

use GSteel\GoogleTimezone\Coordinates;
use GSteel\GoogleTimezone\HttpClient;
use GSteel\GoogleTimezone\Test\Integration\Framework\MockServer;
use GSteel\GoogleTimezone\Test\Integration\Framework\RemoteIntegrationTestCase;
use Laminas\Diactoros\UriFactory;

class HttpClientTest extends RemoteIntegrationTestCase
{
    private HttpClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new HttpClient(
            MockServer::VALID_KEY,
            self::httpClient(),
            new UriFactory(),
            self::requestFactory(),
            self::apiServerUri(),
        );
    }

    public function testInvalidKeyResponse(): void
    {
        $client = new HttpClient(
            MockServer::INVALID_KEY,
            self::httpClient(),
            new UriFactory(),
            self::requestFactory(),
            self::apiServerUri(),
        );

        $result = $client->fetch(
            Coordinates::fromDecimal(0.0, 0.0, 1),
            MockServer::staticReferenceDate(),
        );

        self::assertFalse($result->isSuccess());
        self::assertEquals('The provided API key is invalid.', $result->errorMessage());
        self::assertEquals('REQUEST_DENIED', $result->status());
    }

    public function testMissingKeyResponse(): void
    {
        /** @psalm-suppress InvalidArgument */
        $client = new HttpClient(
            '',
            self::httpClient(),
            new UriFactory(),
            self::requestFactory(),
            self::apiServerUri(),
        );

        $result = $client->fetch(
            Coordinates::fromDecimal(0.0, 0.0, 1),
            MockServer::staticReferenceDate(),
        );

        self::assertFalse($result->isSuccess());
        self::assertStringContainsString(
            'You must use an API key to authenticate each request to Google Maps Platform APIs',
            (string) $result->errorMessage(),
        );
        self::assertEquals('REQUEST_DENIED', $result->status());
    }

    public function testSuccessfulResponse(): void
    {
        $coordinates = Coordinates::fromDecimal(0.0, 0.0, 1);

        $result = $this->client->fetch(
            $coordinates,
            MockServer::staticReferenceDate(),
        );

        self::assertTrue($result->isSuccess());
        self::assertNull($result->errorMessage());
        self::assertEquals('America/Los_Angeles', $result->timezone()->getName());
        self::assertEquals('Pacific Daylight Time', $result->name());
        self::assertSame($coordinates, $result->coordinates());
        self::assertEquals('OK', $result->status());
    }

    public function testLocalisedNameResponse(): void
    {
        $coordinates = Coordinates::fromDecimal(0.0, 0.0, 1);

        $result = $this->client->fetch(
            $coordinates,
            MockServer::staticReferenceDate(),
            'es',
        );

        self::assertTrue($result->isSuccess());
        self::assertNull($result->errorMessage());
        self::assertEquals('America/Los_Angeles', $result->timezone()->getName());
        self::assertEquals('hora de verano del Pacífico', $result->name());
        self::assertSame($coordinates, $result->coordinates());
        self::assertEquals('OK', $result->status());
    }
}
