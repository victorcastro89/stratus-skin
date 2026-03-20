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
    'ssl' => (bool)(getenv('IMAP_SSL') ?: (((int)(getenv('IMAP_PORT') ?: 143)) === 993)),
    'users' => [],
    'count' => (int)(getenv('SEED_COUNT') ?: 50),
];

// Allow single-user mode via env vars
if (getenv('IMAP_USER')) {
    $config['users'][] = [
        'email' => getenv('IMAP_USER'),
        'password' => getenv('IMAP_PASS') ?: '',
    ];
} else {
    // Default test accounts for Docker dev environment
    $config['users'] = [
        ['email' => 'victor@example.test', 'password' => 'password123'],
        ['email' => 'alice@example.test', 'password' => 'password123'],
        ['email' => 'bob@example.test', 'password' => 'password123'],
    ];
}

class EmailSeeder
{
    private array $users;
    private string $host;
    private int $port;
    private bool $ssl;
    private bool $hasImap;
    private string $nsPrefix = '';

    private int $count;

    private array $internalSenders = [];

    private array $randomSenders = [
        'Sarah Chen <sarah.chen@techcorp.io>',
        'Marcus Williams <marcus.w@startup.dev>',
        'Priya Patel <priya@designstudio.co>',
        'James O\'Brien <jobrien@enterprise.com>',
        'Yuki Tanaka <yuki.tanaka@global.jp>',
        'Elena Rodriguez <elena.r@consulting.biz>',
        'David Kim <dkim@fintech.io>',
        'Lisa Johansson <lisa.j@nordic.se>',
        'Ahmed Hassan <ahmed@cloudops.net>',
        'Rachel Green <rgreen@marketing.co>',
        'Tom Bradley <tom.bradley@agency.com>',
        'Nina Kowalski <nina.k@research.edu>',
        'Carlos Mendez <cmendez@logistics.com>',
        'Aisha Mohammed <aisha.m@healthcare.org>',
        'Kevin O\'Malley <kevin@devtools.io>',
    ];

    private array $randomSubjects = [
        'Quick sync on the roadmap',
        'Thoughts on the new proposal?',
        'Can you review this by EOD?',
        'Follow up from yesterday\'s call',
        'Budget approval needed',
        'New hire onboarding checklist',
        'Vendor contract renewal',
        'Feedback on the presentation',
        'Schedule change for next week',
        'FYI: Policy update',
        'Lunch plans?',
        'Conference travel arrangements',
        'Client feedback summary',
        'Quarterly goals check-in',
        'Re: Invoice #4821',
        'Introducing our new team member',
        'Parking lot discussion items',
        'Action items from standup',
        'Request for time off',
        'Happy birthday!',
    ];

    private array $randomBodies = [
        "Hi,\n\nJust wanted to loop back on our earlier conversation. I think we're aligned on the next steps, but let me know if anything has changed on your end.\n\nCheers",
        "Hey,\n\nAttaching the latest version of the document. I've incorporated all the feedback from the last round of reviews.\n\nPlease take a look when you get a chance.\n\nThanks",
        "Hi team,\n\nQuick reminder that the deadline for submissions is this Friday. Please make sure your sections are complete and uploaded to the shared drive.\n\nBest",
        "Hello,\n\nI've been looking into the issue you mentioned and I think I found the root cause. Let's discuss in our next 1:1.\n\nRegards",
        "Hi,\n\nJust a heads up — I'll be out of office next Monday and Tuesday. I've asked Sarah to cover for me on anything urgent.\n\nThanks for understanding",
        "Hey,\n\nGreat job on the presentation today! The client seemed really impressed with the demo. Let's keep the momentum going.\n\nCheers",
        "Hi,\n\nCould you send me the access credentials for the staging environment? I need to run some tests before the release.\n\nThanks",
        "Hello,\n\nPlease find below the summary from our last meeting:\n\n1. Finalize the design specs\n2. Review the test results\n3. Schedule the deployment window\n\nLet me know if I missed anything.",
        "Hi,\n\nI noticed a discrepancy in the latest report. The numbers on page 3 don't match what we discussed. Can you double-check?\n\nThanks",
        "Hey,\n\nAre you free for a quick coffee chat this afternoon? I'd love to pick your brain on something.\n\nNo rush if you're busy!",
    ];

    public function __construct(string $host, int $port, array $users, bool $ssl = false, int $count = 50)
    {
        $this->host = $host;
        $this->port = $port;
        $this->ssl = $ssl;
        $this->users = $users;
        $this->hasImap = extension_loaded('imap');
        $this->count = max(10, $count);

        // Build internal sender list from the primary user's domain
        $primaryEmail = $users[0]['email'] ?? '';
        $domain = strstr($primaryEmail, '@') ? ltrim(strstr($primaryEmail, '@'), '@') : 'example.test';
        $this->buildInternalSenders($domain);
    }

    private function buildInternalSenders(string $domain): void
    {
        $names = [
            ['Alex Turner',    'alex.turner'],
            ['Jamie Rivera',   'jrivera'],
            ['Morgan Lee',     'morgan.lee'],
            ['Casey Park',     'cpark'],
            ['Jordan Smith',   'jsmith'],
            ['Taylor Brown',   'taylor.b'],
            ['Sam Nguyen',     'snguyen'],
            ['Riley Davis',    'riley.davis'],
            ['Quinn Wilson',   'qwilson'],
            ['Avery Johnson',  'avery.j'],
        ];
        foreach ($names as [$display, $local]) {
            $this->internalSenders[] = "{$display} <{$local}@{$domain}>";
        }
    }

    private function imapPath(string $mailbox = 'INBOX'): string
    {
        $flags = $this->ssl ? '/ssl/novalidate-cert' : '/notls/novalidate-cert';
        $server = "{{$this->host}:{$this->port}{$flags}}";
        if ($mailbox === 'INBOX') {
            return "{$server}INBOX";
        }
        return "{$server}{$this->nsPrefix}{$mailbox}";
    }

    private function ensureMailbox($imap, string $folder): void
    {
        $path = $this->imapPath($folder);
        $exists = imap_list($imap, $this->imapPath('INBOX'), $this->nsPrefix . $folder);
        imap_errors(); // clear any queued errors
        if ($exists) {
            return;
        }
        @imap_createmailbox($imap, imap_utf7_encode($path));
        imap_errors(); // clear ALREADYEXISTS or other notices
    }

    private function detectNamespace($imap): void
    {
        $namespaces = imap_getmailboxes($imap, $this->imapPath('INBOX'), '*');
        if (!$namespaces) {
            return;
        }

        foreach ($namespaces as $mbox) {
            $name = $mbox->name;
            // Look for a mailbox like {server}INBOX.Sent or {server}INBOX/Sent
            if (preg_match('/}INBOX([\.\\/])/', $name, $m)) {
                $this->nsPrefix = 'INBOX' . $m[1];
                break;
            }
        }
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
            $this->imapPath('INBOX'),
            $email,
            $password
        );

        if (!$imap) {
            echo "  ⚠️  Could not connect: " . imap_last_error() . "\n";
            return;
        }

        $this->detectNamespace($imap);
        echo "  🔍 Namespace prefix: '" . $this->nsPrefix . "'\n";
        echo "  🔍 INBOX path: " . $this->imapPath('INBOX') . "\n";

        // Get other users for conversation simulation
        $otherUsers = array_values(array_filter($this->users, fn($u) => $u['email'] !== $email));

        // Seed different types of emails
        $this->seedInbox($imap, $email, $otherUsers);
        $this->seedBulk($imap, $email, $otherUsers);
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

        // Calculate start dates dynamically
        $now = time();
        $startDates = [
            $now, // today
            strtotime('-1 day', $now), // yesterday
            strtotime('-5 days', $now), // 5 days ago
        ];
        $threadMessages = array_merge(
            $this->createConversationThread($email, $otherUsers[0]['email'] ?? 'alice@example.test', 3, 'Project Discussion', $startDates[0]),
            $this->createConversationThread($email, $otherUsers[1]['email'] ?? 'bob@example.test', 5, 'Design Review', $startDates[1]),
            $this->createConversationThread($email, $otherUsers[0]['email'] ?? 'alice@example.test', 8, 'Release Planning', $startDates[2])
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

        // Target 50% threads. Fixed threads above = 16 msgs; generate extras to hit count/2.
        $threadTarget = (int)($this->count / 2);
        $extraThreadMsgsNeeded = max(0, $threadTarget - count($threadMessages));
        $threadSubjects = [
            'API integration plan', 'Performance review follow-up', 'Deployment timeline',
            'Feature spec feedback', 'Bug triage: auth failures', 'Q2 roadmap alignment',
            'Onboarding checklist update', 'Infrastructure cost review', 'UX audit findings',
            'Sprint retrospective items',
        ];
        $threadMsgsAdded = 0;
        $threadIdx = 0;
        while ($threadMsgsAdded < $extraThreadMsgsNeeded) {
            $subject = $threadSubjects[$threadIdx % count($threadSubjects)];
            $threadIdx++;
            $length = mt_rand(3, 6);
            $counterparty = $otherUsers[array_rand($otherUsers)]['email'] ?? 'alice@example.test';
            $daysAgo = mt_rand(0, 90);
            $start = strtotime("-{$daysAgo} days -" . mt_rand(0, 86400) . " seconds");
            $msgs = $this->createConversationThread($email, $counterparty, $length, $subject, $start);
            foreach ($msgs as $msg) {
                $templates[] = $msg;
                $threadMsgsAdded++;
                if ($threadMsgsAdded >= $extraThreadMsgsNeeded) break;
            }
        }

        // Fill remaining budget with random singles
        $remaining = $this->count - count($templates);
        if ($remaining > 0) {
            $bag = ['random','random','random','notification','notification','marketing','cc','forward','autoreply','inline_image'];
            for ($i = 0; $i < $remaining; $i++) {
                $type = $bag[array_rand($bag)];
                $daysAgo = mt_rand(0, 180);
                $date = date('r', strtotime("-{$daysAgo} days -" . mt_rand(0, 86400) . " seconds"));
                $sender = $this->pickRandomSender();
                $templates[] = match ($type) {
                    'notification' => $this->createNotificationEmail($email, $date),
                    'marketing'    => $this->createMarketingEmail($email, $date),
                    'cc'           => $this->createCcEmail($email, $sender, $date),
                    'forward'      => $this->createForwardedEmail($email, $sender, $date),
                    'autoreply'    => $this->createAutoReplyEmail($email, $sender, $date),
                    'inline_image' => $this->createInlineImageEmail($email, $sender, $date),
                    default        => $this->createRandomEmail($email, $sender, $date),
                };
            }
        }

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

        // Calculate start dates dynamically
        $now = time();
        $startDates = [
            $now, // today
            strtotime('-1 day', $now), // yesterday
            strtotime('-5 days', $now), // 5 days ago
        ];
        $threadMessages = array_merge(
            $this->createConversationThread($email, $otherUsers[0]['email'] ?? 'alice@example.test', 3, 'Project Discussion', $startDates[0]),
            $this->createConversationThread($email, $otherUsers[1]['email'] ?? 'bob@example.test', 5, 'Design Review', $startDates[1]),
            $this->createConversationThread($email, $otherUsers[0]['email'] ?? 'alice@example.test', 8, 'Release Planning', $startDates[2])
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

        $appended = 0;
        foreach ($templates as $template) {
            if ($this->appendMessage($imap, "INBOX", $template)) {
                $appended++;
            }
        }

        echo " {$appended}/" . count($templates) . " emails\n";
    }

    private function seedSent($imap, string $email, array $otherUsers): void
    {
        echo "  📤 Sent...";
        
        // Create Sent folder if it doesn't exist
        $this->ensureMailbox($imap, 'Sent');

        $templates = [
            $this->createSentEmail($email, $otherUsers[0]['email'] ?? 'alice@example.test', 'Project Update', 'Just wanted to share the latest progress...'),
            $this->createSentEmail($email, $otherUsers[1]['email'] ?? 'bob@example.test', 'Meeting Notes', 'Here are the notes from our meeting...'),
            $this->createSentEmail($email, 'team@example.com', 'Weekly Report', 'This week\'s accomplishments...'),
        ];

        $appended = 0;
        foreach ($templates as $template) {
            if ($this->appendMessage($imap, "Sent", $template)) {
                $appended++;
            }
        }

        echo " {$appended}/" . count($templates) . " emails\n";
    }

    private function seedDrafts($imap, string $email): void
    {
        echo "  📝 Drafts...";
        
        $this->ensureMailbox($imap, 'Drafts');

        $templates = [
            $this->createDraftEmail($email, 'alice@example.test', 'Unfinished thoughts', 'This is a draft I started but never sent...'),
            $this->createDraftEmail($email, 'bob@example.test', 'TODO: Send this', 'Remember to finish this email...'),
        ];

        $appended = 0;
        foreach ($templates as $template) {
            if ($this->appendMessage($imap, "Drafts", $template, "\\Draft")) {
                $appended++;
            }
        }

        echo " {$appended}/" . count($templates) . " emails\n";
    }

    private function seedCustomFolders($imap, string $email, array $otherUsers): void
    {
        echo "  📁 Custom folders...";
        
        $this->ensureMailbox($imap, 'Projects');
        $this->ensureMailbox($imap, 'Archive');

        $projectEmails = [
            $this->createProjectEmail($email, $otherUsers[0]['email'] ?? 'alice@example.test', 'Project Alpha'),
            $this->createProjectEmail($email, $otherUsers[1]['email'] ?? 'bob@example.test', 'Project Beta'),
        ];

        $appended = 0;
        foreach ($projectEmails as $template) {
            if ($this->appendMessage($imap, "Projects", $template)) {
                $appended++;
            }
        }

        echo " {$appended}/" . count($projectEmails) . " emails\n";
    }

    private function seedBulk($imap, string $email, array $otherUsers): void
    {
        // Existing templates already seed ~25 emails; bulk fills the rest
        $remaining = $this->count - 25;
        if ($remaining <= 0) {
            return;
        }

        // Target: 50% of total count should be thread messages.
        // seedInbox already seeds 16 thread messages (3+5+8).
        $threadTarget = (int)($this->count / 2);
        $extraThreadMsgsNeeded = max(0, $threadTarget - 16);

        $threadSubjects = [
            'API integration plan',
            'Performance review follow-up',
            'Deployment timeline',
            'Feature spec feedback',
            'Bug triage: auth failures',
            'Q2 roadmap alignment',
            'Onboarding checklist update',
            'Infrastructure cost review',
            'UX audit findings',
            'Sprint retrospective items',
            'Data migration approach',
            'Security policy changes',
            'Client feedback: v2 launch',
            'Contract renewal discussion',
            'Team offsite planning',
        ];

        $appended = 0;
        $threadMsgsAppended = 0;
        $threadIdx = 0;

        // Seed extra threads until we hit the thread target
        $allSenders = array_merge([$email], array_column($otherUsers, 'email'));
        while ($threadMsgsAppended < $extraThreadMsgsNeeded) {
            $subject = $threadSubjects[$threadIdx % count($threadSubjects)];
            $threadIdx++;
            $length = mt_rand(3, 6);
            $counterparty = count($allSenders) > 1
                ? $allSenders[array_rand(array_slice($allSenders, 1))]
                : ($otherUsers[0]['email'] ?? 'alice@example.test');
            $daysAgo = mt_rand(0, 90);
            $startTimestamp = strtotime("-{$daysAgo} days -" . mt_rand(0, 86400) . " seconds");

            $messages = $this->createConversationThread($email, $counterparty, $length, $subject, $startTimestamp);
            foreach ($messages as $j => $message) {
                $flags = ($j === count($messages) - 1) ? '' : '\\Seen'; // last message unread
                if ($this->appendMessage($imap, "INBOX", $message, $flags)) {
                    $appended++;
                    $threadMsgsAppended++;
                }
                if ($threadMsgsAppended >= $extraThreadMsgsNeeded) {
                    break;
                }
            }
        }

        // Fill remaining budget with random singles
        $singlesNeeded = $remaining - $threadMsgsAppended;

        echo "  📦 Bulk ({$threadMsgsAppended} thread msgs + {$singlesNeeded} singles)...";

        // Weighted template pool — ratios roughly mimic a real inbox
        $templateTypes = [
            'random'       => 30,
            'notification' => 20,
            'marketing'    => 15,
            'cc'           => 10,
            'forward'      => 10,
            'autoreply'    => 5,
            'inline_image' => 10,
        ];

        // Build weighted bag
        $bag = [];
        foreach ($templateTypes as $type => $weight) {
            for ($i = 0; $i < $weight; $i++) {
                $bag[] = $type;
            }
        }

        for ($i = 0; $i < $singlesNeeded; $i++) {
            $type = $bag[array_rand($bag)];
            $daysAgo = mt_rand(0, 180);
            $date = date('r', strtotime("-{$daysAgo} days -" . mt_rand(0, 86400) . " seconds"));
            $sender = $this->pickRandomSender();
            $flags = ($i % 3 === 0) ? '' : '\\Seen'; // ~1/3 unread

            $message = match ($type) {
                'notification' => $this->createNotificationEmail($email, $date),
                'marketing'    => $this->createMarketingEmail($email, $date),
                'cc'           => $this->createCcEmail($email, $sender, $date),
                'forward'      => $this->createForwardedEmail($email, $sender, $date),
                'autoreply'    => $this->createAutoReplyEmail($email, $sender, $date),
                'inline_image' => $this->createInlineImageEmail($email, $sender, $date),
                default        => $this->createRandomEmail($email, $sender, $date),
            };

            if ($this->appendMessage($imap, "INBOX", $message, $flags)) {
                $appended++;
            }
        }

        echo " {$appended}/{$remaining} emails\n";
    }

    private function pickRandomSender(): string
    {
        // 60% internal (same domain), 40% external
        if (!empty($this->internalSenders) && (mt_rand(1, 100) <= 60)) {
            return $this->internalSenders[array_rand($this->internalSenders)];
        }
        return $this->randomSenders[array_rand($this->randomSenders)];
    }

    private function createRandomEmail(string $to, string $from, string $date): string
    {
        $subject = $this->randomSubjects[array_rand($this->randomSubjects)];
        $body = $this->randomBodies[array_rand($this->randomBodies)];
        $messageId = $this->generateMessageId();

        return <<<EMAIL
From: $from
To: $to
Subject: $subject
Date: $date
Message-ID: <$messageId>
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8

$body
EMAIL;
    }

    private function createNotificationEmail(string $to, string $date): string
    {
        $messageId = $this->generateMessageId();
        $notifications = [
            [
                'from'    => 'GitHub <notifications@github.com>',
                'subject' => '[acme/webapp] Pull request #' . mt_rand(100, 999) . ': ' . $this->randomSubjects[array_rand($this->randomSubjects)],
                'body'    => $this->githubNotificationBody(),
            ],
            [
                'from'    => 'Jira <jira@acme.atlassian.net>',
                'subject' => '[PROJ-' . mt_rand(1000, 9999) . '] Issue updated: ' . $this->randomSubjects[array_rand($this->randomSubjects)],
                'body'    => $this->jiraNotificationBody(),
            ],
            [
                'from'    => 'CI/CD Pipeline <builds@ci.example.com>',
                'subject' => (mt_rand(0, 1) ? '✅' : '❌') . ' Build #' . mt_rand(400, 999) . ' — main',
                'body'    => $this->ciNotificationBody(),
            ],
            [
                'from'    => 'Sentry <noreply@sentry.io>',
                'subject' => '⚠️ New issue: TypeError in /api/users (seen ' . mt_rand(2, 200) . 'x)',
                'body'    => "A new issue was detected in production.\n\nTypeError: Cannot read property 'id' of undefined\n  at /api/users/handler.js:42\n  at processRequest (/lib/server.js:118)\n\nView issue: https://sentry.io/issues/" . mt_rand(100000, 999999),
            ],
            [
                'from'    => 'Slack <no-reply@slack.com>',
                'subject' => 'New message in #engineering',
                'body'    => "You have a new message in #engineering:\n\n@here Deploy going out in 10 minutes. Hold merges.\n\nReply in Slack to continue the conversation.",
            ],
        ];

        $n = $notifications[array_rand($notifications)];

        return <<<EMAIL
From: {$n['from']}
To: $to
Subject: {$n['subject']}
Date: $date
Message-ID: <$messageId>
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8
X-Mailer: notification-service/2.1

{$n['body']}
EMAIL;
    }

    private function githubNotificationBody(): string
    {
        $user = explode(' ', $this->randomSenders[array_rand($this->randomSenders)])[0];
        $actions = ['opened', 'commented on', 'approved', 'requested changes on', 'merged'];
        $action = $actions[array_rand($actions)];
        return "{$user} {$action} this pull request.\n\n> Refactored the auth middleware to support token rotation.\n> Added tests for edge cases around expired sessions.\n\n---\nYou are receiving this because you are subscribed to this thread.\nReply to this email directly or view it on GitHub.";
    }

    private function jiraNotificationBody(): string
    {
        $user = explode(' ', $this->randomSenders[array_rand($this->randomSenders)])[0];
        $statuses = ['To Do', 'In Progress', 'In Review', 'Done'];
        $from = $statuses[array_rand($statuses)];
        $to = $statuses[array_rand($statuses)];
        return "{$user} updated the issue.\n\nStatus changed: {$from} → {$to}\nPriority: Medium\nAssignee: You\n\nComment:\n\"Moving this forward — blocked dependency was resolved yesterday.\"\n\n---\nThis message was sent by Atlassian Jira.";
    }

    private function ciNotificationBody(): string
    {
        $pass = (bool)mt_rand(0, 1);
        $duration = mt_rand(30, 600);
        $mins = intdiv($duration, 60);
        $secs = $duration % 60;
        $status = $pass ? 'passed' : 'failed';
        $details = $pass
            ? "All 247 tests passed.\nCoverage: 84.2%\nArtifacts: 3 uploaded"
            : "2 tests failed:\n  - test_user_auth_flow (AssertionError)\n  - test_payment_webhook (TimeoutError)\n\n12 tests skipped.";
        return "Build #{$this->randomInt(400, 999)} {$status} in {$mins}m {$secs}s.\n\nBranch: main\nCommit: " . substr(md5((string)mt_rand()), 0, 8) . "\n\n{$details}";
    }

    private function createMarketingEmail(string $to, string $date): string
    {
        $messageId = $this->generateMessageId();
        $campaigns = [
            [
                'from'    => 'TechDeals Weekly <deals@techdeals.io>',
                'subject' => '🔥 Flash Sale: 70% off developer tools — Today only!',
                'html'    => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#fff;"><div style="background:linear-gradient(135deg,#ff6b35,#f7c948);padding:30px;text-align:center;color:#fff;"><h1 style="margin:0;font-size:28px;">FLASH SALE</h1><p style="font-size:18px;">Up to 70% off premium dev tools</p></div><div style="padding:20px;"><p>Hi there,</p><p>For the next 24 hours, get exclusive discounts on:</p><ul><li><strong>JetBrains All Products Pack</strong> — $89/yr (was $299)</li><li><strong>Figma Professional</strong> — $6/mo (was $15)</li><li><strong>1Password Teams</strong> — $2/mo (was $8)</li></ul><div style="text-align:center;margin:20px 0;"><a href="#" style="background:#ff6b35;color:#fff;padding:12px 30px;text-decoration:none;border-radius:5px;font-weight:bold;">SHOP NOW</a></div><p style="color:#999;font-size:11px;">You received this email because you signed up at techdeals.io.<br><a href="#">Unsubscribe</a> | <a href="#">Update preferences</a></p></div></div>',
            ],
            [
                'from'    => 'CloudHost Pro <marketing@cloudhost.pro>',
                'subject' => 'Your servers are lonely 😢 Upgrade to Pro and save 50%',
                'html'    => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;"><div style="background:#1a1a2e;padding:30px;text-align:center;"><h1 style="color:#e94560;margin:0;">Don\'t Miss Out!</h1><p style="color:#eee;">Your free trial ends in 3 days</p></div><div style="padding:20px;background:#f8f8f8;"><p>Upgrade to <strong>CloudHost Pro</strong> and get:</p><ul><li>Unlimited bandwidth</li><li>24/7 priority support</li><li>Automatic backups</li><li>One-click deployments</li></ul><p style="text-align:center;"><a href="#" style="background:#e94560;color:#fff;padding:10px 25px;text-decoration:none;border-radius:4px;">Upgrade Now — 50% OFF</a></p><p style="color:#999;font-size:11px;text-align:center;">2093 Marketing Blvd, San Jose, CA 95134<br><a href="#">Unsubscribe</a></p></div></div>',
            ],
            [
                'from'    => 'LearnCode Academy <hello@learncode.academy>',
                'subject' => 'New course alert: Master Rust in 30 days 🦀',
                'html'    => '<div style="font-family:Georgia,serif;max-width:600px;margin:0 auto;padding:20px;"><h2 style="color:#2d3748;">New Course Available</h2><p>Hi learner,</p><p>We just launched <strong>"Rust from Zero to Production"</strong> — our most requested course ever.</p><p>What you\'ll learn:</p><ol><li>Ownership & borrowing demystified</li><li>Building async web services with Actix</li><li>Error handling patterns that scale</li><li>Publishing your first crate</li></ol><p><a href="#" style="color:#e53e3e;font-weight:bold;">Start learning →</a></p><p style="color:#a0aec0;font-size:12px;">You\'re receiving this because you enrolled in a LearnCode course.<br><a href="#">Unsubscribe</a></p></div>',
            ],
        ];

        $c = $campaigns[array_rand($campaigns)];

        return <<<EMAIL
From: {$c['from']}
To: $to
Subject: {$c['subject']}
Date: $date
Message-ID: <$messageId>
MIME-Version: 1.0
Content-Type: text/html; charset=UTF-8
List-Unsubscribe: <mailto:unsub@example.com>
Precedence: bulk

<!DOCTYPE html>
<html><head><meta charset="UTF-8"></head>
<body>{$c['html']}</body>
</html>
EMAIL;
    }

    private function createCcEmail(string $to, string $from, string $date): string
    {
        $messageId = $this->generateMessageId();
        $cc1 = $this->randomSenders[array_rand($this->randomSenders)];
        $cc2 = $this->randomSenders[array_rand($this->randomSenders)];
        $subject = 'FYI: ' . $this->randomSubjects[array_rand($this->randomSubjects)];
        $body = $this->randomBodies[array_rand($this->randomBodies)] . "\n\n(CC'ing the rest of the team for visibility)";

        return <<<EMAIL
From: $from
To: $to
Cc: $cc1, $cc2
Subject: $subject
Date: $date
Message-ID: <$messageId>
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8

$body
EMAIL;
    }

    private function createForwardedEmail(string $to, string $from, string $date): string
    {
        $messageId = $this->generateMessageId();
        $originalSender = $this->randomSenders[array_rand($this->randomSenders)];
        $originalSubject = $this->randomSubjects[array_rand($this->randomSubjects)];
        $originalBody = $this->randomBodies[array_rand($this->randomBodies)];
        $originalDate = date('r', strtotime($date . ' -' . mt_rand(1, 30) . ' days'));
        $forwarderName = explode(' <', $from)[0];

        return <<<EMAIL
From: $from
To: $to
Subject: Fwd: $originalSubject
Date: $date
Message-ID: <$messageId>
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8

Thought you should see this.

— {$forwarderName}

---------- Forwarded message ----------
From: $originalSender
Date: $originalDate
Subject: $originalSubject
To: $from

$originalBody
EMAIL;
    }

    private function createAutoReplyEmail(string $to, string $originalTo, string $date): string
    {
        $messageId = $this->generateMessageId();
        $name = explode(' <', $originalTo)[0];
        $returnDate = date('M j', strtotime($date . ' +' . mt_rand(2, 14) . ' days'));
        $delegates = $this->randomSenders[array_rand($this->randomSenders)];

        return <<<EMAIL
From: $originalTo
To: $to
Subject: Out of Office: Re: {$this->randomSubjects[array_rand($this->randomSubjects)]}
Date: $date
Message-ID: <$messageId>
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8
Auto-Submitted: auto-replied
X-Auto-Response-Suppress: All

Hi,

Thank you for your email. I am currently out of the office and will return on {$returnDate}.

I will have limited access to email during this time. For urgent matters, please contact {$delegates}.

Best regards,
{$name}
EMAIL;
    }

    private function createInlineImageEmail(string $to, string $from, string $date): string
    {
        $messageId = $this->generateMessageId();
        $subject = $this->randomSubjects[array_rand($this->randomSubjects)];
        $name = explode(' <', $from)[0];
        $cid = 'img' . mt_rand(1000, 9999) . '@example.test';

        // 1x1 red PNG pixel (68 bytes) as a placeholder inline image
        $pngData = base64_encode(base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg=='
        ));

        return <<<EMAIL
From: $from
To: $to
Subject: $subject (with screenshot)
Date: $date
Message-ID: <$messageId>
MIME-Version: 1.0
Content-Type: multipart/related; boundary="----INLINE_BOUND"

------INLINE_BOUND
Content-Type: text/html; charset=UTF-8

<!DOCTYPE html>
<html><body>
<p>Hi,</p>
<p>Here's the screenshot I mentioned:</p>
<p><img src="cid:$cid" alt="Screenshot" style="max-width:100%;border:1px solid #ddd;border-radius:4px;" /></p>
<p>Let me know what you think.</p>
<p>— {$name}</p>
</body></html>

------INLINE_BOUND
Content-Type: image/png; name="screenshot.png"
Content-Transfer-Encoding: base64
Content-ID: <$cid>
Content-Disposition: inline; filename="screenshot.png"

$pngData
------INLINE_BOUND--
EMAIL;
    }

    private function randomInt(int $min, int $max): int
    {
        return mt_rand($min, $max);
    }

    private function appendMessage($imap, string $mailbox, string $message, string $flags = ''): bool
    {
        $imapPath = $this->imapPath($mailbox);
        $internalDate = $this->extractImapInternalDate($message);

        $result = imap_append($imap, $imapPath, $message, $flags, $internalDate ?: null);

        if (!$result) {
            $err = imap_last_error();
            echo "\n    ⚠️  imap_append failed for [{$mailbox}]: " . ($err ?: 'unknown error');
            // Drain queued errors
            imap_errors();
        }

        return $result;
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

    private function createConversationThread(
    string $mailboxOwner,
    string $counterparty,
    int $length,
    string $subject,
    ?int $startTimestamp = null
): array {
    $length = max(3, min(8, $length));
    $baseSubject = $this->normalizeThreadSubject($subject);

    $participants = [$mailboxOwner, $counterparty];
    $messages = [];
    $messageIds = [];
    $renderedBodies = [];
    $messageMeta = [];

    $threadBodies = [
        "Starting thread about {$baseSubject}.\nCan we align on approach and timeline?\n\nBest,\n" . $this->getFirstName($counterparty),
        "Looks good to me.\nI think we can keep the scope small for the first pass.\n\nThanks,\n" . $this->getFirstName($mailboxOwner),
        "Agreed.\nI'll prepare the initial draft and share it shortly.\n\nBest,\n" . $this->getFirstName($counterparty),
        "Perfect.\nPlease include the rollout steps and any blockers.\n\nThanks,\n" . $this->getFirstName($mailboxOwner),
        "Here is the latest update.\nI've attached the main points below for review.\n\nBest,\n" . $this->getFirstName($counterparty),
        "Reviewed.\nA couple of minor tweaks, but overall this is ready.\n\nThanks,\n" . $this->getFirstName($mailboxOwner),
        "Great, I'll finalize it today.\n\nBest,\n" . $this->getFirstName($counterparty),
        "Sounds good.\nLet's close this out after the final confirmation.\n\nThanks,\n" . $this->getFirstName($mailboxOwner),
    ];

    $start = $startTimestamp ?? time();
    $timestamps = [];
    for ($i = 0; $i < $length; $i++) {
        $timestamps[] = $start + ($i * 3600);
    }

    for ($i = 0; $i < $length; $i++) {
        $isReply = $i > 0;

        $fromAddress = $participants[$i % 2 === 0 ? 1 : 0];
        $toAddress = $participants[$i % 2 === 0 ? 0 : 1];

        if ($i === 0) {
            $fromAddress = $counterparty;
            $toAddress = $mailboxOwner;
        }

        $messageId = $this->generateMessageId();
        $messageIds[] = $messageId;

        $subjectLine = $isReply ? 'Re: ' . $baseSubject : $baseSubject;

        $body = $threadBodies[$i] ?? $this->createThreadBody($i, $fromAddress, $baseSubject);

        if ($isReply) {
            $previousBody = $renderedBodies[$i - 1];
            $previousFrom = $messageMeta[$i - 1]['from'];
            $previousDate = $messageMeta[$i - 1]['date'];
            $previousTo = $messageMeta[$i - 1]['to'];
            $previousSubject = $messageMeta[$i - 1]['subject'];

            $body .= "\n\n" . $this->outlookDivider($previousFrom, $previousDate, $previousTo, $previousSubject, $previousBody);
        }

        $date = date('r', $timestamps[$i]);

        $renderedBodies[] = $body;
        $messageMeta[] = [
            'from' => $fromAddress,
            'to' => $toAddress,
            'date' => $date,
            'message_id' => $messageId,
            'subject' => $subjectLine,
            'references' => $isReply ? array_slice($messageIds, 0, $i) : [],
            'in_reply_to' => $isReply ? $messageIds[$i - 1] : null,
        ];

        $messages[] = $this->renderPlainTextMessage([
            'from' => $fromAddress,
            'to' => $toAddress,
            'subject' => $subjectLine,
            'date' => $date,
            'message_id' => $messageId,
            'in_reply_to' => $isReply ? $messageIds[$i - 1] : null,
            'references' => $isReply ? array_slice($messageIds, 0, $i) : [],
            'body' => $body,
        ]);
    }

    return $messages;
}

private function renderPlainTextMessage(array $message): string
{
    $headers = [
        'From: ' . $message['from'],
        'To: ' . $message['to'],
        'Subject: ' . $message['subject'],
        'Date: ' . $message['date'],
        'Message-ID: ' . $this->formatMessageIdHeader($message['message_id']),
    ];

    if (!empty($message['in_reply_to'])) {
        $headers[] = 'In-Reply-To: ' . $this->formatMessageIdHeader($message['in_reply_to']);
    }

    if (!empty($message['references'])) {
        $headers[] = 'References: ' . implode(
            ' ',
            array_map([$this, 'formatMessageIdHeader'], $message['references'])
        );
    }

    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';

    return implode("\r\n", $headers) . "\r\n\r\n" . $message['body'];
}

private function formatMessageIdHeader(string $rawMessageId): string
{
    $trimmed = trim($rawMessageId);
    if (preg_match('/^<.+>$/', $trimmed)) {
        return $trimmed;
    }

    return '<' . $trimmed . '>';
}

private function normalizeThreadSubject(string $subject): string
{
    $normalized = trim($subject);
    $normalized = preg_replace('/^(\s*(re|fwd?)\s*:\s*)+/i', '', $normalized) ?? $normalized;
    $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

    return $normalized !== '' ? $normalized : 'Conversation';
}


    private function outlookDivider(string $from, string $date, string $to, string $subject, string $body): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $body);
        return implode("\n", [
            '________________________________________',
            'From: ' . $from,
            'Sent: ' . $date,
            'To: ' . $to,
            'Subject: ' . $subject,
            '',
            $normalized,
        ]);
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
Subject: Q4 Report 
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
    $config['users'],
    $config['ssl'],
    $config['count']
);

$seeder->seed();
