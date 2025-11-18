-- Critical performance indexes for 1000+ users

-- Messages optimization
CREATE INDEX IF NOT EXISTS idx_messages_receiver_unread ON messages(receiver_id, is_read, created_at);
CREATE INDEX IF NOT EXISTS idx_messages_conversation ON messages(sender_id, receiver_id, created_at);

-- Grades optimization  
CREATE INDEX IF NOT EXISTS idx_grades_student_term_year ON grades(student_id, term, academic_year);
CREATE INDEX IF NOT EXISTS idx_grades_subject_term ON grades(subject_id, term, academic_year);

-- Attendance optimization
CREATE INDEX IF NOT EXISTS idx_attendance_composite ON attendance(user_id, date, status);

-- Students optimization
CREATE INDEX IF NOT EXISTS idx_students_class_status ON students(class_id, status);

-- Announcements optimization
CREATE INDEX IF NOT EXISTS idx_announcements_audience_status ON announcements(audience, status, publish_date);

-- Activity logs cleanup (keep only 90 days)
DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Optimize tables
OPTIMIZE TABLE messages;
OPTIMIZE TABLE grades;
OPTIMIZE TABLE attendance;
OPTIMIZE TABLE students;
OPTIMIZE TABLE announcements;
