<?php

/**
 * Tests for the undo_send plugin PHP side.
 *
 * Covers the three preference hook methods (prefs_section, prefs_list,
 * prefs_save) and the delay-validation logic. These methods are the only
 * server-side logic in the plugin — the rest is CSS/JS asset loading.
 */
class UndoSendTest extends PHPUnit\Framework\TestCase
{
    private static undo_send $plugin;
    private static rcmail $rcmail;

    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/../undo_send.php';

        self::$rcmail = rcmail::get_instance();
        $plugin = new undo_send(self::$rcmail->plugins);

        // Inject rcmail without triggering init() (which tries to load assets
        // and register output objects not available in CLI test mode).
        $prop = new ReflectionProperty('undo_send', 'rcmail');
        $prop->setValue($plugin, self::$rcmail);

        self::$plugin = $plugin;
    }

    protected function setUp(): void
    {
        // Reset config state touched by individual tests
        self::$rcmail->config->set('dont_override', []);
        self::$rcmail->config->set('undo_send_delay', 5);
        $_POST = [];
    }

    // ── prefs_section ─────────────────────────────────────────────────────────

    public function test_prefs_section_adds_undo_send_entry(): void
    {
        $args   = ['list' => []];
        $result = self::$plugin->prefs_section($args);

        $this->assertArrayHasKey('undo_send', $result['list']);
        $this->assertSame('undo_send', $result['list']['undo_send']['id']);
    }

    public function test_prefs_section_preserves_existing_sections(): void
    {
        $args   = ['list' => ['server' => ['id' => 'server', 'section' => 'Server']]];
        $result = self::$plugin->prefs_section($args);

        $this->assertArrayHasKey('server', $result['list']);
        $this->assertArrayHasKey('undo_send', $result['list']);
    }

    // ── prefs_list ────────────────────────────────────────────────────────────

    public function test_prefs_list_ignores_wrong_section(): void
    {
        $args   = ['section' => 'mailbox', 'blocks' => []];
        $result = self::$plugin->prefs_list($args);

        $this->assertSame($args, $result);
    }

    public function test_prefs_list_returns_delay_field_for_undo_send_section(): void
    {
        $args = [
            'section' => 'undo_send',
            'blocks'  => ['main' => ['options' => []]],
        ];
        $result = self::$plugin->prefs_list($args);

        $this->assertArrayHasKey('undo_send_delay', $result['blocks']['main']['options']);
    }

    public function test_prefs_list_hides_field_when_dont_override_set(): void
    {
        self::$rcmail->config->set('dont_override', ['undo_send_delay']);

        $args = [
            'section' => 'undo_send',
            'blocks'  => ['main' => ['options' => []]],
        ];
        $result = self::$plugin->prefs_list($args);

        $this->assertArrayNotHasKey('undo_send_delay', $result['blocks']['main']['options']);
    }

    // ── prefs_save ────────────────────────────────────────────────────────────

    public function test_prefs_save_ignores_wrong_section(): void
    {
        $_POST = ['_undo_send_delay' => '5'];
        $args  = ['section' => 'mailbox', 'prefs' => []];

        $result = self::$plugin->prefs_save($args);

        $this->assertArrayNotHasKey('undo_send_delay', $result['prefs']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideValidDelays')]
    public function test_prefs_save_accepts_valid_delays(int $delay): void
    {
        $_POST = ['_undo_send_delay' => (string) $delay];
        $args  = ['section' => 'undo_send', 'prefs' => []];

        $result = self::$plugin->prefs_save($args);

        $this->assertArrayHasKey('undo_send_delay', $result['prefs'],
            "Delay {$delay} should be accepted");
        $this->assertSame($delay, $result['prefs']['undo_send_delay']);
    }

    public static function provideValidDelays(): array
    {
        return [
            'disabled (0)'  => [0],
            '3 seconds'     => [3],
            '5 seconds'     => [5],
            '10 seconds'    => [10],
            '15 seconds'    => [15],
            '30 seconds'    => [30],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideInvalidDelays')]
    public function test_prefs_save_rejects_invalid_delays(string $input): void
    {
        $_POST = ['_undo_send_delay' => $input];
        $args  = ['section' => 'undo_send', 'prefs' => []];

        $result = self::$plugin->prefs_save($args);

        $this->assertArrayNotHasKey('undo_send_delay', $result['prefs'],
            "Invalid delay '{$input}' should be rejected");
    }

    public static function provideInvalidDelays(): array
    {
        // Only values whose (int) cast lands outside the allowed set are rejected.
        // Note: 'fast' → 0 (valid), '' → 0 (valid), '5.5' → 5 (valid) — those
        // are covered by the valid-delays test instead.
        return [
            'arbitrary number' => ['7'],
            'negative'         => ['-1'],
            'very large'       => ['9999'],
        ];
    }

    /**
     * Non-integer strings that PHP's (int) cast converts to a valid delay
     * are accepted because the validation is on the cast result, not the raw string.
     * This is documented behaviour: 'fast' → 0 (disabled), '5.5' → 5 (5 s).
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provideCoercedValidDelays')]
    public function test_prefs_save_accepts_coerced_valid_delays(string $input, int $expected): void
    {
        $_POST = ['_undo_send_delay' => $input];
        $args  = ['section' => 'undo_send', 'prefs' => []];

        $result = self::$plugin->prefs_save($args);

        $this->assertArrayHasKey('undo_send_delay', $result['prefs']);
        $this->assertSame($expected, $result['prefs']['undo_send_delay']);
    }

    public static function provideCoercedValidDelays(): array
    {
        return [
            'non-numeric string coerces to 0 (disabled)' => ['fast', 0],
            'float string coerces to int (5)'             => ['5.5', 5],
        ];
    }

    public function test_prefs_save_respects_dont_override(): void
    {
        self::$rcmail->config->set('dont_override', ['undo_send_delay']);
        $_POST = ['_undo_send_delay' => '10'];
        $args  = ['section' => 'undo_send', 'prefs' => []];

        $result = self::$plugin->prefs_save($args);

        $this->assertArrayNotHasKey('undo_send_delay', $result['prefs']);
    }

    public function test_prefs_save_preserves_other_prefs(): void
    {
        $_POST = ['_undo_send_delay' => '7'];
        $args  = ['section' => 'undo_send', 'prefs' => ['existing_key' => 'val']];

        $result = self::$plugin->prefs_save($args);

        // 7 is not in the allowed set → not saved
        $this->assertArrayNotHasKey('undo_send_delay', $result['prefs']);
        // Other prefs are untouched
        $this->assertSame('val', $result['prefs']['existing_key']);
    }
}
