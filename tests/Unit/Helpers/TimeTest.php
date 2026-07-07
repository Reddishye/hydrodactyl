<?php

namespace Pterodactyl\Tests\Unit\Helpers;

use Pterodactyl\Helpers\Time;
use Pterodactyl\Tests\TestCase;

class TimeTest extends TestCase
{
    /**
     * Test that the timezone offset is returned in the correct format for UTC.
     */
    public function testGetMySQLTimezoneOffsetForUtc()
    {
        $offset = Time::getMySQLTimezoneOffset('UTC');

        $this->assertSame('+00:00', $offset);
    }

    /**
     * Test that the timezone offset is returned in the correct format for a positive offset.
     */
    public function testGetMySQLTimezoneOffsetForPositiveOffset()
    {
        $offset = Time::getMySQLTimezoneOffset('Asia/Tokyo');

        $this->assertSame('+09:00', $offset);
    }

    /**
     * Test that the timezone offset is returned in the correct format for a negative offset.
     */
    public function testGetMySQLTimezoneOffsetForNegativeOffset()
    {
        $offset = Time::getMySQLTimezoneOffset('America/New_York');

        $this->assertMatchesRegularExpression('/^-[0-9]{2}:[0-9]{2}$/', $offset);
    }

    /**
     * Test that the timezone offset string always matches the expected format.
     */
    public function testGetMySQLTimezoneOffsetFormat()
    {
        $offset = Time::getMySQLTimezoneOffset('Europe/London');

        $this->assertMatchesRegularExpression('/^[+-][0-9]{2}:[0-9]{2}$/', $offset);
    }
}
