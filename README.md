# Google Timezone API Client

[![Build Status](https://github.com/gsteel/google-timezone/workflows/Continuous%20Integration/badge.svg)](https://github.com/gsteel/google-timezone/actions?query=workflow%3A"Continuous+Integration")

[![codecov](https://codecov.io/gh/gsteel/google-timezone/branch/main/graph/badge.svg)](https://codecov.io/gh/gsteel/google-timezone)
[![Psalm Type Coverage](https://shepherd.dev/github/gsteel/google-timezone/coverage.svg)](https://shepherd.dev/github/gsteel/google-timezone)

[![Latest Stable Version](https://poser.pugx.org/gsteel/google-timezone/v/stable)](https://packagist.org/packages/gsteel/google-timezone)
[![Total Downloads](https://poser.pugx.org/gsteel/google-timezone/downloads)](https://packagist.org/packages/gsteel/google-timezone)

## Introduction

Provides a well tested set of interfaces and value objects for interacting with [Google's Timezone API](https://developers.google.com/maps/documentation/timezone/overview)

## Installation

The only supported method of installation is via composer.

This client requires a [PSR-18 Http Client](https://packagist.org/providers/psr/http-client-implementation) and PSR-17 Factory implementations. These implementations are not required by composer, so you will need to ensure you have them installed, for example,

```bash
composer require php-http/curl-client laminas/laminas-diactoros gsteel/google-timezone
```

## Usage

The TimeZone API requires an [API key from Google](https://developers.google.com/maps/documentation/timezone/get-api-key) 

```php
<?php

use DateTimeImmutable;
use GSteel\GoogleTimezone\Coordinates;
use GSteel\GoogleTimezone\HttpClient;
use Http\Client\Curl\Client;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\UriFactory;

$apiKey = 'Some API Key';

$client = new HttpClient(
    $apiKey,
    new Client(),
    new UriFactory(),
    new RequestFactory()
);

$primeMeridian = '51.47,-0.00';
$referenceDate = DateTimeImmutable::createFromFormat('!Y-m-d', '2020-01-01');

$result = $this->client->fetch(
    Coordinates::fromString($primeMeridian),
    $referenceDate
);

assert($result->isSuccess());        // true
echo $result->timezone()->getName(); // "Europe/London"
$result->isReferenceDateDst();       // false
echo $result->name();                // "Greenwich Mean Time"
```

## License

[MIT Licensed](LICENSE.md).

## Contributing

…is welcomed. Please make sure your patch passes CI :)
