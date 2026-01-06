-- Property Availability Management System
-- This system tracks property availability status and provides content-based filtering

-- 1. Enhance properties table with availability tracking
ALTER TABLE properties 
ADD COLUMN IF NOT EXISTS availability_status ENUM('available', 'under_offer', 'sold', 'withdrawn', 'expired') DEFAULT 'available' AFTER status,
ADD COLUMN IF NOT EXISTS available_from TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER availability_status,
ADD COLUMN IF NOT EXISTS available_until TIMESTAMP NULL AFTER available_from,
ADD COLUMN IF NOT EXISTS last_status_change TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER available_until,
ADD COLUMN IF NOT EXISTS status_change_reason VARCHAR(255) NULL AFTER last_status_change,
ADD COLUMN IF NOT EXISTS property_features JSON NULL AFTER status_change_reason,
ADD COLUMN IF NOT EXISTS property_tags VARCHAR(500) NULL AFTER property_features,
ADD COLUMN IF NOT EXISTS view_count INT DEFAULT 0 AFTER property_tags,
ADD COLUMN IF NOT EXISTS favorite_count INT DEFAULT 0 AFTER view_count;

-- 2. Create property availability history table
CREATE TABLE IF NOT EXISTS property_availability_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    old_status ENUM('available', 'under_offer', 'sold', 'withdrawn', 'expired') NOT NULL,
    new_status ENUM('available', 'under_offer', 'sold', 'withdrawn', 'expired') NOT NULL,
    changed_by INT NOT NULL, -- user_id who made the change
    change_reason VARCHAR(255) NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_availability_history_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    CONSTRAINT fk_availability_history_user FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (property_id, changed_at),
    INDEX (new_status, changed_at)
);

-- 3. Create property features table for content-based filtering
CREATE TABLE IF NOT EXISTS property_features (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    feature_name VARCHAR(100) NOT NULL,
    feature_value VARCHAR(255) NULL,
    feature_type ENUM('amenity', 'infrastructure', 'location', 'property_type', 'custom') DEFAULT 'amenity',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_property_features_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    UNIQUE KEY unique_property_feature (property_id, feature_name),
    INDEX (feature_name, feature_value),
    INDEX (feature_type, feature_name)
);

-- 4. Create user preferences table for personalized recommendations
CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    preference_type ENUM('property_type', 'district', 'price_range', 'area_range', 'features', 'amenities') NOT NULL,
    preference_key VARCHAR(100) NOT NULL,
    preference_value VARCHAR(255) NOT NULL,
    preference_weight DECIMAL(3,2) DEFAULT 1.00, -- 0.00 to 1.00
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_preferences_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_preference (user_id, preference_type, preference_key),
    INDEX (user_id, preference_type),
    INDEX (preference_key, preference_value)
);

-- 5. Create property view tracking table
CREATE TABLE IF NOT EXISTS property_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    user_id INT NULL, -- NULL for anonymous users
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    view_duration INT NULL, -- in seconds
    source_page VARCHAR(100) NULL, -- where the view came from
    user_agent TEXT NULL,
    ip_address VARCHAR(45) NULL,
    CONSTRAINT fk_property_views_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    CONSTRAINT fk_property_views_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX (property_id, viewed_at),
    INDEX (user_id, viewed_at),
    INDEX (viewed_at)
);

-- 6. Create property recommendations table
CREATE TABLE IF NOT EXISTS property_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    property_id INT NOT NULL,
    recommendation_score DECIMAL(5,4) NOT NULL, -- 0.0000 to 1.0000
    recommendation_reason VARCHAR(255) NULL,
    recommendation_type ENUM('content_based', 'collaborative', 'popularity', 'location', 'price') DEFAULT 'content_based',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    CONSTRAINT fk_property_recommendations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_property_recommendations_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_property_recommendation (user_id, property_id),
    INDEX (user_id, recommendation_score DESC),
    INDEX (recommendation_type, recommendation_score DESC),
    INDEX (expires_at)
);

-- 7. Create property search history table for better recommendations
CREATE TABLE IF NOT EXISTS property_search_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    search_query TEXT NOT NULL,
    search_filters JSON NULL,
    results_count INT DEFAULT 0,
    clicked_properties JSON NULL, -- Array of property IDs that were clicked
    search_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_property_search_history_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id, search_date DESC),
    INDEX (search_date)
);

-- 8. Insert sample property features for content-based filtering
INSERT IGNORE INTO property_features (property_id, feature_name, feature_value, feature_type) VALUES
-- Sample features for existing properties
(1, 'road_access', 'yes', 'infrastructure'),
(1, 'water_supply', 'yes', 'infrastructure'),
(1, 'electricity', 'yes', 'infrastructure'),
(1, 'drainage', 'yes', 'infrastructure'),
(1, 'security', 'yes', 'amenity'),
(1, 'parking', 'yes', 'amenity'),
(1, 'garden', 'yes', 'amenity'),
(1, 'near_school', 'yes', 'location'),
(1, 'near_hospital', 'yes', 'location'),
(1, 'near_market', 'yes', 'location'),

(2, 'road_access', 'yes', 'infrastructure'),
(2, 'water_supply', 'yes', 'infrastructure'),
(2, 'electricity', 'yes', 'infrastructure'),
(2, 'drainage', 'yes', 'infrastructure'),
(2, 'security', 'yes', 'amenity'),
(2, 'parking', 'yes', 'amenity'),
(2, 'garden', 'yes', 'amenity'),
(2, 'near_school', 'yes', 'location'),
(2, 'near_hospital', 'yes', 'location'),
(2, 'near_market', 'yes', 'location'),
(2, 'air_conditioning', 'yes', 'amenity'),
(2, 'heating', 'yes', 'amenity'),
(2, 'elevator', 'yes', 'amenity'),
(2, 'balcony', 'yes', 'amenity'),
(2, 'terrace', 'yes', 'amenity');

-- 9. Insert sample user preferences for testing recommendations
INSERT IGNORE INTO user_preferences (user_id, preference_type, preference_key, preference_value, preference_weight) VALUES
-- User 2 (buyer) preferences
(2, 'property_type', 'house', 'house', 0.9),
(2, 'district', 'Kathmandu', 'Kathmandu', 0.8),
(2, 'district', 'Lalitpur', 'Lalitpur', 0.7),
(2, 'price_range', 'max_price', '30000000', 0.9),
(2, 'area_range', 'min_area', '1500', 0.8),
(2, 'features', 'parking', 'yes', 0.9),
(2, 'features', 'garden', 'yes', 0.7),
(2, 'features', 'security', 'yes', 0.8),
(2, 'amenities', 'near_school', 'yes', 0.6),
(2, 'amenities', 'near_market', 'yes', 0.7);

-- 10. Create triggers for automatic status updates
DELIMITER //

-- Trigger to update availability history when status changes
CREATE TRIGGER IF NOT EXISTS tr_property_status_change
AFTER UPDATE ON properties
FOR EACH ROW
BEGIN
    IF OLD.availability_status != NEW.availability_status THEN
        INSERT INTO property_availability_history (property_id, old_status, new_status, changed_by, change_reason)
        VALUES (NEW.id, OLD.availability_status, NEW.availability_status, NEW.seller_id, NEW.status_change_reason);
    END IF;
END//

-- Trigger to update view count when property is viewed
CREATE TRIGGER IF NOT EXISTS tr_property_view_count
AFTER INSERT ON property_views
FOR EACH ROW
BEGIN
    UPDATE properties 
    SET view_count = view_count + 1 
    WHERE id = NEW.property_id;
END//

-- Trigger to update favorite count when favorites change
CREATE TRIGGER IF NOT EXISTS tr_property_favorite_count
AFTER INSERT ON favorites
FOR EACH ROW
BEGIN
    UPDATE properties 
    SET favorite_count = favorite_count + 1 
    WHERE id = NEW.property_id;
END//

CREATE TRIGGER IF NOT EXISTS tr_property_favorite_count_delete
AFTER DELETE ON favorites
FOR EACH ROW
BEGIN
    UPDATE properties 
    SET favorite_count = favorite_count - 1 
    WHERE id = OLD.property_id;
END//

DELIMITER ;

-- 11. Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_properties_availability_status ON properties(availability_status);
CREATE INDEX IF NOT EXISTS idx_properties_available_from ON properties(available_from);
CREATE INDEX IF NOT EXISTS idx_properties_view_count ON properties(view_count DESC);
CREATE INDEX IF NOT EXISTS idx_properties_favorite_count ON properties(favorite_count DESC);
CREATE INDEX IF NOT EXISTS idx_properties_features ON properties(property_features);
CREATE INDEX IF NOT EXISTS idx_properties_tags ON properties(property_tags);

-- 12. Update existing properties to have proper availability status
UPDATE properties SET 
    availability_status = 'available',
    available_from = created_at,
    property_tags = CONCAT(type, ', ', district, ', ', 
                          CASE WHEN type = 'house' THEN CONCAT(bedrooms, 'BHK') ELSE 'land' END),
    property_features = JSON_OBJECT(
        'type', type,
        'district', district,
        'price_range', CASE 
            WHEN price < 10000000 THEN 'budget'
            WHEN price < 25000000 THEN 'mid_range'
            ELSE 'premium'
        END,
        'area_category', CASE 
            WHEN area_sqft < 1000 THEN 'small'
            WHEN area_sqft < 2500 THEN 'medium'
            ELSE 'large'
        END
    )
WHERE availability_status IS NULL;

-- 13. Create view for available properties with enhanced information
CREATE OR REPLACE VIEW available_properties_view AS
SELECT 
    p.*,
    u.name as seller_name,
    u.kyc_status as seller_kyc_status,
    COUNT(DISTINCT f.id) as total_favorites,
    COUNT(DISTINCT o.id) as total_offers,
    COUNT(DISTINCT pv.id) as total_views,
    AVG(o.offer_amount) as avg_offer_amount,
    MAX(o.created_at) as last_offer_date
FROM properties p
JOIN users u ON p.seller_id = u.id
LEFT JOIN favorites f ON p.id = f.property_id
LEFT JOIN offers o ON p.id = o.property_id AND o.status IN ('pending', 'countered')
LEFT JOIN property_views pv ON p.id = pv.property_id
WHERE p.availability_status = 'available'
GROUP BY p.id
ORDER BY p.created_at DESC;

-- 14. Create stored procedure for content-based property recommendations
DELIMITER //

CREATE PROCEDURE GetContentBasedRecommendations(
    IN p_user_id INT,
    IN p_limit INT DEFAULT 10
)
BEGIN
    DECLARE user_property_type VARCHAR(50);
    DECLARE user_preferred_districts TEXT;
    DECLARE user_max_price DECIMAL(15,2);
    DECLARE user_min_area INT;
    
    -- Get user preferences
    SELECT 
        GROUP_CONCAT(DISTINCT preference_value) INTO user_property_type
    FROM user_preferences 
    WHERE user_id = p_user_id AND preference_type = 'property_type';
    
    SELECT 
        GROUP_CONCAT(DISTINCT preference_value) INTO user_preferred_districts
    FROM user_preferences 
    WHERE user_id = p_user_id AND preference_type = 'district';
    
    SELECT 
        CAST(preference_value AS DECIMAL(15,2)) INTO user_max_price
    FROM user_preferences 
    WHERE user_id = p_user_id AND preference_type = 'price_range' AND preference_key = 'max_price'
    LIMIT 1;
    
    SELECT 
        CAST(preference_value AS INT) INTO user_min_area
    FROM user_preferences 
    WHERE user_id = p_user_id AND preference_type = 'area_range' AND preference_key = 'min_area'
    LIMIT 1;
    
    -- Get recommended properties based on content similarity
    SELECT 
        p.*,
        u.name as seller_name,
        u.kyc_status as seller_kyc_status,
        -- Calculate recommendation score
        (
            -- Property type match (30%)
            (CASE WHEN p.type IN (user_property_type) THEN 0.3 ELSE 0 END) +
            -- District match (25%)
            (CASE WHEN FIND_IN_SET(p.district, user_preferred_districts) > 0 THEN 0.25 ELSE 0 END) +
            -- Price match (20%)
            (CASE WHEN p.price <= user_max_price THEN 0.2 ELSE 0 END) +
            -- Area match (15%)
            (CASE WHEN p.area_sqft >= user_min_area THEN 0.15 ELSE 0 END) +
            -- Popularity bonus (10%)
            (CASE WHEN p.view_count > 10 THEN 0.1 ELSE 0 END)
        ) as recommendation_score
    FROM properties p
    JOIN users u ON p.seller_id = u.id
    WHERE p.availability_status = 'available'
    AND p.id NOT IN (
        SELECT property_id FROM property_recommendations 
        WHERE user_id = p_user_id AND expires_at > NOW()
    )
    HAVING recommendation_score > 0.3
    ORDER BY recommendation_score DESC, p.created_at DESC
    LIMIT p_limit;
    
END//

DELIMITER ;

-- 15. Create function to calculate property similarity score
DELIMITER //

CREATE FUNCTION CalculatePropertySimilarity(
    p1_id INT,
    p2_id INT
) RETURNS DECIMAL(5,4)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE similarity_score DECIMAL(5,4) DEFAULT 0.0;
    DECLARE p1_type, p2_type VARCHAR(50);
    DECLARE p1_district, p2_district VARCHAR(100);
    DECLARE p1_price, p2_price BIGINT;
    DECLARE p1_area, p2_area INT;
    
    -- Get property details
    SELECT type, district, price, area_sqft 
    INTO p1_type, p1_district, p1_price, p1_area
    FROM properties WHERE id = p1_id;
    
    SELECT type, district, price, area_sqft 
    INTO p2_type, p2_district, p2_price, p2_area
    FROM properties WHERE id = p2_id;
    
    -- Calculate similarity score
    SET similarity_score = 0.0;
    
    -- Type similarity (30%)
    IF p1_type = p2_type THEN
        SET similarity_score = similarity_score + 0.3;
    END IF;
    
    -- District similarity (25%)
    IF p1_district = p2_district THEN
        SET similarity_score = similarity_score + 0.25;
    END IF;
    
    -- Price similarity (20%) - within 20% range
    IF ABS(p1_price - p2_price) / GREATEST(p1_price, p2_price) <= 0.2 THEN
        SET similarity_score = similarity_score + 0.2;
    END IF;
    
    -- Area similarity (15%) - within 30% range
    IF p1_area IS NOT NULL AND p2_area IS NOT NULL THEN
        IF ABS(p1_area - p2_area) / GREATEST(p1_area, p2_area) <= 0.3 THEN
            SET similarity_score = similarity_score + 0.15;
        END IF;
    END IF;
    
    -- Feature similarity (10%) - based on common features
    -- This would require more complex logic with the features table
    
    RETURN similarity_score;
END//

DELIMITER ;

-- 16. Insert sample data for testing
INSERT IGNORE INTO property_availability_history (property_id, old_status, new_status, changed_by, change_reason) VALUES
(1, 'available', 'available', 1, 'Property listed'),
(2, 'available', 'available', 1, 'Property listed');

-- 17. Create view for property statistics
CREATE OR REPLACE VIEW property_statistics_view AS
SELECT 
    p.id,
    p.title,
    p.type,
    p.district,
    p.availability_status,
    p.view_count,
    p.favorite_count,
    COUNT(DISTINCT o.id) as offer_count,
    AVG(o.offer_amount) as avg_offer_amount,
    p.created_at,
    DATEDIFF(NOW(), p.created_at) as days_listed
FROM properties p
LEFT JOIN offers o ON p.id = o.property_id
GROUP BY p.id
ORDER BY p.created_at DESC;

-- 18. Create view for market trends
CREATE OR REPLACE VIEW market_trends_view AS
SELECT 
    p.type,
    p.district,
    COUNT(*) as total_properties,
    AVG(p.price) as avg_price,
    MIN(p.price) as min_price,
    MAX(p.price) as max_price,
    AVG(p.area_sqft) as avg_area,
    SUM(CASE WHEN p.availability_status = 'available' THEN 1 ELSE 0 END) as available_count,
    SUM(CASE WHEN p.availability_status = 'sold' THEN 1 ELSE 0 END) as sold_count,
    AVG(p.view_count) as avg_views,
    AVG(p.favorite_count) as avg_favorites
FROM properties p
GROUP BY p.type, p.district
ORDER BY total_properties DESC;

-- 19. Create indexes for recommendation queries
CREATE INDEX IF NOT EXISTS idx_recommendations_user_score ON property_recommendations(user_id, recommendation_score DESC);
CREATE INDEX IF NOT EXISTS idx_recommendations_type_score ON property_recommendations(recommendation_type, recommendation_score DESC);
CREATE INDEX IF NOT EXISTS idx_features_name_value ON property_features(feature_name, feature_value);
CREATE INDEX IF NOT EXISTS idx_features_property ON property_features(property_id, feature_type);
CREATE INDEX IF NOT EXISTS idx_user_preferences_user_type ON user_preferences(user_id, preference_type);
CREATE INDEX IF NOT EXISTS idx_property_views_property_date ON property_views(property_id, viewed_at DESC);

-- 20. Final update to ensure all properties have proper status
UPDATE properties SET 
    availability_status = 'available' 
WHERE availability_status IS NULL OR availability_status = '';

-- Display summary
SELECT 
    'Property Availability System Setup Complete' as status,
    COUNT(*) as total_properties,
    SUM(CASE WHEN availability_status = 'available' THEN 1 ELSE 0 END) as available_properties,
    SUM(CASE WHEN availability_status = 'sold' THEN 1 ELSE 0 END) as sold_properties,
    SUM(CASE WHEN availability_status = 'under_offer' THEN 1 ELSE 0 END) as under_offer_properties
FROM properties;
