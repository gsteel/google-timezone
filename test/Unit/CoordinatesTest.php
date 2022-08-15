<?php

declare(strict_types=1);

namespace GSteel\GoogleTimezone\Test\Unit;

use GSteel\GoogleTimezone\Coordinates;
use GSteel\GoogleTimezone\Exception\InvalidArgument;
use GSteel\GoogleTimezone\Exception\InvalidCoordinate;
use PHPUnit\Framework\TestCase;

use function json_decode;
use function json_encode;
use function PHPUnit\Framework\assertEquals;

class CoordinatesTest extends TestCase
{
    public function testLatitudeAndLongitudeAreTheCorrectWayAroundWhenConstructedFromAString(): void
    {
        $coords = Coordinates::fromString('1.23,2.34');
        self::assertEquals(1.23, $coords->lat());
        assertEquals(2.34, $coords->lng());
    }

    public function testInvalidLatitude(): void
    {
        $this->expectException(InvalidCoordinate::class);
        $this->expectExceptionMessage('Latitude');
        Coordinates::fromDecimal(-100., 0.);
    }

    public function testInvalidLongitude(): void
    {
        $this->expectException(InvalidCoordinate::class);
        $this->expectExceptionMessage('Longitude');
        Coordinates::fromDecimal(0., 500.);
    }

    public function testStringHasAllSignificantDigits(): void
    {
        $coords = Coordinates::fromDecimal(-1.2345678901234567, 0.12345678901234567);
        self::assertEquals('-1.2345679,0.1234568', $coords->toString());
    }

    public function testRoundingIsUsed(): void
    {
        $coords = Coordinates::fromDecimal(0.123456789, 0.123456789);
        self::assertEquals(0.1234568, $coords->lat());
        self::assertEquals(0.1234568, $coords->lng());
    }

    public function testJsonSerialise(): void
    {
        $coords = Coordinates::fromDecimal(0.1, 0.2);
        $json   = json_encode($coords);
        self::assertJson($json);
        $value = json_decode($json, true);
        self::assertIsArray($value);
        self::assertArrayHasKey('lat', $value);
        self::assertArrayHasKey('lng', $value);
        self::assertEquals(0.1, $value['lat']);
        self::assertEquals(0.2, $value['lng']);
    }

    public function testJsonSerialisationRoundTrip(): void
    {
        $coords = Coordinates::fromDecimal(0.1, 0.2);
        $copy   = Coordinates::fromJsonString(json_encode($coords));
        self::assertTrue($coords->isEqualTo($copy));
    }

    /** @return list<string[]> */
    public function invalidJsonProvider(): array
    {
        return [
            [json_encode(['too' => ['deep' => 1]])],
            ['invalid'],
        ];
    }

    /** @dataProvider invalidJsonProvider */
    public function testInvalidJsonPayload(string $json): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Failed to decode');
        $this->expectExceptionCode(500);
        Coordinates::fromJsonString($json);
    }

    /** @return list<string[]> */
    public function invalidJsonFormatProvider(): array
    {
        return [
            [json_encode(['foo'])],
            [json_encode(['lat' => 1, 'lng' => 'hey'])],
            [json_encode(['lat' => 1.23, 'lng' => 'hey'])],
            [json_encode(['lat' => 1, 'lng' => 1])],
        ];
    }

    /** @dataProvider invalidJsonFormatProvider */
    public function testInvalidJsonFormat(string $json): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('recognisable format');
        Coordinates::fromJsonString($json);
    }

    public function testInequalityToOtherValueObject(): void
    {
        $coords1 = Coordinates::fromDecimal(0.2, 0.1);
        $coords2 = Coordinates::fromDecimal(0.1, 0.2);
        self::assertFalse($coords1->isEqualTo($coords2));
    }

    /** @return array<array-key, array{0: string, 1: float, 2: float}> */
    public function strings(): array
    {
        return [
            ['1.23,3.21', 1.23, 3.21],
            ['-1.23,+3.21', -1.23, 3.21],
            ['-.31,-.32', -0.31, -0.32],
        ];
    }

    /** @dataProvider strings */
    public function testCoordinatesCanBeSerialisedToAndFromAString(string $input, float $lat, float $lng): void
    {
        $coords = Coordinates::fromString($input);
        $expect = Coordinates::fromDecimal($lat, $lng);
        self::assertTrue($coords->isEqualTo($expect));
    }

    public function testStringConversion(): void
    {
        $value          = '1.2345,1.5432';
        $fromString     = Coordinates::fromString($value);
        $fromConversion = Coordinates::fromString($fromString->toString());
        self::assertTrue($fromString->isEqualTo($fromConversion));
    }

    /** @return list<string[]> */
    public function invalidStringProvider(): array
    {
        return [
            ['1.23,foo'],
            ['foo, 1.23'],
            ['foo'],
            ['1,1'],
        ];
    }

    /** @dataProvider invalidStringProvider */
    public function testExceptionThrownForInvalidStringFormat(string $invalidString): void
    {
        $this->expectException(InvalidArgument::class);
        Coordinates::fromString($invalidString);
    }

    public function testRandomCoords(): void
    {
        $coords = Coordinates::random();
        self::assertLessThanOrEqual(90, $coords->lat());
        self::assertGreaterThanOrEqual(-90, $coords->lat());
        self::assertLessThanOrEqual(180, $coords->lng());
        self::assertGreaterThanOrEqual(-180, $coords->lng());
        $copy = Coordinates::fromDecimal($coords->lat(), $coords->lng());
        self::assertTrue($coords->isEqualTo($copy));
        self::assertFalse($coords->isEqualTo(Coordinates::random()));
    }

    /** @return list<int[]> */
    public function outOfBoundsProvider(): array
    {
        return [
            [-91, 0],
            [91, 0],
            [0, -181],
            [0, 181],
        ];
    }

    /** @dataProvider outOfBoundsProvider */
    public function testCoordinateBounds(int $lat, int $lng): void
    {
        $this->expectException(InvalidArgument::class);
        Coordinates::fromDecimal((float) $lat, (float) $lng);
    }

    /** @return list<int[]> */
    public function insideBoundsProvider(): array
    {
        return [
            [-90, 0],
            [90, 0],
            [0, -180],
            [0, 180],
        ];
    }

    /** @dataProvider insideBoundsProvider */
    public function testCoordinateInclusiveBounds(int $lat, int $lng): void
    {
        Coordinates::fromDecimal((float) $lat, (float) $lng);
        self::assertTrue(true);
    }
}
