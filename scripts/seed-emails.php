#!/usr/bin/env php
<?php
/**
 * Email Seeder for Roundcube Testing
 * Generates diverse test emails via IMAP upload
 */

declare(strict_types=1);

// Configuration
$config = [
    'mailserver' => getenv('IMAP_HOST') ?: 'mailserver',
    'port' => (int)(getenv('IMAP_PORT') ?: 143),
    'users' => [
        ['email' => 'victor@example.test', 'password' => 'password123'],
        ['email' => 'alice@example.test', 'password' => 'password123'],
        ['email' => 'bob@example.test', 'password' => 'password123'],
    ],
];

class EmailSeeder
{
    private array $users;
    private string $host;
    private int $port;
    private bool $hasImap;

    public function __construct(string $host, int $port, array $users)
    {
        $this->host = $host;
        $this->port = $port;
        $this->users = $users;
        $this->hasImap = extension_loaded('imap');
    }

    public function seed(): void
    {
        echo "🌱 Starting email seeding...\n\n";

        if (!$this->hasImap) {
            echo "⚠️  PHP IMAP extension is not available. Using SMTP fallback (INBOX-only seeding).\n\n";
        }

        foreach ($this->users as $user) {
            echo "📧 Seeding mailbox: {$user['email']}\n";
            $this->seedUserMailbox($user['email'], $user['password']);
            echo "\n";
        }

        echo "✅ Email seeding complete!\n";
    }

    private function seedUserMailbox(string $email, string $password): void
    {
        if (!$this->hasImap) {
            $this->seedInboxViaSmtp($email);
            return;
        }

        $imap = @imap_open(
            "{{$this->host}:{$this->port}/novalidate-cert}INBOX",
            $email,
            $password
        );

        if (!$imap) {
            echo "  ⚠️  Could not connect: " . imap_last_error() . "\n";
            return;
        }

        // Get other users for conversation simulation
        $otherUsers = array_values(array_filter($this->users, fn($u) => $u['email'] !== $email));

        // Seed different types of emails
        $this->seedInbox($imap, $email, $otherUsers);
        $this->seedSent($imap, $email, $otherUsers);
        $this->seedDrafts($imap, $email);
        $this->seedCustomFolders($imap, $email, $otherUsers);

        imap_close($imap);
        echo "  ✅ Completed\n";
    }

    private function seedInboxViaSmtp(string $email): void
    {
        echo "  📥 INBOX (SMTP fallback)...";

        $otherUsers = array_values(array_filter($this->users, fn($u) => $u['email'] !== $email));

        $threadMessages = array_merge(
            $this->createConversationThread($email, $otherUsers[0]['email'] ?? 'alice@example.test', 3, 'Project Discussion'),
            $this->createConversationThread($email, $otherUsers[1]['email'] ?? 'bob@example.test', 5, 'Design Review'),
            $this->createConversationThread($email, $otherUsers[0]['email'] ?? 'alice@example.test', 8, 'Release Planning')
        );

        $templates = [
            $this->createWelcomeEmail($email),
            $this->createMeetingInvite($email, $otherUsers[0]['email'] ?? 'alice@example.test'),
            $this->createNewsletterEmail($email),
            $this->createHtmlEmail($email, $otherUsers[1]['email'] ?? 'bob@example.test'),
            $this->createPlainTextEmail($email, $otherUsers[0]['email'] ?? 'alice@example.test'),
            $this->createEmailWithAttachment($email, $otherUsers[1]['email'] ?? 'bob@example.test'),
            $this->createUrgentEmail($email, $otherUsers[0]['email'] ?? 'alice@example.test'),
            $this->createOldEmail($email, 'support@example.com', 365),
            $this->createRecentEmail($email, $otherUsers[0]['email'] ?? 'alice@example.test', 1),
        ];

        $templates = array_merge($templates, $threadMessages);

        $sent = 0;
        foreach ($templates as $template) {
            if ($this->sendViaSmtp($template, $email)) {
                $sent++;
            }
        }

        echo " {$sent}/" . count($templates) . " emails\n";
    }

    private function sendViaSmtp(string $rawMessage, string $recipient): bool
    {
        $smtpHost = getenv('SMTP_HOST') ?: $this->host;
        $smtpPort = (int)(getenv('SMTP_PORT') ?: 25);

        $fromHeader = $this->extractHeader($rawMessage, 'From') ?: 'admin@example.test';
        $fromAddress = $this->extractEmailAddress($fromHeader) ?: 'admin@example.test';

        $socket = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, 10);
        if (!$socket) {
            echo "\n    ⚠️  SMTP connect failed: {$errstr} ({$errno})";
            return false;
        }

        stream_set_timeout($socket, 10);

        try {
            $this->expectSmtpCode($socket, [220]);
            $this->smtpCommand($socket, 'EHLO seeder.local', [250]);
            $this->smtpCommand($socket, "MAIL FROM:<{$fromAddress}>", [250]);
            $this->smtpCommand($socket, "RCPT TO:<{$recipient}>", [250, 251]);
            $this->smtpCommand($socket, 'DATA', [354]);

            $normalized = str_replace(["\r\n", "\r"], "\n", $rawMessage);
            $lines = explode("\n", $normalized);
            $dotStuffed = [];
            foreach ($lines as $line) {
                $dotStuffed[] = str_starts_with($line, '.') ? '.' . $line : $line;
            }
            $messageData = implode("\r\n", $dotStuffed) . "\r\n.\r\n";
            fwrite($socket, $messageData);
            $this->expectSmtpCode($socket, [250]);
            $this->smtpCommand($socket, 'QUIT', [221]);
        } catch (RuntimeException $e) {
            fclose($socket);
            echo "\n    ⚠️  SMTP send failed for {$recipient}: {$e->getMessage()}";
            return false;
        }

        fclose($socket);
        return true;
    }

    private function smtpCommand($socket, string $command, array $okCodes): void
    {
        fwrite($socket, $command . "\r\n");
        $this->expectSmtpCode($socket, $okCodes);
    }

    private function expectSmtpCode($socket, array $okCodes): void
    {
        $response = '';
        while (($line = fgets($socket, 1024)) !== false) {
            $response .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }

        if ($response === '') {
            throw new RuntimeException('empty SMTP response');
        }

        $code = (int)substr($response, 0, 3);
        if (!in_array($code, $okCodes, true)) {
            throw new RuntimeException(trim($response));
        }
    }

    private function extractHeader(string $rawMessage, string $headerName): ?string
    {
        if (preg_match('/^' . preg_quote($headerName, '/') . ':\s*(.+)$/mi', $rawMessage, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function extractEmailAddress(string $input): ?string
    {
        if (preg_match('/<([^>]+)>/', $input, $matches)) {
            return trim($matches[1]);
        }

        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $input, $matches)) {
            return trim($matches[0]);
        }

        return null;
    }

    private function seedInbox($imap, string $email, array $otherUsers): void
    {
        echo "  📥 INBOX...";

        $threadMessages = array_merge(
            $this->createConversationThread($email, $otherUsers[0]['email'] ?? 'alice@example.test', 3, 'Project Discussion'),
            $this->createConversationThread($email, $otherUsers[1]['email'] ?? 'bob@example.test', 5, 'Design Review'),
            $this->createConversationThread($email, $otherUsers[0]['email'] ?? 'alice@example.test', 8, 'Release Planning')
        );
        
        $templates = [
            $this->createWelcomeEmail($email),
            $this->createMeetingInvite($email, $otherUsers[0]['email'] ?? 'alice@example.test'),
            $this->createNewsletterEmail($email),
            $this->createHtmlEmail($email, $otherUsers[1]['email'] ?? 'bob@example.test'),
            $this->createPlainTextEmail($email, $otherUsers[0]['email'] ?? 'alice@example.test'),
            $this->createEmailWithAttachment($email, $otherUsers[1]['email'] ?? 'bob@example.test'),
            $this->createUrgentEmail($email, $otherUsers[0]['email'] ?? 'alice@example.test'),
            $this->createOldEmail($email, 'support@example.com', 365),
            $this->createRecentEmail($email, $otherUsers[0]['email'] ?? 'alice@example.test', 1),
        ];

        $templates = array_merge($templates, $threadMessages);

        foreach ($templates as $template) {
            $this->appendMessage($imap, "INBOX", $template);
        }

        echo " " . count($templates) . " emails\n";
    }

    private function seedSent($imap, string $email, array $otherUsers): void
    {
        echo "  📤 Sent...";
        
        // Create Sent folder if it doesn't exist
        @imap_createmailbox($imap, "{{$this->host}:{$this->port}/novalidate-cert}Sent");

        $templates = [
            $this->createSentEmail($email, $otherUsers[0]['email'] ?? 'alice@example.test', 'Project Update', 'Just wanted to share the latest progress...'),
            $this->createSentEmail($email, $otherUsers[1]['email'] ?? 'bob@example.test', 'Meeting Notes', 'Here are the notes from our meeting...'),
            $this->createSentEmail($email, 'team@example.com', 'Weekly Report', 'This week\'s accomplishments...'),
        ];

        foreach ($templates as $template) {
            $this->appendMessage($imap, "Sent", $template);
        }

        echo " " . count($templates) . " emails\n";
    }

    private function seedDrafts($imap, string $email): void
    {
        echo "  📝 Drafts...";
        
        @imap_createmailbox($imap, "{{$this->host}:{$this->port}/novalidate-cert}Drafts");

        $templates = [
            $this->createDraftEmail($email, 'alice@example.test', 'Unfinished thoughts', 'This is a draft I started but never sent...'),
            $this->createDraftEmail($email, 'bob@example.test', 'TODO: Send this', 'Remember to finish this email...'),
        ];

        foreach ($templates as $template) {
            $this->appendMessage($imap, "Drafts", $template, "\\Draft");
        }

        echo " " . count($templates) . " emails\n";
    }

    private function seedCustomFolders($imap, string $email, array $otherUsers): void
    {
        echo "  📁 Custom folders...";
        
        @imap_createmailbox($imap, "{{$this->host}:{$this->port}/novalidate-cert}Projects");
        @imap_createmailbox($imap, "{{$this->host}:{$this->port}/novalidate-cert}Archive");

        $projectEmails = [
            $this->createProjectEmail($email, $otherUsers[0]['email'] ?? 'alice@example.test', 'Project Alpha'),
            $this->createProjectEmail($email, $otherUsers[1]['email'] ?? 'bob@example.test', 'Project Beta'),
        ];

        foreach ($projectEmails as $template) {
            $this->appendMessage($imap, "Projects", $template);
        }

        echo " 2 emails\n";
    }

    private function appendMessage($imap, string $mailbox, string $message, string $flags = ''): bool
    {
        $imapPath = "{{$this->host}:{$this->port}/novalidate-cert}{$mailbox}";
        $internalDate = $this->extractImapInternalDate($message);

        return imap_append($imap, $imapPath, $message, $flags, $internalDate ?: null);
    }

    private function extractImapInternalDate(string $rawMessage): string
    {
        $headerDate = $this->extractHeader($rawMessage, 'Date');
        if ($headerDate) {
            $timestamp = strtotime($headerDate);
            if ($timestamp !== false) {
                return date('d-M-Y H:i:s O', $timestamp);
            }
        }

        return date('d-M-Y H:i:s O');
    }

    // Email template generators
    private function createWelcomeEmail(string $to): string
    {
        $date = date('r');
        return <<<EMAIL
From: System Admin <admin@example.test>
To: $to
Subject: Welcome to Roundcube Webmail
Date: $date
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8

Welcome to your new email account!

This is a test email to verify your mailbox is working correctly.

Best regards,
System Administrator
EMAIL;
    }

    private function createMeetingInvite(string $to, string $from): string
    {
        $date = date('r', strtotime('-2 days'));
        $eventDate = date('Ymd\THis', strtotime('+1 week'));
        $uid = uniqid();
        
        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Roundcube Test//EN
METHOD:REQUEST
BEGIN:VEVENT
UID:{$uid}@example.test
DTSTAMP:{$eventDate}Z
DTSTART:{$eventDate}Z
DTEND:{$eventDate}Z
SUMMARY:Team Standup Meeting
DESCRIPTION:Weekly team sync meeting
LOCATION:Conference Room A
ORGANIZER:mailto:$from
ATTENDEE:mailto:$to
STATUS:CONFIRMED
SEQUENCE:0
END:VEVENT
END:VCALENDAR
ICS;

        return <<<EMAIL
From: $from
To: $to
Subject: Meeting Invitation: Team Standup
Date: $date
MIME-Version: 1.0
Content-Type: multipart/mixed; boundary="----BOUNDARY123"

------BOUNDARY123
Content-Type: text/plain; charset=UTF-8

You are invited to a meeting.

------BOUNDARY123
Content-Type: text/calendar; charset=UTF-8; method=REQUEST
Content-Disposition: attachment; filename="meeting.ics"

$ics
------BOUNDARY123--
EMAIL;
    }

    private function createNewsletterEmail(string $to): string
    {
        $date = date('r', strtotime('-5 days'));
        return <<<EMAIL
From: Newsletter <newsletter@example.com>
To: $to
Subject: 📰 Weekly Tech Digest - Edition #42
Date: $date
MIME-Version: 1.0
Content-Type: text/html; charset=UTF-8

<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
<h1 style="color: #2c3e50;">Weekly Tech Digest</h1>
<p>This week's top stories:</p>
<ul>
<li><a href="#">New Framework Release Announcement</a></li>
<li><a href="#">Best Practices for Email Testing</a></li>
<li><a href="#">Security Updates You Should Know</a></li>
</ul>
<p style="color: #7f8c8d; font-size: 12px;">
You're receiving this because you subscribed to our newsletter.<br>
<a href="#">Unsubscribe</a>
</p>
</body>
</html>
EMAIL;
    }

    private function createConversationThread(string $to, string $from, int $length, string $subject): array
    {
        $length = max(3, min(8, $length));
        $messages = [];
        $messageIds = [];
        $renderedBodies = [];
        $messageMeta = [];

        for ($i = 0; $i < $length; $i++) {
            $fromAddress = $i % 2 === 0 ? $from : $to;
            $toAddress = $i % 2 === 0 ? $to : $from;
            $date = date('r', strtotime('-' . (10 - $i) . ' hours'));
            $messageId = $this->generateMessageId();
            $messageIds[] = $messageId;

            $isReply = $i > 0;
            $subjectLine = $isReply ? "Re: {$subject}" : $subject;
            $inReplyTo = $isReply ? "\nIn-Reply-To: <{$messageIds[$i - 1]}>" : '';
            $references = $isReply ? "\nReferences: <" . implode('> <', array_slice($messageIds, 0, $i)) . ">" : '';

            $latestReplyText = $this->createThreadBody($i, $fromAddress, $subject);
            $body = $latestReplyText;

            if ($isReply) {
                $previousBody = $renderedBodies[$i - 1];
                $previousFrom = $messageMeta[$i - 1]['from'];
                $previousDate = $messageMeta[$i - 1]['date'];
                $body .= "\n\nOn {$previousDate}, {$previousFrom} wrote:\n" . $this->quoteForReply($previousBody);
            }

            $renderedBodies[] = $body;
            $messageMeta[] = ['from' => $fromAddress, 'date' => $date];

            $messages[] = <<<EMAIL
From: $fromAddress
To: $toAddress
Subject: $subjectLine
Date: $date
Message-ID: <$messageId>$inReplyTo$references
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8

$body
EMAIL;
        }

        return $messages;
    }

    private function quoteForReply(string $text): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = explode("\n", $normalized);

        return implode("\n", array_map(fn($line) => '> ' . $line, $lines));
    }

    private function createThreadBody(int $index, string $from, string $subject): string
    {
        $name = $this->getFirstName($from);

        $messages = [
            "Starting thread about {$subject}.\n\nCan we align on approach and timeline?\n\nBest,\n{$name}",
            "Great start. I reviewed the notes and agree with the direction.\n\nI added a few comments inline.\n\n- {$name}",
            "Thanks, this looks good.\n\nI can take the next action item and report back tomorrow.\n\n{$name}",
            "Quick update: first draft is done and pushed for review.\n\nPlease check when you have a moment.\n\n{$name}",
            "I reviewed the draft and left feedback.\n\nMain ask is tightening the edge-case handling.\n\n{$name}",
            "Applied the feedback and retested locally.\n\nAll core scenarios pass now.\n\n{$name}",
            "Looks much better now.\n\nIf no blockers, we can finalize this in today's sync.\n\n{$name}",
            "Perfect, closing the loop here.\n\nLet's track follow-ups in the next planning cycle.\n\nThanks!\n{$name}",
        ];

        return $messages[min($index, count($messages) - 1)];
    }

    private function generateMessageId(): string
    {
        return str_replace('.', '', uniqid('msg', true)) . '@example.test';
    }

    private function createHtmlEmail(string $to, string $from): string
    {
        $date = date('r', strtotime('-1 day'));
        return <<<EMAIL
From: $from
To: $to
Subject: Check out this design mockup
Date: $date
MIME-Version: 1.0
Content-Type: text/html; charset=UTF-8

<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; padding: 20px;">
<div style="max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
<h2 style="color: #3498db; border-bottom: 3px solid #3498db; padding-bottom: 10px;">Design Mockup</h2>
<p>Hi there!</p>
<p>I've prepared a new design mockup for the project. Here are the key features:</p>
<ul style="line-height: 1.8;">
<li><strong>Clean layout</strong> with modern aesthetics</li>
<li><strong>Responsive design</strong> that works on all devices</li>
<li><strong>Accessible</strong> color contrast ratios</li>
</ul>
<div style="background: #ecf0f1; padding: 15px; border-left: 4px solid #3498db; margin: 20px 0;">
<em>"Design is not just what it looks like and feels like. Design is how it works." - Steve Jobs</em>
</div>
<p>Let me know your thoughts!</p>
<p style="margin-top: 30px;">
Best regards,<br>
<strong>{$this->getFirstName($from)}</strong>
</p>
</div>
</body>
</html>
EMAIL;
    }

    private function createPlainTextEmail(string $to, string $from): string
    {
        $date = date('r', strtotime('-7 days'));
        return <<<EMAIL
From: $from
To: $to
Subject: Quick question about the API
Date: $date
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8

Hey,

I was reviewing the API documentation and had a quick question about the authentication flow.

Can we schedule a 15-minute call to discuss?

Thanks!
{$this->getFirstName($from)}
EMAIL;
    }

    private function createEmailWithAttachment(string $to, string $from): string
    {
        $date = date('r', strtotime('-4 days'));
        $attachmentContent = base64_encode("This is a sample PDF document content.\n\nLorem ipsum dolor sit amet...");
        
        return <<<EMAIL
From: $from
To: $to
Subject: 📎 Q4 Report (with attachment)
Date: $date
MIME-Version: 1.0
Content-Type: multipart/mixed; boundary="----ATTACHMENT_BOUNDARY"

------ATTACHMENT_BOUNDARY
Content-Type: text/plain; charset=UTF-8

Please find the Q4 report attached.

Let me know if you have any questions.

Best,
{$this->getFirstName($from)}

------ATTACHMENT_BOUNDARY
Content-Type: application/pdf; name="Q4_Report.pdf"
Content-Transfer-Encoding: base64
Content-Disposition: attachment; filename="Q4_Report.pdf"

$attachmentContent
------ATTACHMENT_BOUNDARY--
EMAIL;
    }

    private function createUrgentEmail(string $to, string $from): string
    {
        $date = date('r', strtotime('-2 hours'));
        return <<<EMAIL
From: $from
To: $to
Subject: 🚨 URGENT: Server maintenance tonight
Date: $date
Importance: high
Priority: urgent
X-Priority: 1
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8

URGENT NOTICE:

We will be performing critical server maintenance tonight from 10 PM to 2 AM.

All services will be offline during this window.

Please save your work and plan accordingly.

- IT Team
EMAIL;
    }

    private function createOldEmail(string $to, string $from, int $daysAgo): string
    {
        $date = date('r', strtotime("-{$daysAgo} days"));
        return <<<EMAIL
From: $from
To: $to
Subject: Old archived message from last year
Date: $date
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8

This is an old email from {$daysAgo} days ago.

It's useful for testing date sorting and archive functionality.

Best regards
EMAIL;
    }

    private function createRecentEmail(string $to, string $from, int $daysAgo): string
    {
        $date = date('r', strtotime("-{$daysAgo} days"));
        return <<<EMAIL
From: $from
To: $to
Subject: Recent message: Let's catch up
Date: $date
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8

Hey!

It's been a while. Want to grab coffee sometime this week?

Let me know!
{$this->getFirstName($from)}
EMAIL;
    }

    private function createSentEmail(string $from, string $to, string $subject, string $body): string
    {
        $date = date('r', strtotime('-3 days'));
        return <<<EMAIL
From: $from
To: $to
Subject: $subject
Date: $date
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8

$body

Sent from my Roundcube account.
EMAIL;
    }

    private function createDraftEmail(string $from, string $to, string $subject, string $body): string
    {
        $date = date('r');
        return <<<EMAIL
From: $from
To: $to
Subject: $subject
Date: $date
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8
X-Draft-Info: {"type":"draft"}

$body
EMAIL;
    }

    private function createProjectEmail(string $to, string $from, string $project): string
    {
        $date = date('r', strtotime('-10 days'));
        return <<<EMAIL
From: $from
To: $to
Subject: [$project] Status Update
Date: $date
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8

Project: $project
Status: On Track

Latest updates:
- Milestone 1: Completed
- Milestone 2: In Progress (75%)
- Milestone 3: Planned

Next steps will be discussed in our weekly meeting.

Regards
EMAIL;
    }

    private function getFirstName(string $email): string
    {
        $name = explode('@', $email)[0];
        return ucfirst($name);
    }
}

// Main execution
$seeder = new EmailSeeder(
    $config['mailserver'],
    $config['port'],
    $config['users']
);

$seeder->seed();
