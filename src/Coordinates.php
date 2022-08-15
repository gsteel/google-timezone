<?php

declare(strict_types=1);

namespace GSteel\GoogleTimezone;

use GSteel\GoogleTimezone\Exception\InvalidArgument;
use GSteel\GoogleTimezone\Exception\InvalidCoordinate;
use JsonException;
use JsonSerializable;

use function assert;
use function is_array;
use function is_float;
use function json_decode;
use function preg_match;
use function random_int;
use function round;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/** @psalm-immutable */
final class Coordinates implements JsonSerializable
{
    public const DEFAULT_PRECISION = 7;

    private float $latitude;
    private float $longitude;

    private function __construct(float $latitude, float $longitude, private int $precision = self::DEFAULT_PRECISION)
    {
        $this->latitude  = round($latitude, $precision);
        $this->longitude = round($longitude, $precision);
    }

    /**
     * Generate coordinates from floating point numbers
     *
     * Floats are rounded to the number of decimal places
     *
     * @psalm-pure
     */
    public static function fromDecimal(float $lat, float $lng, int $precision = self::DEFAULT_PRECISION): self
    {
        return new self(
            self::validateLatitude($lat),
            self::validateLongitude($lng),
            $precision
        );
    }

    /**
     * Generate coordinates from a comma separated tuple of lat and lng
     *
     * @throws InvalidArgument
     *
     * @psalm-pure
     */
    public static function fromString(string $latLng): self
    {
        if (! preg_match('/^([-+]?\d*\.\d+),([-+]?\d*\.\d+)$/', $latLng, $match)) {
            throw new InvalidArgument(
                'Coordinate strings must have fractional digits and a decimal point such as "1.23,1.23"'
            );
        }

        return self::fromDecimal((float) $match[1], (float) $match[2]);
    }

    /**
     *  Return a random set of coordinates
     */
    public static function random(): self
    {
        return new self(
            random_int(-900000, 900000) / 10000.0,
            random_int(-1800000, 1800000) / 10000.0
        );
    }

    /**
     * Expects a JSON object with the keys 'lat' and 'lng'
     *
     * @throws InvalidArgument
     *
     * @psalm-pure
     */
    public static function fromJsonString(string $json): self
    {
        try {
            $data = json_decode($json, true, 2, JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            throw new InvalidArgument('Failed to decode JSON string', 500, $error);
        }

        assert(is_array($data));
        $lat = $data['lat'] ?? null;
        $lng = $data['lng'] ?? null;
        if (! is_float($lat) || ! is_float($lng)) {
            throw new InvalidArgument('JSON value did not decode to a recognisable format: ' . $json);
        }

        return self::fromDecimal($lat, $lng);
    }

    public function lat(): float
    {
        return $this->latitude;
    }

    public function lng(): float
    {
        return $this->longitude;
    }

    /** @psalm-pure */
    private static function validateLatitude(float $latitude): float
    {
        if ($latitude < -90.0 || $latitude > 90.0) {
            throw InvalidCoordinate::latOutOfRange($latitude);
        }

        return $latitude;
    }

    /** @psalm-pure */
    private static function validateLongitude(float $longitude): float
    {
        if ($longitude < -180.0 || $longitude > 180.0) {
            throw InvalidCoordinate::lngOutOfRange($longitude);
        }

        return $longitude;
    }

    public function isEqualTo(self $other): bool
    {
        return $this->toString() === $other->toString();
    }

    /** @return array{lat: float, lng: float} */
    public function jsonSerialize(): array
    {
        return [
            'lat' => $this->latitude,
            'lng' => $this->longitude,
        ];
    }

    public function toString(): string
    {
        $format = sprintf('%%0.%1$dF,%%0.%1$dF', $this->precision);

        return sprintf($format, $this->latitude, $this->longitude);
    }
}
