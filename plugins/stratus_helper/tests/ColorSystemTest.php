<?php

/**
 * Tests for stratus_helper color-math private methods.
 *
 * These are pure functions with no side effects — they depend only on their
 * arguments and on each other, making them ideal unit-test targets.
 *
 * Private methods are accessed via ReflectionMethod so we can test them
 * directly without going through the full plugin init() lifecycle.
 */
class ColorSystemTest extends PHPUnit\Framework\TestCase
{
    private static stratus_helper $plugin;

    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/../stratus_helper.php';
        $plugin = new stratus_helper(rcube::get_instance()->plugins);
        // Inject rcmail without calling init() (which needs skin='stratus' config)
        $prop = new ReflectionProperty('stratus_helper', 'rcmail');
        $prop->setValue($plugin, rcmail::get_instance());
        self::$plugin = $plugin;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function call(string $method, mixed ...$args): mixed
    {
        $ref = new ReflectionMethod(stratus_helper::class, $method);
        return $ref->invoke(self::$plugin, ...$args);
    }

    private function isValidHex(string $color): bool
    {
        return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $color);
    }

    // ── hex_to_hsl ───────────────────────────────────────────────────────────

    public function test_hex_to_hsl_red(): void
    {
        [$h, $s, $l] = $this->call('hex_to_hsl', '#ff0000');
        $this->assertEqualsWithDelta(0.0, $h, 0.1);
        $this->assertEqualsWithDelta(100.0, $s, 0.1);
        $this->assertEqualsWithDelta(50.0, $l, 0.1);
    }

    public function test_hex_to_hsl_white(): void
    {
        [$h, $s, $l] = $this->call('hex_to_hsl', '#ffffff');
        $this->assertEqualsWithDelta(0.0, $h, 0.1);
        $this->assertEqualsWithDelta(0.0, $s, 0.1);
        $this->assertEqualsWithDelta(100.0, $l, 0.1);
    }

    public function test_hex_to_hsl_black(): void
    {
        [$h, $s, $l] = $this->call('hex_to_hsl', '#000000');
        $this->assertEqualsWithDelta(0.0, $h, 0.1);
        $this->assertEqualsWithDelta(0.0, $s, 0.1);
        $this->assertEqualsWithDelta(0.0, $l, 0.1);
    }

    public function test_hex_to_hsl_blue(): void
    {
        [$h, $s, $l] = $this->call('hex_to_hsl', '#0000ff');
        $this->assertEqualsWithDelta(240.0, $h, 0.1);
        $this->assertEqualsWithDelta(100.0, $s, 0.1);
        $this->assertEqualsWithDelta(50.0, $l, 0.1);
    }

    public function test_hex_to_hsl_shortform_expands(): void
    {
        $full  = $this->call('hex_to_hsl', '#ff0000');
        $short = $this->call('hex_to_hsl', '#f00');
        $this->assertEqualsWithDelta($full[0], $short[0], 0.01);
        $this->assertEqualsWithDelta($full[1], $short[1], 0.01);
        $this->assertEqualsWithDelta($full[2], $short[2], 0.01);
    }

    // ── hsl_to_hex ───────────────────────────────────────────────────────────

    public function test_hsl_to_hex_red(): void
    {
        $this->assertSame('#ff0000', $this->call('hsl_to_hex', 0.0, 100.0, 50.0));
    }

    public function test_hsl_to_hex_white(): void
    {
        $this->assertSame('#ffffff', $this->call('hsl_to_hex', 0.0, 0.0, 100.0));
    }

    public function test_hsl_to_hex_black(): void
    {
        $this->assertSame('#000000', $this->call('hsl_to_hex', 0.0, 0.0, 0.0));
    }

    public function test_hsl_to_hex_grey(): void
    {
        $result = $this->call('hsl_to_hex', 0.0, 0.0, 50.0);
        // 50% lightness, no saturation → mid-grey (127 or 128 depending on rounding)
        $this->assertMatchesRegularExpression('/^#(7f7f7f|808080)$/', $result);
    }

    // ── round-trip ───────────────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\DataProvider('provideRoundTripColors')]
    public function test_hex_hsl_roundtrip(string $hex): void
    {
        [$h, $s, $l] = $this->call('hex_to_hsl', $hex);
        $result = $this->call('hsl_to_hex', $h, $s, $l);
        // Allow ±1 per channel for floating-point rounding
        $origRgb   = array_map('hexdec', str_split(ltrim($hex, '#'), 2));
        $resultRgb = array_map('hexdec', str_split(ltrim($result, '#'), 2));
        foreach ($origRgb as $i => $expected) {
            $this->assertEqualsWithDelta($expected, $resultRgb[$i], 1,
                "Channel {$i} of {$hex} → HSL → hex round-trip");
        }
    }

    public static function provideRoundTripColors(): array
    {
        return [
            'indigo'  => ['#5c6bc0'],
            'green'   => ['#4caf50'],
            'orange'  => ['#ff9800'],
            'teal'    => ['#009688'],
            'red'     => ['#f44336'],
            'white'   => ['#ffffff'],
            'black'   => ['#000000'],
        ];
    }

    // ── relative_luminance ───────────────────────────────────────────────────

    public function test_relative_luminance_black(): void
    {
        $this->assertEqualsWithDelta(0.0, $this->call('relative_luminance', '#000000'), 0.0001);
    }

    public function test_relative_luminance_white(): void
    {
        $this->assertEqualsWithDelta(1.0, $this->call('relative_luminance', '#ffffff'), 0.0001);
    }

    public function test_relative_luminance_is_between_0_and_1(): void
    {
        $lum = $this->call('relative_luminance', '#5c6bc0');
        $this->assertGreaterThanOrEqual(0.0, $lum);
        $this->assertLessThanOrEqual(1.0, $lum);
    }

    // ── contrast_ratio ───────────────────────────────────────────────────────

    public function test_contrast_ratio_black_vs_white_is_21(): void
    {
        $ratio = $this->call('contrast_ratio', '#000000', '#ffffff');
        $this->assertEqualsWithDelta(21.0, $ratio, 0.01);
    }

    public function test_contrast_ratio_same_color_is_1(): void
    {
        $ratio = $this->call('contrast_ratio', '#5c6bc0', '#5c6bc0');
        $this->assertEqualsWithDelta(1.0, $ratio, 0.01);
    }

    public function test_contrast_ratio_is_commutative(): void
    {
        $ab = $this->call('contrast_ratio', '#5c6bc0', '#ffffff');
        $ba = $this->call('contrast_ratio', '#ffffff', '#5c6bc0');
        $this->assertEqualsWithDelta($ab, $ba, 0.0001);
    }

    public function test_contrast_ratio_is_at_least_1(): void
    {
        $ratio = $this->call('contrast_ratio', '#aabbcc', '#112233');
        $this->assertGreaterThanOrEqual(1.0, $ratio);
    }

    // ── lighten_hex / darken_hex ─────────────────────────────────────────────

    public function test_lighten_hex_increases_lightness(): void
    {
        $original = $this->call('hex_to_hsl', '#5c6bc0');
        $lighter  = $this->call('lighten_hex', '#5c6bc0', 10.0);
        $result   = $this->call('hex_to_hsl', $lighter);
        $this->assertGreaterThan($original[2], $result[2]);
    }

    public function test_darken_hex_decreases_lightness(): void
    {
        $original = $this->call('hex_to_hsl', '#5c6bc0');
        $darker   = $this->call('darken_hex', '#5c6bc0', 10.0);
        $result   = $this->call('hex_to_hsl', $darker);
        $this->assertLessThan($original[2], $result[2]);
    }

    public function test_lighten_hex_clamps_at_white(): void
    {
        // Lightening white should return white, not overflow
        $result = $this->call('lighten_hex', '#ffffff', 20.0);
        $this->assertSame('#ffffff', $result);
    }

    public function test_darken_hex_clamps_at_black(): void
    {
        // Darkening black should return black, not underflow
        $result = $this->call('darken_hex', '#000000', 20.0);
        $this->assertSame('#000000', $result);
    }

    public function test_lighten_darken_returns_valid_hex(): void
    {
        $this->assertTrue($this->isValidHex($this->call('lighten_hex', '#5c6bc0', 15.0)));
        $this->assertTrue($this->isValidHex($this->call('darken_hex', '#5c6bc0', 6.0)));
    }

    // ── sanitize_color ───────────────────────────────────────────────────────

    public function test_sanitize_color_valid_6char(): void
    {
        $this->assertSame('#5c6bc0', $this->call('sanitize_color', '#5c6bc0'));
    }

    public function test_sanitize_color_valid_3char(): void
    {
        $this->assertSame('#f0f', $this->call('sanitize_color', '#f0f'));
    }

    public function test_sanitize_color_without_hash_gets_hash(): void
    {
        $this->assertSame('#5c6bc0', $this->call('sanitize_color', '5c6bc0'));
    }

    public function test_sanitize_color_invalid_returns_fallback(): void
    {
        $this->assertSame('#5c6bc0', $this->call('sanitize_color', 'not-a-color'));
    }

    public function test_sanitize_color_empty_returns_fallback(): void
    {
        $this->assertSame('#5c6bc0', $this->call('sanitize_color', ''));
    }

    public function test_sanitize_color_strips_non_hex_chars(): void
    {
        // Characters outside #0-9a-fA-F are stripped
        $result = $this->call('sanitize_color', '#xyz123');
        // 'xyz' stripped → '#123' → valid 3-char
        $this->assertSame('#123', $result);
    }

    // ── sanitize_css_value ───────────────────────────────────────────────────

    public function test_sanitize_css_value_passes_clean_hex(): void
    {
        $this->assertSame('#5c6bc0', $this->call('sanitize_css_value', '#5c6bc0'));
    }

    public function test_sanitize_css_value_passes_rgba(): void
    {
        $val = 'rgba(92, 107, 192, 0.2)';
        $this->assertSame($val, $this->call('sanitize_css_value', $val));
    }

    public function test_sanitize_css_value_passes_linear_gradient(): void
    {
        $val = 'linear-gradient(135deg, #5c6bc0 0%, #7986cb 100%)';
        $this->assertSame($val, $this->call('sanitize_css_value', $val));
    }

    public function test_sanitize_css_value_strips_semicolons(): void
    {
        $this->assertStringNotContainsString(
            ';',
            $this->call('sanitize_css_value', 'red; color: blue')
        );
    }

    public function test_sanitize_css_value_strips_braces(): void
    {
        $result = $this->call('sanitize_css_value', '{color:red}');
        $this->assertStringNotContainsString('{', $result);
        $this->assertStringNotContainsString('}', $result);
    }

    public function test_sanitize_css_value_strips_css_comments(): void
    {
        $result = $this->call('sanitize_css_value', 'red /* injected */');
        $this->assertStringNotContainsString('/*', $result);
        $this->assertStringNotContainsString('*/', $result);
    }

    // ── hex_to_rgb ───────────────────────────────────────────────────────────

    public function test_hex_to_rgb_red(): void
    {
        $this->assertSame('255, 0, 0', $this->call('hex_to_rgb', '#ff0000'));
    }

    public function test_hex_to_rgb_white(): void
    {
        $this->assertSame('255, 255, 255', $this->call('hex_to_rgb', '#ffffff'));
    }

    public function test_hex_to_rgb_known_value(): void
    {
        // #5c6bc0 = 92, 107, 192
        $this->assertSame('92, 107, 192', $this->call('hex_to_rgb', '#5c6bc0'));
    }

    public function test_hex_to_rgb_shortform(): void
    {
        $this->assertSame('255, 0, 0', $this->call('hex_to_rgb', '#f00'));
    }

    // ── derive_full_palette ───────────────────────────────────────────────────

    public function test_derive_full_palette_produces_all_tokens(): void
    {
        $scheme = $this->call('derive_full_palette', ['primary' => '#5c6bc0', 'label' => 'Test']);

        $required = [
            'primary_dark', 'text_accent', 'text_accent_dark',
            'sidebar_bg', 'sidebar_gradient', 'sidebar_text',
            'sidebar_text_hover', 'sidebar_text_active', 'sidebar_active_bg',
            'surface_tint', 'hover_bg', 'selected_bg', 'focus_ring',
            'font', 'font_secondary', 'border',
            'dark_background', 'dark_surface', 'dark_surface_raised',
            'dark_font', 'dark_font_secondary', 'dark_border',
        ];

        foreach ($required as $token) {
            $this->assertArrayHasKey($token, $scheme, "Missing token: {$token}");
            $this->assertNotEmpty($scheme[$token], "Empty token: {$token}");
        }
    }

    public function test_derive_full_palette_hex_tokens_are_valid(): void
    {
        $scheme = $this->call('derive_full_palette', ['primary' => '#5c6bc0', 'label' => 'Test']);

        $hexTokens = [
            'primary_dark', 'text_accent', 'text_accent_dark',
            'sidebar_bg', 'sidebar_text', 'font', 'font_secondary', 'border',
            'dark_background', 'dark_surface', 'dark_surface_raised',
            'dark_font', 'dark_font_secondary', 'dark_border',
        ];

        foreach ($hexTokens as $token) {
            $this->assertTrue(
                $this->isValidHex($scheme[$token]),
                "Token '{$token}' is not a valid 6-char hex color: '{$scheme[$token]}'"
            );
        }
    }

    public function test_derive_full_palette_skips_existing_tokens(): void
    {
        $existing = '#aabbcc';  // pre-supplied valid hex
        $scheme = $this->call('derive_full_palette', [
            'primary'      => '#5c6bc0',
            'primary_dark' => $existing,  // should NOT be overwritten
            'label'        => 'Test',
        ]);

        $this->assertSame($existing, $scheme['primary_dark']);
    }

    public function test_derive_full_palette_text_accent_meets_contrast(): void
    {
        // text_accent must have ≥ 4.5:1 contrast against white (#ffffff)
        $scheme = $this->call('derive_full_palette', ['primary' => '#5c6bc0', 'label' => 'Test']);
        $ratio  = $this->call('contrast_ratio', $scheme['text_accent'], '#ffffff');
        $this->assertGreaterThanOrEqual(4.5, $ratio,
            "text_accent '{$scheme['text_accent']}' does not meet 4.5:1 contrast against white");
    }

    public function test_derive_full_palette_dark_font_meets_contrast(): void
    {
        // dark_font should have ≥ 7:1 AAA contrast against dark_background
        $scheme = $this->call('derive_full_palette', ['primary' => '#5c6bc0', 'label' => 'Test']);
        $ratio  = $this->call('contrast_ratio', $scheme['dark_font'], $scheme['dark_background']);
        $this->assertGreaterThanOrEqual(7.0, $ratio,
            "dark_font '{$scheme['dark_font']}' does not meet 7:1 AAA against dark_background '{$scheme['dark_background']}'");
    }

    public function test_derive_full_palette_works_for_desaturated_primary(): void
    {
        // Grey primary (#808080) has no hue — derivation must not crash
        $scheme = $this->call('derive_full_palette', ['primary' => '#808080', 'label' => 'Grey']);
        $this->assertArrayHasKey('text_accent', $scheme);
        $this->assertArrayHasKey('dark_background', $scheme);
    }

    public function test_derive_full_palette_works_for_near_black_primary(): void
    {
        $scheme = $this->call('derive_full_palette', ['primary' => '#111111', 'label' => 'Dark']);
        $this->assertArrayHasKey('text_accent', $scheme);
    }

    public function test_derive_full_palette_works_for_near_white_primary(): void
    {
        $scheme = $this->call('derive_full_palette', ['primary' => '#eeeeee', 'label' => 'Light']);
        $this->assertArrayHasKey('text_accent', $scheme);
    }
}
