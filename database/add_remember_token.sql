-- Migration script to add remember me functionality
-- Run this script to update existing databases

-- Add remember token columns to users table
ALTER TABLE users 
ADD COLUMN remember_token VARCHAR(255) NULL AFTER is_active,
ADD COLUMN remember_token_expires TIMESTAMP NULL AFTER remember_token;

-- Create index for better performance on remember token queries
CREATE INDEX idx_remember_token ON users(remember_token_expires);

-- Update existing users to have NULL values for new columns
-- (This is handled automatically by the ALTER TABLE statements)

-- Note: This migration is safe to run on existing databases
-- The new columns are added with NULL values, which won't break existing functionality
