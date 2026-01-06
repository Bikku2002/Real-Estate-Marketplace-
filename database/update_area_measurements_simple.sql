-- Update database schema for Nepal Area Measurement System (Simplified)
-- This adds support for multiple area units and conversions

USE final6;

-- Add new area_unit field to properties table
ALTER TABLE properties ADD COLUMN area_unit VARCHAR(20) DEFAULT 'sqft' AFTER area_ana;

-- Add index for better performance
CREATE INDEX idx_properties_area_unit ON properties(area_unit);

-- Update existing records to have proper area units
-- If area_ana exists, set unit to 'ana', otherwise 'sqft'
UPDATE properties SET area_unit = 'ana' WHERE area_ana IS NOT NULL AND area_ana > 0;
UPDATE properties SET area_unit = 'sqft' WHERE area_unit IS NULL OR area_unit = '';

-- Create a view for area conversions
CREATE OR REPLACE VIEW property_areas AS
SELECT 
    id,
    title,
    area_sqft,
    area_ana,
    area_unit,
    CASE 
        WHEN area_unit = 'sqft' THEN area_sqft
        WHEN area_unit = 'ana' THEN area_ana
        WHEN area_unit = 'paisa' THEN (area_ana * 4)
        WHEN area_unit = 'dhur' THEN (area_ana * 4)
        WHEN area_unit = 'ropani' THEN (area_ana / 16)
        WHEN area_unit = 'kattha' THEN (area_ana / 5)
        WHEN area_unit = 'bigha' THEN (area_ana / 400)
        WHEN area_unit = 'sqm' THEN (area_sqft * 0.092903)
        ELSE area_sqft
    END as area_primary,
    CASE 
        WHEN area_unit = 'sqft' THEN (area_sqft * 0.092903)
        WHEN area_unit = 'ana' THEN (area_ana * 342.25 * 0.092903)
        WHEN area_unit = 'paisa' THEN (area_ana * 4 * 85.56 * 0.092903)
        WHEN area_unit = 'dhur' THEN (area_ana * 4 * 85.56 * 0.092903)
        WHEN area_unit = 'ropani' THEN (area_ana * 5476 * 0.092903)
        WHEN area_unit = 'kattha' THEN (area_ana * 1711.2 * 0.092903)
        WHEN area_unit = 'bigha' THEN (area_ana * 34224 * 0.092903)
        WHEN area_unit = 'sqm' THEN area_sqft
        ELSE (area_sqft * 0.092903)
    END as area_sqm,
    CASE 
        WHEN area_unit = 'sqft' THEN area_sqft
        WHEN area_unit = 'ana' THEN (area_ana * 342.25)
        WHEN area_unit = 'paisa' THEN (area_ana * 4 * 85.56)
        WHEN area_unit = 'dhur' THEN (area_ana * 4 * 85.56)
        WHEN area_unit = 'ropani' THEN (area_ana * 5476)
        WHEN area_unit = 'kattha' THEN (area_ana * 1711.2)
        WHEN area_unit = 'bigha' THEN (area_ana * 34224)
        WHEN area_unit = 'sqm' THEN (area_sqft / 0.092903)
        ELSE area_sqft
    END as area_sqft_calculated
FROM properties;

-- Create a table for area unit preferences
CREATE TABLE IF NOT EXISTS user_area_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    preferred_unit VARCHAR(20) DEFAULT 'sqft',
    show_conversions BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_preference (user_id)
);

-- Insert default preferences for existing users
INSERT IGNORE INTO user_area_preferences (user_id, preferred_unit, show_conversions)
SELECT id, 'sqft', TRUE FROM users;

-- Create a table for area conversion history (for analytics)
CREATE TABLE IF NOT EXISTS area_conversion_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    from_unit VARCHAR(20),
    to_unit VARCHAR(20),
    from_value DECIMAL(15,4),
    to_value DECIMAL(15,4),
    conversion_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Add some sample data for testing
INSERT INTO area_conversion_log (user_id, from_unit, to_unit, from_value, to_value) VALUES
(1, 'ana', 'sqft', 1, 342.25),
(1, 'ropani', 'sqft', 1, 5476),
(1, 'kattha', 'sqft', 1, 1711.2);

-- Show the updated structure
DESCRIBE properties;
SELECT 'Area measurement system updated successfully!' as status;
