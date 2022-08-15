<?php

declare(strict_types=1);

namespace GSteel\GoogleTimezone\Test\Unit;

use DateTimeImmutable;
use GSteel\GoogleTimezone\Coordinates;
use GSteel\GoogleTimezone\Exception\AssertionFailed;
use GSteel\GoogleTimezone\Exception\BadMethodCall;
use GSteel\GoogleTimezone\Result;
use PHPUnit\Framework\TestCase;

use function assert;

class ResultTest extends TestCase
{
    /** @var DateTimeImmutable */
    private $date;

    protected function setUp(): void
    {
        parent::setUp();
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', '2020-01-01');
        assert($date instanceof DateTimeImmutable);
        $this->date = $date;
    }

    /** @return array<string, array{0: array<string, mixed>}> */
    public function invalidPayloadProvider(): array
    {
        return [
            'Missing' => [[]],
            'Unknown' => [['status' => 'Foo']],
            'Invalid' => [['status' => 1]],
        ];
    }

    /**
     * @param array<string, mixed> $body
     *
     * @dataProvider invalidPayloadProvider
     */
    public function testResultsMustContainAValidStatusCode(array $body): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessage('is not a valid status code');

        Result::with($body, Coordinates::random(), null, $this->date);
    }

    public function testThatOnlyAnOkStatusIsConsideredSuccessful(): void
    {
        self::assertTrue(
            Result::with(['status' => 'OK'], Coordinates::random(), null, $this->date)->isSuccess()
        );

        self::assertFalse(
            Result::with(['status' => 'INVALID_REQUEST'], Coordinates::random(), null, $this->date)->isSuccess()
        );
    }

    public function testUnsuccessfulResultsDoNotReferenceATimezone(): void
    {
        $result = Result::with(['status' => 'ZERO_RESULTS'], Coordinates::random(), null, $this->date);

        $this->expectException(BadMethodCall::class);
        $this->expectExceptionMessage('The timezone is not available for an unsuccessful request');

        /** @psalm-suppress UnusedMethodCall */
        $result->timezone();
    }

    public function testUnsuccessfulResultsDoNotHaveALocalisedTimezoneName(): void
    {
        $result = Result::with(['status' => 'OVER_QUERY_LIMIT'], Coordinates::random(), null, $this->date);

        $this->expectException(BadMethodCall::class);
        $this->expectExceptionMessage('The timezone name is not available for an unsuccessful request');

        /** @psalm-suppress UnusedMethodCall */
        $result->name();
    }

    public function testUnsuccessfulResultsDoNotHaveDstInformation(): void
    {
        $result = Result::with(['status' => 'OVER_QUERY_LIMIT'], Coordinates::random(), null, $this->date);

        $this->expectException(BadMethodCall::class);
        $this->expectExceptionMessage('The DST offset is not available for an unsuccessful request');

        /** @psalm-suppress UnusedMethodCall */
        $result->isReferenceDateDst();
    }

    public function testThatNonZeroDstOffsetImpliesTheReferenceDateIsObservingDst(): void
    {
        $result = Result::with([
            'status' => 'OK',
            'timeZoneId' => 'Europe/London',
            'timeZoneName' => 'BST',
            'dstOffset' => 3600,
            'errorMessage' => null,
            'rawOffset' => 0,
        ], Coordinates::random(), null, $this->date);

        self::assertTrue($result->isReferenceDateDst());
    }

    public function testThatZeroDstOffsetImpliesTheReferenceDateIsNotObservingDst(): void
    {
        $result = Result::with([
            'status' => 'OK',
            'timeZoneId' => 'Europe/London',
            'timeZoneName' => 'GMT',
            'dstOffset' => 0,
            'errorMessage' => null,
            'rawOffset' => 0,
        ], Coordinates::random(), null, $this->date);

        self::assertFalse($result->isReferenceDateDst());
    }
}
