<?php

declare(strict_types=1);

namespace GSteel\GoogleTimezone;

use DateTimeInterface;
use Fig\Http\Message\RequestMethodInterface;
use GSteel\GoogleTimezone\Exception\RequestFailed;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

use function array_filter;
use function assert;
use function http_build_query;
use function is_array;
use function json_decode;

use const JSON_THROW_ON_ERROR;

final class HttpClient implements Client
{
    private const BASE_URI = 'https://maps.googleapis.com/maps/api/timezone/json';

    private ClientInterface $httpClient;
    private UriFactoryInterface $uriFactory;
    private RequestFactoryInterface $requestFactory;
    /** @var non-empty-string */
    private string $apiKey;
    /** @var non-empty-string */
    private string $baseUri;

    /**
     * @param non-empty-string $apiKey
     * @param non-empty-string $baseUri
     */
    public function __construct(
        string $apiKey,
        ClientInterface $httpClient,
        UriFactoryInterface $uriFactory,
        RequestFactoryInterface $requestFactory,
        string $baseUri = self::BASE_URI
    ) {
        $this->apiKey         = $apiKey;
        $this->httpClient     = $httpClient;
        $this->uriFactory     = $uriFactory;
        $this->requestFactory = $requestFactory;
        $this->baseUri        = $baseUri;
    }

    /**
     * @param non-empty-string|null $language
     *
     * @throws RequestFailed If the request cannot be sent or the response cannot be understood.
     */
    public function fetch(Coordinates $coordinates, DateTimeInterface $referenceDate, ?string $language = null): Result
    {
        $parameters = array_filter([
            'key' => $this->apiKey,
            'location' => $coordinates->toString(),
            'timestamp' => $referenceDate->getTimestamp(),
            'language' => $language,
        ]);

        $uri = $this->uriFactory->createUri($this->baseUri)
            ->withQuery(http_build_query($parameters));

        $request = $this->requestFactory->createRequest(
            RequestMethodInterface::METHOD_GET,
            $uri
        );

        /**
         * All responses appear to return 200 OK regardless of how messed up the request is 👍
         */
        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw RequestFailed::withNetworkFailure($request, $e);
        }

        try {
            $body = json_decode((string) $response->getBody(), true, 2, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw RequestFailed::withInvalidResponseBody($request, $response, $e);
        }

        assert(is_array($body));

        return Result::with($body, $coordinates, $language, $referenceDate);
    }
}
