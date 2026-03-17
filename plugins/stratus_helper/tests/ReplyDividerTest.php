<?php

/**
 * Tests for stratus_helper::outlook_reply_divider().
 *
 * The method receives an $args array from the message_compose_body hook and
 * transforms the quoted-reply HTML in place. The core logic is a regex
 * replacement and a strrpos() splice — both are testable directly.
 */
class ReplyDividerTest extends PHPUnit\Framework\TestCase
{
    private static stratus_helper $plugin;

    public static function setUpBeforeClass(): void
    {
        @include_once __DIR__ . '/../stratus_helper.php';
        $rcube  = rcmail::get_instance();
        $plugin = new stratus_helper($rcube->plugins);
        $prop = new ReflectionProperty('stratus_helper', 'rcmail');
        $prop->setValue($plugin, $rcube);
        self::$plugin = $plugin;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Build a minimal message double with the fields outlook_reply_divider reads.
     */
    private function makeMessage(array $headers = [], string $subject = 'Test Subject'): object
    {
        return new class($headers, $subject) {
            public object $headers;
            public string $subject;
            private array $data;

            public function __construct(array $data, string $subject)
            {
                $this->headers = new stdClass();
                $this->subject = $subject;
                $this->data    = $data;
            }

            public function get_header(string $name): string
            {
                return $this->data[$name] ?? '';
            }
        };
    }

    private function baseArgs(string $body, object $message): array
    {
        return [
            'mode'    => 'reply',
            'html'    => true,
            'body'    => $body,
            'message' => $message,
        ];
    }

    private function makeReplyBody(string $quotedContent = '<p>Original text.</p>'): string
    {
        return '<p>My reply here.</p>'
            . '<p id="reply-intro">On Mon, 15 Jan 2024 wrote:</p>'
            . '<blockquote>' . $quotedContent . '</blockquote>';
    }

    // ── Guard clauses ─────────────────────────────────────────────────────────

    public function test_skips_non_reply_mode(): void
    {
        $body = $this->makeReplyBody();
        $args = $this->baseArgs($body, $this->makeMessage());
        $args['mode'] = 'forward';

        $result = self::$plugin->outlook_reply_divider($args);
        $this->assertSame($body, $result['body']);
    }

    public function test_skips_when_html_is_false(): void
    {
        $body = $this->makeReplyBody();
        $args = $this->baseArgs($body, $this->makeMessage());
        $args['html'] = false;

        $result = self::$plugin->outlook_reply_divider($args);
        $this->assertSame($body, $result['body']);
    }

    public function test_skips_when_no_reply_intro_present(): void
    {
        $body = '<p>My reply.</p><blockquote><p>Quoted.</p></blockquote>';
        $args = $this->baseArgs($body, $this->makeMessage());

        $result = self::$plugin->outlook_reply_divider($args);
        $this->assertSame($body, $result['body']);
    }

    public function test_skips_when_message_is_null(): void
    {
        $body          = $this->makeReplyBody();
        $args          = $this->baseArgs($body, $this->makeMessage());
        $args['message'] = null;

        $result = self::$plugin->outlook_reply_divider($args);
        $this->assertSame($body, $result['body']);
    }

    public function test_skips_when_message_has_no_headers(): void
    {
        $msg          = $this->makeMessage();
        unset($msg->headers);

        $body = $this->makeReplyBody();
        $args = [
            'mode'    => 'reply',
            'html'    => true,
            'body'    => $body,
            'message' => $msg,
        ];

        $result = self::$plugin->outlook_reply_divider($args);
        $this->assertSame($body, $result['body']);
    }

    // ── Transformation ────────────────────────────────────────────────────────

    public function test_removes_reply_intro_paragraph(): void
    {
        $args   = $this->baseArgs($this->makeReplyBody(), $this->makeMessage([
            'from' => 'sender@example.com',
            'to'   => 'me@example.com',
            'date' => 'Mon, 15 Jan 2024 10:30:00 +0000',
        ]));
        $result = self::$plugin->outlook_reply_divider($args);

        $this->assertStringNotContainsString('<p id="reply-intro">', $result['body']);
    }

    public function test_inserts_hr_divider(): void
    {
        $args   = $this->baseArgs($this->makeReplyBody(), $this->makeMessage([
            'from' => 'sender@example.com',
            'to'   => 'me@example.com',
            'date' => 'Mon, 15 Jan 2024 10:30:00 +0000',
        ]));
        $result = self::$plugin->outlook_reply_divider($args);

        $this->assertStringContainsString('<hr id="reply-divider"', $result['body']);
    }

    public function test_inserts_reply_header_div(): void
    {
        $args   = $this->baseArgs($this->makeReplyBody(), $this->makeMessage([
            'from' => 'sender@example.com',
            'to'   => 'me@example.com',
            'date' => 'Mon, 15 Jan 2024 10:30:00 +0000',
        ]));
        $result = self::$plugin->outlook_reply_divider($args);

        $this->assertStringContainsString('<div id="reply-header"', $result['body']);
    }

    public function test_removes_outer_blockquote_wrapper(): void
    {
        $quoted = '<p>Original text.</p>';
        $args   = $this->baseArgs(
            '<p>Reply.</p><p id="reply-intro">wrote:</p><blockquote>' . $quoted . '</blockquote>',
            $this->makeMessage([
                'from' => 'a@b.com', 'to' => 'c@d.com',
                'date' => 'Mon, 15 Jan 2024 10:00:00 +0000',
            ])
        );
        $result = self::$plugin->outlook_reply_divider($args);

        // The outer blockquote open is replaced by the divider HTML
        // The outer blockquote close (last </blockquote>) should be removed
        $this->assertStringNotContainsString('</blockquote>', $result['body'],
            'Outer </blockquote> should have been removed');
    }

    public function test_preserves_quoted_content(): void
    {
        $innerContent = '<p>This is the <b>quoted</b> message.</p>';
        $args = $this->baseArgs(
            '<p>My reply.</p><p id="reply-intro">wrote:</p><blockquote>' . $innerContent . '</blockquote>',
            $this->makeMessage([
                'from' => 'a@b.com', 'to' => 'c@d.com',
                'date' => 'Mon, 15 Jan 2024 10:00:00 +0000',
            ])
        );
        $result = self::$plugin->outlook_reply_divider($args);

        $this->assertStringContainsString($innerContent, $result['body']);
    }

    public function test_handles_optional_leading_br(): void
    {
        // RC sometimes prepends <br> before the reply-intro in top-posting mode
        $body = '<p>Reply.</p><br><p id="reply-intro">wrote:</p><blockquote><p>Quoted.</p></blockquote>';
        $args = $this->baseArgs($body, $this->makeMessage([
            'from' => 'a@b.com', 'to' => 'c@d.com',
            'date' => 'Mon, 15 Jan 2024 10:00:00 +0000',
        ]));
        $result = self::$plugin->outlook_reply_divider($args);

        $this->assertStringNotContainsString('<p id="reply-intro">', $result['body']);
        $this->assertStringContainsString('<hr id="reply-divider"', $result['body']);
    }

    public function test_removes_only_last_blockquote_close_tag(): void
    {
        // Nested blockquotes: only the outermost </blockquote> should be removed
        $body = '<p>Reply.</p>'
            . '<p id="reply-intro">wrote:</p>'
            . '<blockquote>'
            .   '<p>Outer quote.</p>'
            .   '<blockquote><p>Nested quote.</p></blockquote>'
            . '</blockquote>';

        $args   = $this->baseArgs($body, $this->makeMessage([
            'from' => 'a@b.com', 'to' => 'c@d.com',
            'date' => 'Mon, 15 Jan 2024 10:00:00 +0000',
        ]));
        $result = self::$plugin->outlook_reply_divider($args);

        // Inner </blockquote> for the nested quote must survive
        $this->assertStringContainsString('</blockquote>', $result['body'],
            'Inner nested </blockquote> should be preserved');
    }
}
