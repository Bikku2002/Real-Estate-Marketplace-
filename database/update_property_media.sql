-- Update properties table to include video and 3D preview fields
-- Run this script to add new media capabilities to properties

USE final6;

-- Add new columns for property video and 3D preview
ALTER TABLE properties 
ADD COLUMN IF NOT EXISTS property_video VARCHAR(255) NULL AFTER cover_image,
ADD COLUMN IF NOT EXISTS three_d_preview VARCHAR(255) NULL AFTER property_video;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_properties_video ON properties(property_video);
CREATE INDEX IF NOT EXISTS idx_properties_3d ON properties(three_d_preview);

-- Create directories for new file types
-- Note: These directories will be created automatically by PHP when files are uploaded
-- uploads/properties/videos/ - for property videos
-- uploads/properties/3d/ - for 3D model files

-- Update existing properties to have NULL values for new fields
UPDATE properties SET 
    property_video = NULL,
    three_d_preview = NULL 
WHERE property_video IS NULL OR three_d_preview IS NULL;

-- Verify the changes
DESCRIBE properties;

-- Show sample of updated structure
SELECT 
    id, 
    title, 
    type, 
    cover_image, 
    property_video, 
    three_d_preview, 
    created_at 
FROM properties 
LIMIT 5;
