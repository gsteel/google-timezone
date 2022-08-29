<?php

declare(strict_types=1);

namespace GSteel\GoogleTimezone\Exception;

use function sprintf;

final class InvalidCoordinate extends InvalidArgument
{
    public static function latOutOfRange(float $lat): self
    {
        return new self(sprintf(
            'Latitude must be a number between -90 and 90. Received %f',
            $lat,
        ));
    }

    public static function lngOutOfRange(float $lng): self
    {
        return new self(sprintf(
            'Longitude must be a number between -180 and 180. Received %f',
            $lng,
        ));
    }
}
