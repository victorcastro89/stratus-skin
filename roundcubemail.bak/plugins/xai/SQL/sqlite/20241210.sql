
CREATE TABLE IF NOT EXISTS xai_models (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    provider VARCHAR(255) NOT NULL,
    model VARCHAR(255) NOT NULL,
    UNIQUE (provider, model)
);

CREATE TABLE IF NOT EXISTS xai_message_summaries (
    message_id CHAR(32) NOT NULL,
    user_id INTEGER NOT NULL,
    language_code CHAR(5) NOT NULL,
    model_id INTEGER NOT NULL DEFAULT 0,
    summary TEXT,
    started_at DATETIME DEFAULT NULL,
    generated_at DATETIME DEFAULT NULL,
    PRIMARY KEY (user_id, message_id, language_code),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE
);
