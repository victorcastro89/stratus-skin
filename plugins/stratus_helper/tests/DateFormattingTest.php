<?php

/**
 * Tests for stratus_helper date/timestamp helper methods.
 *
 * get_message_timestamp() is a pure parsing function: given a message date
 * value in any of the formats RC uses, it returns a Unix timestamp or null.
 * This is the only date logic worth unit-testing in isolation — the higher-level
 * messages_list() hook depends on rcmail::format_date() and should be covered
 * by integration tests.
 */
class DateFormattingTest extends PHPUnit\Framework\TestCase
{
    private static stratus_helper $plugin;

    public static function setUpBeforeClass(): void
    {
        // stratus_helper.php is already included by ColorSystemTest if that ran
        // first, but include_once makes this safe regardless of test order.
        @include_once __DIR__ . '/../stratus_helper.php';
        $plugin = new stratus_helper(rcube::get_instance()->plugins);
        $prop = new ReflectionProperty('stratus_helper', 'rcmail');
        $prop->setValue($plugin, rcmail::get_instance());
        self::$plugin = $plugin;
    }

    private function call(string $method, mixed ...$args): mixed
    {
        $ref = new ReflectionMethod(stratus_helper::class, $method);
        return $ref->invoke(self::$plugin, ...$args);
    }

    // ── get_message_timestamp ─────────────────────────────────────────────────

    public function test_numeric_int_returns_as_is(): void
    {
        $ts = 1700000000;
        $this->assertSame($ts, $this->call('get_message_timestamp', $ts));
    }

    public function test_numeric_string_returns_cast_to_int(): void
    {
        $this->assertSame(1700000000, $this->call('get_message_timestamp', '1700000000'));
    }

    public function test_datetime_interface_returns_timestamp(): void
    {
        $dt = new DateTime('2024-01-15 10:30:00', new DateTimeZone('UTC'));
        $this->assertSame($dt->getTimestamp(), $this->call('get_message_timestamp', $dt));
    }

    public function test_datetimeimmutable_returns_timestamp(): void
    {
        $dt = new DateTimeImmutable('2024-06-01 00:00:00', new DateTimeZone('UTC'));
        $this->assertSame($dt->getTimestamp(), $this->call('get_message_timestamp', $dt));
    }

    public function test_parseable_date_string_returns_timestamp(): void
    {
        $result = $this->call('get_message_timestamp', '2024-01-15 10:30:00');
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    public function test_rfc2822_date_string_returns_timestamp(): void
    {
        // Standard email date header format
        $result = $this->call('get_message_timestamp', 'Mon, 15 Jan 2024 10:30:00 +0000');
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    public function test_empty_string_returns_null(): void
    {
        $this->assertNull($this->call('get_message_timestamp', ''));
    }

    public function test_whitespace_only_returns_null(): void
    {
        $this->assertNull($this->call('get_message_timestamp', '   '));
    }

    public function test_null_returns_null(): void
    {
        $this->assertNull($this->call('get_message_timestamp', null));
    }

    public function test_zero_numeric_returns_zero(): void
    {
        // Timestamp 0 is valid (epoch) for numeric input
        $this->assertSame(0, $this->call('get_message_timestamp', 0));
    }
}
