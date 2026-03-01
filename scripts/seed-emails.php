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

    public function __construct(string $host, int $port, array $users)
    {
        $this->host = $host;
        $this->port = $port;
        $this->users = $users;
    }

    public function seed(): void
    {
        echo "🌱 Starting email seeding...\n\n";

        foreach ($this->users as $user) {
            echo "📧 Seeding mailbox: {$user['email']}\n";
            $this->seedUserMailbox($user['email'], $user['password']);
            echo "\n";
        }

        echo "✅ Email seeding complete!\n";
    }

    private function seedUserMailbox(string $email, string $password): void
    {
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
        $otherUsers = array_filter($this->users, fn($u) => $u['email'] !== $email);

        // Seed different types of emails
        $this->seedInbox($imap, $email, $otherUsers);
        $this->seedSent($imap, $email, $otherUsers);
        $this->seedDrafts($imap, $email);
        $this->seedCustomFolders($imap, $email, $otherUsers);

        imap_close($imap);
        echo "  ✅ Completed\n";
    }

    private function seedInbox($imap, string $email, array $otherUsers): void
    {
        echo "  📥 INBOX...";
        
        $templates = [
            $this->createWelcomeEmail($email),
            $this->createMeetingInvite($email, $otherUsers[0]['email'] ?? 'alice@example.test'),
            $this->createNewsletterEmail($email),
            $this->createThreadedConversation($email, $otherUsers[0]['email'] ?? 'alice@example.test'),
            $this->createHtmlEmail($email, $otherUsers[1]['email'] ?? 'bob@example.test'),
            $this->createPlainTextEmail($email, $otherUsers[0]['email'] ?? 'alice@example.test'),
            $this->createEmailWithAttachment($email, $otherUsers[1]['email'] ?? 'bob@example.test'),
            $this->createUrgentEmail($email, $otherUsers[0]['email'] ?? 'alice@example.test'),
            $this->createOldEmail($email, 'support@example.com', 365),
            $this->createRecentEmail($email, $otherUsers[0]['email'] ?? 'alice@example.test', 1),
        ];

        foreach ($templates as $template) {
            imap_append($imap, "{{$this->host}:{$this->port}/novalidate-cert}INBOX", $template);
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
            imap_append($imap, "{{$this->host}:{$this->port}/novalidate-cert}Sent", $template);
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
            imap_append($imap, "{{$this->host}:{$this->port}/novalidate-cert}Drafts", $template, "\\Draft");
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
            imap_append($imap, "{{$this->host}:{$this->port}/novalidate-cert}Projects", $template);
        }

        echo " 2 emails\n";
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

    private function createThreadedConversation(string $to, string $from): string
    {
        $messageId = uniqid() . '@example.test';
        $date = date('r', strtotime('-3 days'));
        
        return <<<EMAIL
From: $from
To: $to
Subject: Re: Project Discussion
Date: $date
Message-ID: <$messageId>
In-Reply-To: <original@example.test>
References: <original@example.test>
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8

I agree with your points. Let's schedule a follow-up meeting.

What does your calendar look like next week?

Best,
{$this->getFirstName($from)}
EMAIL;
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
if (!extension_loaded('imap')) {
    echo "❌ Error: PHP IMAP extension is not installed.\n";
    echo "   Run: docker exec -it roundcube-dev apt-get install -y php-imap && docker-php-ext-enable imap\n";
    exit(1);
}

$seeder = new EmailSeeder(
    $config['mailserver'],
    $config['port'],
    $config['users']
);

$seeder->seed();
