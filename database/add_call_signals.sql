USE school_ecosystem;

CREATE TABLE IF NOT EXISTS call_signals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    caller_id INT NOT NULL,
    receiver_id INT NOT NULL,
    signal_type ENUM('offer', 'answer', 'ice') NOT NULL,
    signal_data TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_receiver (receiver_id),
    INDEX idx_created (created_at)
);
