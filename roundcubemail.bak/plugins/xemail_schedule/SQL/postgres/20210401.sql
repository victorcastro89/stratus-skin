ALTER TABLE xemail_schedule_queue ADD sending SMALLINT NOT NULL DEFAULT 0;
CREATE INDEX xemail_schedule_queue_sending ON xemail_schedule_queue (sending);

ALTER TABLE xemail_schedule_queue ADD CONSTRAINT user_id_fk_xemail_schedule_queue
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE;
