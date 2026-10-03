-- Messagerie PLANIPRO
USE planipro;

CREATE TABLE IF NOT EXISTS conversations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    participant_a INT UNSIGNED NOT NULL,
    participant_b INT UNSIGNED NOT NULL,
    last_message_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_participants (participant_a, participant_b),
    CONSTRAINT fk_conv_a FOREIGN KEY (participant_a) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_conv_b FOREIGN KEY (participant_b) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_conv_last (last_message_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    lu TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_msg_conv FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    CONSTRAINT fk_msg_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_msg_conv (conversation_id, created_at),
    INDEX idx_msg_lu (lu)
) ENGINE=InnoDB;
