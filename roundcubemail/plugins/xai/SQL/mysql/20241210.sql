CREATE TABLE IF NOT EXISTS xai_models (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    provider VARCHAR(255) NOT NULL,
    model VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY unique_model (provider, model)
) ROW_FORMAT=DYNAMIC ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS xai_message_summaries (
    message_id char(32) NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    language_code CHAR(5) NOT NULL,
    model_id INT UNSIGNED NOT NULL DEFAULT 0,
    summary TEXT,
    started_at DATETIME DEFAULT NULL,
    generated_at DATETIME DEFAULT NULL,
    PRIMARY KEY (user_id, message_id, language_code),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ROW_FORMAT=DYNAMIC ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
