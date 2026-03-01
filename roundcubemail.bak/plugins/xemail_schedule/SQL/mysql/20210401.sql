ALTER TABLE xemail_schedule_queue ADD sending TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE xemail_schedule_queue ADD INDEX xemail_schedule_queue_sending (sending);

ALTER TABLE xemail_schedule_queue ADD CONSTRAINT user_id_fk_xemail_schedule_queue
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE;

