drop table expenses;

CREATE TABLE IF NOT EXISTS expenses
(
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id      INTEGER NOT NULL,
    date         TEXT    NOT NULL, -- ISO-8601 timestamp
    category     TEXT    NOT NULL,
    amount_cents FLOAT NOT NULL,
    description  TEXT,

    FOREIGN KEY (user_id)
        REFERENCES users (id)
        ON DELETE CASCADE
);