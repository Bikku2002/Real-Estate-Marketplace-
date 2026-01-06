-- Fix for GetContentBasedRecommendations Stored Procedure
-- Drop the existing procedure if it exists
DROP PROCEDURE IF EXISTS GetContentBasedRecommendations;

-- Create the corrected procedure
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

-- Test the procedure
-- CALL GetContentBasedRecommendations(2, 5);
