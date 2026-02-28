CREATE TABLE IF NOT EXISTS xemail_schedule_queue (
    message_id VARCHAR(180) NOT NULL DEFAULT '',
    user_id INTEGER NOT NULL DEFAULT 0,
    address_to VARCHAR(255) NOT NULL DEFAULT '',
    address_from VARCHAR(255) NOT NULL DEFAULT '',
    subject VARCHAR(255) NOT NULL DEFAULT '',
    mail_mime TEXT NOT NULL,
    send_time TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (message_id)
);

CREATE INDEX user_id ON xemail_schedule_queue (user_id);
CREATE INDEX send_time ON xemail_schedule_queue (send_time);
CREATE INDEX sent_at ON xemail_schedule_queue (sent_at);