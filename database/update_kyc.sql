-- Update existing database with KYC columns
-- This script will safely add columns if they don't exist

USE final6;

-- Add profile_image column if it doesn't exist
SET @column_exists = 0;
SELECT COUNT(*) INTO @column_exists 
FROM information_schema.columns 
WHERE table_schema = 'final6' 
AND table_name = 'users' 
AND column_name = 'profile_image';

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NULL AFTER password_hash;', 
    'SELECT "profile_image column already exists";');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add kyc_status column if it doesn't exist
SET @column_exists = 0;
SELECT COUNT(*) INTO @column_exists 
FROM information_schema.columns 
WHERE table_schema = 'final6' 
AND table_name = 'users' 
AND column_name = 'kyc_status';

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE users ADD COLUMN kyc_status ENUM(''pending'',''verified'',''rejected'') DEFAULT ''pending'' AFTER profile_image;', 
    'SELECT "kyc_status column already exists";');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add kyc_document_type column if it doesn't exist
SET @column_exists = 0;
SELECT COUNT(*) INTO @column_exists 
FROM information_schema.columns 
WHERE table_schema = 'final6' 
AND table_name = 'users' 
AND column_name = 'kyc_document_type';

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE users ADD COLUMN kyc_document_type ENUM(''citizenship'',''passport'',''license'') NULL AFTER kyc_status;', 
    'SELECT "kyc_document_type column already exists";');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add kyc_document_number column if it doesn't exist
SET @column_exists = 0;
SELECT COUNT(*) INTO @column_exists 
FROM information_schema.columns 
WHERE table_schema = 'final6' 
AND table_name = 'users' 
AND column_name = 'kyc_document_number';

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE users ADD COLUMN kyc_document_number VARCHAR(50) NULL AFTER kyc_document_type;', 
    'SELECT "kyc_document_number column already exists";');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add kyc_document_image column if it doesn't exist
SET @column_exists = 0;
SELECT COUNT(*) INTO @column_exists 
FROM information_schema.columns 
WHERE table_schema = 'final6' 
AND table_name = 'users' 
AND column_name = 'kyc_document_image';

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE users ADD COLUMN kyc_document_image VARCHAR(255) NULL AFTER kyc_document_number;', 
    'SELECT "kyc_document_image column already exists";');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add kyc_verified_at column if it doesn't exist
SET @column_exists = 0;
SELECT COUNT(*) INTO @column_exists 
FROM information_schema.columns 
WHERE table_schema = 'final6' 
AND table_name = 'users' 
AND column_name = 'kyc_verified_at';

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE users ADD COLUMN kyc_verified_at TIMESTAMP NULL AFTER kyc_document_image;', 
    'SELECT "kyc_verified_at column already exists";');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add kyc_notes column if it doesn't exist
SET @column_exists = 0;
SELECT COUNT(*) INTO @column_exists 
FROM information_schema.columns 
WHERE table_schema = 'final6' 
AND table_name = 'users' 
AND column_name = 'kyc_notes';

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE users ADD COLUMN kyc_notes TEXT NULL AFTER kyc_verified_at;', 
    'SELECT "kyc_notes column already exists";');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index on kyc_status if it doesn't exist
SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists 
FROM information_schema.statistics 
WHERE table_schema = 'final6' 
AND table_name = 'users' 
AND index_name = 'kyc_status';

SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE users ADD INDEX kyc_status (kyc_status);', 
    'SELECT "kyc_status index already exists";');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Show final table structure
DESCRIBE users;

-- Show success message
SELECT 'KYC columns update completed successfully!' as status;
