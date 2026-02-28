
-- RC requires sequences to be named <table_name>_seq
CREATE SEQUENCE IF NOT EXISTS xai_models_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE TABLE IF NOT EXISTS xai_models (
    id INTEGER PRIMARY KEY DEFAULT nextval('xai_models_seq'),
    provider VARCHAR(255) NOT NULL,
    model VARCHAR(255) NOT NULL,
    CONSTRAINT unique_model UNIQUE (provider, model)
);

CREATE TABLE IF NOT EXISTS xai_message_summaries (
    message_id CHAR(32) NOT NULL,
    user_id INTEGER NOT NULL,
    language_code CHAR(5) NOT NULL,
    model_id INTEGER NOT NULL DEFAULT 0,
    summary TEXT,
    started_at TIMESTAMP NULL,
    generated_at TIMESTAMP NULL,
    PRIMARY KEY (user_id, message_id, language_code),
    FOREIGN KEY (user_id)
    REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE
);
