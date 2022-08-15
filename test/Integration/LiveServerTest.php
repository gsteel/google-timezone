<?php

declare(strict_types=1);

namespace GSteel\GoogleTimezone\Test\Integration;

use DateTimeImmutable;
use GSteel\GoogleTimezone\Coordinates;
use GSteel\GoogleTimezone\HttpClient;
use Http\Client\Curl\Client;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\UriFactory;
use PHPUnit\Framework\TestCase;

use function assert;
use function getenv;
use function is_string;

final class LiveServerTest extends TestCase
{
    private HttpClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $apiKey = getenv('API_KEY');
        if (! is_string($apiKey) || $apiKey === '') {
            self::markTestSkipped('No API Key is available in the API_KEY environment variable');
        }

        $this->client = new HttpClient(
            $apiKey,
            new Client(),
            new UriFactory(),
            new RequestFactory()
        );
    }

    public function testSuccessfulResponse(): void
    {
        $primeMeridian = '51.4779786,-0.00';
        $referenceDate = DateTimeImmutable::createFromFormat('!Y-m-d', '2020-01-01');
        assert($referenceDate !== false);

        $result = $this->client->fetch(
            Coordinates::fromString($primeMeridian),
            $referenceDate
        );

        self::assertTrue($result->isSuccess());
        self::assertEquals('Europe/London', $result->timezone()->getName());
        self::assertFalse($result->isReferenceDateDst());
        self::assertEquals('Greenwich Mean Time', $result->name());
    }

    public function testDstIsCorrectlyReported(): void
    {
        $primeMeridian = '51.4779786,-0.00';
        $referenceDate = DateTimeImmutable::createFromFormat('!Y-m-d', '2020-06-01');
        assert($referenceDate !== false);

        $result = $this->client->fetch(
            Coordinates::fromString($primeMeridian),
            $referenceDate
        );

        self::assertTrue($result->isSuccess());
        self::assertEquals('Europe/London', $result->timezone()->getName());
        self::assertTrue($result->isReferenceDateDst());
    }
}
