<?php

declare(strict_types=1);

namespace GSteel\GoogleTimezone\Test\Integration\Framework;

use Http\Client\Curl\Client;
use Laminas\Diactoros\RequestFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestFactoryInterface;
use React\ChildProcess\Process;

use function assert;
use function sprintf;
use function usleep;

use const CURLOPT_CONNECTTIMEOUT_MS;

abstract class RemoteIntegrationTestCase extends TestCase
{
    /** @var int */
    private static $serverPort;
    /** @var Process */
    private static $serverProcess;
    /** @var TestHttpClient */
    private static $httpClient;
    /** @var RequestFactory */
    private static $requestFactory;
    /** @var string */
    protected static $basePath = '/maps/api/timezone/json';

    protected function setUp(): void
    {
        parent::setUp();
        self::httpClient()->clearState();
    }

    public static function setUpBeforeClass(): void
    {
        self::$httpClient     = new TestHttpClient(
            new Client(null, null, [CURLOPT_CONNECTTIMEOUT_MS => 100])
        );
        self::$requestFactory = new RequestFactory();
        self::$serverPort     = 8089;
        self::$serverProcess  = new Process(
            sprintf('exec php %s/run-server.php %d %s', __DIR__, self::$serverPort, self::$basePath)
        );
        self::$serverProcess->start();
        usleep(100000);
    }

    public static function tearDownAfterClass(): void
    {
        foreach (self::$serverProcess->pipes as $pipe) {
            $pipe->close();
        }

        self::$serverProcess->terminate();
    }

    /** @return non-empty-string */
    protected static function apiServerUri(): string
    {
        $value = sprintf('http://127.0.0.1:%d/maps/api/timezone/json', self::$serverPort);
        assert($value !== '');

        return $value;
    }

    protected static function httpClient(): TestHttpClient
    {
        return self::$httpClient;
    }

    protected static function requestFactory(): RequestFactoryInterface
    {
        return self::$requestFactory;
    }
}
