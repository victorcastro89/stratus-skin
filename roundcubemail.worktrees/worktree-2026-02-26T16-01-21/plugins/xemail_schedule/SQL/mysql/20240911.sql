ALTER TABLE `xemail_schedule_queue` ROW_FORMAT=DYNAMIC;
ALTER TABLE `xemail_schedule_queue` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `xemail_schedule_queue` ADD server_config MEDIUMTEXT NOT NULL;