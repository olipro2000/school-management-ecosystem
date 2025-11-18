ALTER TABLE announcements ADD COLUMN class_id INT NULL AFTER audience;
ALTER TABLE announcements ADD FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE;
