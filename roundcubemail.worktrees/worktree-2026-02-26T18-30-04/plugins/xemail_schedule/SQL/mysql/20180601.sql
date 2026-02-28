CREATE TABLE IF NOT EXISTS xemail_schedule_queue (
    message_id VARCHAR(180) NOT NULL DEFAULT '',
    user_id INT UNSIGNED NOT NULL DEFAULT 0,
    address_to VARCHAR(255) NOT NULL DEFAULT '',
    address_from VARCHAR(255) NOT NULL DEFAULT '',
    subject VARCHAR(255) NOT NULL DEFAULT '',
    mail_mime LONGBLOB NOT NULL,
    send_time TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (message_id),
    INDEX (user_id),
    INDEX (send_time),
    INDEX (sent_at)
) ENGINE = InnoDB DEFAULT CHARSET utf8 COLLATE utf8_unicode_ci;

