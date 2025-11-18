-- Add profile_picture column to users table
USE school_ecosystem;

ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) AFTER profile_image;
