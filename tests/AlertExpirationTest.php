<?php

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Proud_Alert_Expiration::check() in lib/proud-alert-expiration.php.
 *
 * Verifies that the request-time check correctly compares the stored expiration
 * date (in the site timezone) against the current time and deactivates
 * the alert bar when the end of the chosen day has passed.
 */
class AlertExpirationTest extends TestCase
{
    /** Tracks update_option calls: [ option_name => value ] */
    private array $updatedOptions = [];

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $this->updatedOptions = [];

        // Capture all update_option calls for assertion.
        Functions\when('update_option')->alias(function (string $key, $val): bool {
            $this->updatedOptions[$key] = $val;
            return true;
        });
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Stubs get_option to return the given values by option name.
     *
     * @param array $map  option_name => value
     */
    private function stubGetOption(array $map): void
    {
        Functions\when('get_option')->alias(function (string $key) use ($map) {
            return $map[$key] ?? false;
        });
    }

    // -----------------------------------------------------------------------
    // Tests
    // -----------------------------------------------------------------------

    /**
     * A date well in the past must deactivate the alert bar.
     * End-of-day semantics: 2020-01-01 23:59:59 UTC is long gone.
     */
    public function test_expired_date_deactivates_alert(): void
    {
        Functions\when('wp_timezone')->justReturn(new DateTimeZone('UTC'));
        $this->stubGetOption([
            'alert_expiration' => '2020-01-01',
            'alert_active'     => '1',
        ]);

        Proud_Alert_Expiration::check();

        $this->assertSame(0, $this->updatedOptions['alert_active'],
            'alert_active must be set to 0 when expiration has passed.');
        $this->assertSame('', $this->updatedOptions['alert_expiration'],
            'alert_expiration must be cleared when expiration has passed.');
    }

    /**
     * A date well in the future must not trigger deactivation.
     */
    public function test_future_date_does_nothing(): void
    {
        Functions\when('wp_timezone')->justReturn(new DateTimeZone('UTC'));
        $this->stubGetOption([
            'alert_expiration' => '2099-12-31',
            'alert_active'     => '1',
        ]);

        Proud_Alert_Expiration::check();

        $this->assertEmpty($this->updatedOptions,
            'update_option must not be called when expiration is in the future.');
    }

    /**
     * Today's date must NOT expire — end-of-day semantics keep the bar
     * visible through the chosen day and turn it off after midnight.
     *
     * We build "today" inside the mocked timezone to keep the test
     * deterministic regardless of the host machine's local clock offset.
     * This test has a 1-second window of flakiness at exactly 23:59:59
     * local time, which is acceptable.
     */
    public function test_today_does_not_expire(): void
    {
        $tz = new DateTimeZone('UTC');
        Functions\when('wp_timezone')->justReturn($tz);

        $today = (new DateTimeImmutable('now', $tz))->format('Y-m-d');
        $this->stubGetOption([
            'alert_expiration' => $today,
            'alert_active'     => '1',
        ]);

        Proud_Alert_Expiration::check();

        $this->assertEmpty($this->updatedOptions,
            'update_option must not be called when expiration is today (end-of-day semantics).');
    }

    /**
     * When alert_expiration is empty, the callback must return early without
     * calling update_option.
     */
    public function test_empty_expiration_is_noop(): void
    {
        Functions\when('wp_timezone')->justReturn(new DateTimeZone('UTC'));
        $this->stubGetOption([
            'alert_expiration' => '',
            'alert_active'     => '1',
        ]);

        Proud_Alert_Expiration::check();

        $this->assertEmpty($this->updatedOptions,
            'update_option must not be called when alert_expiration is empty.');
    }

    /**
     * When alert_active is falsy, the callback must return early without
     * calling update_option, even if a past expiration date is stored.
     */
    public function test_already_inactive_is_noop(): void
    {
        Functions\when('wp_timezone')->justReturn(new DateTimeZone('UTC'));
        $this->stubGetOption([
            'alert_expiration' => '2020-01-01',
            'alert_active'     => '0',
        ]);

        Proud_Alert_Expiration::check();

        $this->assertEmpty($this->updatedOptions,
            'update_option must not be called when alert_active is already falsy.');
    }

    /**
     * An unparseable string must be treated as "no expiration" — no-op.
     */
    public function test_unparseable_expiration_is_noop(): void
    {
        Functions\when('wp_timezone')->justReturn(new DateTimeZone('UTC'));
        $this->stubGetOption([
            'alert_expiration' => 'next tuesday',
            'alert_active'     => '1',
        ]);

        Proud_Alert_Expiration::check();

        $this->assertEmpty($this->updatedOptions,
            'update_option must not be called when alert_expiration is unparseable.');
    }

    /**
     * The old datetime format 'YYYY-MM-DD HH:MM' is no longer supported.
     * It must be treated as unparseable — no-op.
     */
    public function test_old_datetime_format_is_unparseable_noop(): void
    {
        Functions\when('wp_timezone')->justReturn(new DateTimeZone('UTC'));
        $this->stubGetOption([
            'alert_expiration' => '2020-01-01 00:00',
            'alert_active'     => '1',
        ]);

        Proud_Alert_Expiration::check();

        $this->assertEmpty($this->updatedOptions,
            'update_option must not be called for old datetime format — date-only is the only accepted format.');
    }

    /**
     * The old datetime-local format 'YYYY-MM-DDTHH:MM' is no longer supported.
     * It must be treated as unparseable — no-op.
     */
    public function test_old_datetime_local_format_is_unparseable_noop(): void
    {
        Functions\when('wp_timezone')->justReturn(new DateTimeZone('UTC'));
        $this->stubGetOption([
            'alert_expiration' => '2020-01-01T00:00',
            'alert_active'     => '1',
        ]);

        Proud_Alert_Expiration::check();

        $this->assertEmpty($this->updatedOptions,
            'update_option must not be called for old datetime-local format — date-only is the only accepted format.');
    }

    /**
     * Timezone correctness: a past date parsed in America/New_York must
     * still fire correctly even though UTC and NY differ.
     * 2020-06-01 end-of-day in NY = 2020-06-02 03:59:59 UTC — both are past.
     */
    public function test_timezone_aware_expiry_fires_for_past_local_date(): void
    {
        Functions\when('wp_timezone')->justReturn(new DateTimeZone('America/New_York'));
        $this->stubGetOption([
            'alert_expiration' => '2020-06-01',
            'alert_active'     => '1',
        ]);

        Proud_Alert_Expiration::check();

        $this->assertSame(0, $this->updatedOptions['alert_active'],
            'alert_active must be set to 0 when expiration has passed in the site timezone.');
        $this->assertSame('', $this->updatedOptions['alert_expiration'],
            'alert_expiration must be cleared when expiration has passed in the site timezone.');
    }
}
