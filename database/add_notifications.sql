-- Notifications table for system-wide notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('message', 'announcement', 'grade', 'payment', 'attendance', 'system') NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at)
);

-- Add message_type column to messages table for voice notes
ALTER TABLE messages ADD COLUMN message_type ENUM('text', 'voice', 'file') DEFAULT 'text' AFTER message;
