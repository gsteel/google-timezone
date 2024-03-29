<?php

declare(strict_types=1);

namespace GSteel\GoogleTimezone;

use DateTimeInterface;
use DateTimeZone;
use GSteel\GoogleTimezone\Exception\AssertionFailed;
use GSteel\GoogleTimezone\Exception\BadMethodCall;

use function gettype;
use function in_array;
use function is_int;
use function is_string;
use function sprintf;

/** @psalm-immutable */
final class Result
{
    private const STATUS_VALUES = [
        'OK' => 'OK',
        'INVALID_REQUEST' => 'INVALID_REQUEST',
        'OVER_DAILY_LIMIT' => 'OVER_DAILY_LIMIT',
        'OVER_QUERY_LIMIT' => 'OVER_QUERY_LIMIT',
        'REQUEST_DENIED' => 'REQUEST_DENIED',
        'UNKNOWN_ERROR' => 'UNKNOWN_ERROR',
        'ZERO_RESULTS' => 'ZERO_RESULTS',
    ];

    /**
     * @param non-empty-string|null $errorMessage
     * @param non-empty-string|null $timezone
     * @param non-empty-string|null $name
     * @param non-empty-string|null $language
     * @psalm-param value-of<self::STATUS_VALUES> $status
     */
    private function __construct(
        private readonly string $status,
        private readonly int|null $dstOffset,
        private readonly string|null $errorMessage,
        private readonly int|null $utcOffset,
        private readonly string|null $timezone,
        private readonly string|null $name,
        private readonly Coordinates $coordinates,
        private readonly string|null $language,
        private readonly DateTimeInterface $referenceDate,
    ) {
    }

    /**
     * @internal Result instances are not meant to be instantiated by consumers.
     *
     * @param array<array-key, mixed> $data
     * @param non-empty-string|null   $language
     */
    public static function with(
        array $data,
        Coordinates $coordinates,
        string|null $language,
        DateTimeInterface $referenceDate,
    ): self {
        $status = $data['status'] ?? null;
        if (! is_string($status)) {
            throw new AssertionFailed(sprintf(
                '"%s" is not a valid status code',
                gettype($status),
            ));
        }

        if (! in_array($status, self::STATUS_VALUES, true)) {
            throw new AssertionFailed(sprintf(
                '"%s" is not a valid status code',
                $status,
            ));
        }

        $dstOffset = isset($data['dstOffset']) && is_int($data['dstOffset'])
            ? $data['dstOffset']
            : null;

        $message = isset($data['errorMessage']) && is_string($data['errorMessage']) && $data['errorMessage'] !== ''
            ? $data['errorMessage']
            : null;

        $utcOffset = isset($data['rawOffset']) && is_int($data['rawOffset'])
            ? $data['rawOffset']
            : null;

        $timezone = isset($data['timeZoneId']) && is_string($data['timeZoneId']) && $data['timeZoneId'] !== ''
            ? $data['timeZoneId']
            : null;

        $name = isset($data['timeZoneName']) && is_string($data['timeZoneName']) && $data['timeZoneName'] !== ''
            ? $data['timeZoneName']
            : null;

        return new self(
            $status,
            $dstOffset,
            $message,
            $utcOffset,
            $timezone,
            $name,
            $coordinates,
            $language,
            $referenceDate,
        );
    }

    public function isSuccess(): bool
    {
        return $this->status === 'OK';
    }

    public function timezone(): DateTimeZone
    {
        if ($this->timezone === null) {
            throw new BadMethodCall('The timezone is not available for an unsuccessful request');
        }

        return new DateTimeZone($this->timezone);
    }

    public function name(): string|null
    {
        if ($this->name === null) {
            throw new BadMethodCall('The timezone name is not available for an unsuccessful request');
        }

        return $this->name;
    }

    public function coordinates(): Coordinates
    {
        return $this->coordinates;
    }

    /**
     * @return non-empty-string
     * @psalm-return value-of<self::STATUS_VALUES>
     */
    public function status(): string
    {
        return $this->status;
    }

    public function errorMessage(): string|null
    {
        return $this->errorMessage;
    }

    public function isReferenceDateDst(): bool
    {
        if ($this->dstOffset === null) {
            throw new BadMethodCall('The DST offset is not available for an unsuccessful request');
        }

        return $this->dstOffset !== 0;
    }
}
