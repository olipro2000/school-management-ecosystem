-- Add missing columns to payments table
ALTER TABLE payments 
ADD COLUMN IF NOT EXISTS receipt_screenshot VARCHAR(255) AFTER amount,
ADD COLUMN IF NOT EXISTS bank_reference VARCHAR(100) AFTER receipt_screenshot,
ADD COLUMN IF NOT EXISTS status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending' AFTER bank_reference,
ADD COLUMN IF NOT EXISTS remarks TEXT AFTER status,
ADD COLUMN IF NOT EXISTS verified_by INT AFTER remarks,
ADD COLUMN IF NOT EXISTS verified_at TIMESTAMP NULL AFTER verified_by;

-- Add foreign key if not exists
ALTER TABLE payments ADD CONSTRAINT fk_verified_by FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL;
