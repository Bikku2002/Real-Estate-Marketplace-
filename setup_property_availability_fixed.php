<?php
declare(strict_types=1);

/**
 * Property Availability System Setup Script - FIXED VERSION
 * 
 * This script sets up the complete property availability management system
 * including database tables, triggers, and sample data in the correct order.
 */

echo "🚀 Setting up Property Availability System for RealEstate...\n\n";

// Database connection
try {
    require_once __DIR__ . '/config/db.php';
    $pdo = get_pdo();
    echo "✅ Database connection established\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 1: Create tables first (without data)
echo "\n📋 Step 1: Creating database tables...\n";

$createTables = [
    // 1. Create property availability history table
    "CREATE TABLE IF NOT EXISTS property_availability_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        property_id INT NOT NULL,
        old_status ENUM('available', 'under_offer', 'sold', 'withdrawn', 'expired') NOT NULL,
        new_status ENUM('available', 'under_offer', 'sold', 'withdrawn', 'expired') NOT NULL,
        changed_by INT NOT NULL,
        change_reason VARCHAR(255) NULL,
        changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (property_id, changed_at),
        INDEX (new_status, changed_at)
    )",
    
    // 2. Create property features table
    "CREATE TABLE IF NOT EXISTS property_features (
        id INT AUTO_INCREMENT PRIMARY KEY,
        property_id INT NOT NULL,
        feature_name VARCHAR(100) NOT NULL,
        feature_value VARCHAR(255) NULL,
        feature_type ENUM('amenity', 'infrastructure', 'location', 'property_type', 'custom') DEFAULT 'amenity',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_property_feature (property_id, feature_name),
        INDEX (feature_name, feature_value),
        INDEX (feature_type, feature_name)
    )",
    
    // 3. Create user preferences table
    "CREATE TABLE IF NOT EXISTS user_preferences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        preference_type ENUM('property_type', 'district', 'price_range', 'area_range', 'features', 'amenities') NOT NULL,
        preference_key VARCHAR(100) NOT NULL,
        preference_value VARCHAR(255) NOT NULL,
        preference_weight DECIMAL(3,2) DEFAULT 1.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_preference (user_id, preference_type, preference_key),
        INDEX (user_id, preference_type),
        INDEX (preference_key, preference_value)
    )",
    
    // 4. Create property view tracking table
    "CREATE TABLE IF NOT EXISTS property_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        property_id INT NOT NULL,
        user_id INT NULL,
        viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        view_duration INT NULL,
        source_page VARCHAR(100) NULL,
        user_agent TEXT NULL,
        ip_address VARCHAR(45) NULL,
        INDEX (property_id, viewed_at),
        INDEX (user_id, viewed_at),
        INDEX (viewed_at)
    )",
    
    // 5. Create property recommendations table
    "CREATE TABLE IF NOT EXISTS property_recommendations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        property_id INT NOT NULL,
        recommendation_score DECIMAL(5,4) NOT NULL,
        recommendation_reason VARCHAR(255) NULL,
        recommendation_type ENUM('content_based', 'collaborative', 'popularity', 'location', 'price') DEFAULT 'content_based',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NULL,
        UNIQUE KEY unique_user_property_recommendation (user_id, property_id),
        INDEX (user_id, recommendation_score DESC),
        INDEX (recommendation_type, recommendation_score DESC),
        INDEX (expires_at)
    )",
    
    // 6. Create property search history table
    "CREATE TABLE IF NOT EXISTS property_search_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        search_query TEXT NOT NULL,
        search_filters JSON NULL,
        results_count INT DEFAULT 0,
        clicked_properties JSON NULL,
        search_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id, search_date DESC),
        INDEX (search_date)
    )"
];

$tableSuccessCount = 0;
$tableErrorCount = 0;

foreach ($createTables as $sql) {
    try {
        $pdo->exec($sql);
        $tableSuccessCount++;
        echo "  ✅ Table created successfully\n";
    } catch (Exception $e) {
        $tableErrorCount++;
        echo "  ❌ Error creating table: " . $e->getMessage() . "\n";
    }
}

echo "\n📊 Table Creation Summary: {$tableSuccessCount} successful, {$tableErrorCount} failed\n";

// Step 2: Add columns to existing properties table
echo "\n📋 Step 2: Enhancing properties table...\n";

$alterProperties = [
    "ALTER TABLE properties ADD COLUMN IF NOT EXISTS availability_status ENUM('available', 'under_offer', 'sold', 'withdrawn', 'expired') DEFAULT 'available'",
    "ALTER TABLE properties ADD COLUMN IF NOT EXISTS available_from TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    "ALTER TABLE properties ADD COLUMN IF NOT EXISTS available_until TIMESTAMP NULL",
    "ALTER TABLE properties ADD COLUMN IF NOT EXISTS last_status_change TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    "ALTER TABLE properties ADD COLUMN IF NOT EXISTS status_change_reason VARCHAR(255) NULL",
    "ALTER TABLE properties ADD COLUMN IF NOT EXISTS property_features JSON NULL",
    "ALTER TABLE properties ADD COLUMN IF NOT EXISTS property_tags VARCHAR(500) NULL",
    "ALTER TABLE properties ADD COLUMN IF NOT EXISTS view_count INT DEFAULT 0",
    "ALTER TABLE properties ADD COLUMN IF NOT EXISTS favorite_count INT DEFAULT 0"
];

$alterSuccessCount = 0;
$alterErrorCount = 0;

foreach ($alterProperties as $sql) {
    try {
        $pdo->exec($sql);
        $alterSuccessCount++;
        echo "  ✅ Column added successfully\n";
    } catch (Exception $e) {
        $alterErrorCount++;
        echo "  ❌ Error adding column: " . $e->getMessage() . "\n";
    }
}

echo "\n📊 Properties Table Enhancement Summary: {$alterSuccessCount} successful, {$alterErrorCount} failed\n";

// Step 3: Create indexes
echo "\n📋 Step 3: Creating database indexes...\n";

$createIndexes = [
    "CREATE INDEX IF NOT EXISTS idx_properties_availability_status ON properties(availability_status)",
    "CREATE INDEX IF NOT EXISTS idx_properties_available_from ON properties(available_from)",
    "CREATE INDEX IF NOT EXISTS idx_properties_view_count ON properties(view_count DESC)",
    "CREATE INDEX IF NOT EXISTS idx_properties_favorite_count ON properties(favorite_count DESC)",
    "CREATE INDEX IF NOT EXISTS idx_properties_features ON properties(property_features)",
    "CREATE INDEX IF NOT EXISTS idx_properties_tags ON properties(property_tags)"
];

$indexSuccessCount = 0;
$indexErrorCount = 0;

foreach ($createIndexes as $sql) {
    try {
        $pdo->exec($sql);
        $indexSuccessCount++;
        echo "  ✅ Index created successfully\n";
    } catch (Exception $e) {
        $indexErrorCount++;
        echo "  ❌ Error creating index: " . $e->getMessage() . "\n";
    }
}

echo "\n📊 Index Creation Summary: {$indexSuccessCount} successful, {$indexErrorCount} failed\n";

// Step 4: Insert sample data
echo "\n📋 Step 4: Inserting sample data...\n";

// First, check if we have any properties to work with
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM properties");
    $propertyCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "  ℹ️  Found {$propertyCount} existing properties\n";
} catch (Exception $e) {
    echo "  ❌ Error checking properties: " . $e->getMessage() . "\n";
    $propertyCount = 0;
}

if ($propertyCount > 0) {
    // Update existing properties to have proper availability status
    try {
        $pdo->exec("UPDATE properties SET 
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
        WHERE availability_status IS NULL");
        echo "  ✅ Updated existing properties with availability status\n";
    } catch (Exception $e) {
        echo "  ❌ Error updating properties: " . $e->getMessage() . "\n";
    }
    
    // Insert sample property features for first 2 properties
    try {
        $pdo->exec("INSERT IGNORE INTO property_features (property_id, feature_name, feature_value, feature_type) VALUES
        (1, 'road_access', 'yes', 'infrastructure'),
        (1, 'water_supply', 'yes', 'infrastructure'),
        (1, 'electricity', 'yes', 'infrastructure'),
        (1, 'security', 'yes', 'amenity'),
        (1, 'parking', 'yes', 'amenity'),
        (1, 'garden', 'yes', 'amenity'),
        (1, 'near_school', 'yes', 'location'),
        (1, 'near_hospital', 'yes', 'location'),
        (1, 'near_market', 'yes', 'location'),
        (2, 'road_access', 'yes', 'infrastructure'),
        (2, 'water_supply', 'yes', 'infrastructure'),
        (2, 'electricity', 'yes', 'infrastructure'),
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
        (2, 'terrace', 'yes', 'amenity')");
        echo "  ✅ Inserted sample property features\n";
    } catch (Exception $e) {
        echo "  ❌ Error inserting property features: " . $e->getMessage() . "\n";
    }
    
    // Insert sample user preferences (only if user 2 exists)
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE id = 2");
        $user2Exists = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        
        if ($user2Exists) {
            $pdo->exec("INSERT IGNORE INTO user_preferences (user_id, preference_type, preference_key, preference_value, preference_weight) VALUES
            (2, 'property_type', 'house', 'house', 0.9),
            (2, 'district', 'Kathmandu', 'Kathmandu', 0.8),
            (2, 'district', 'Lalitpur', 'Lalitpur', 0.7),
            (2, 'price_range', 'max_price', '30000000', 0.9),
            (2, 'area_range', 'min_area', '1500', 0.8),
            (2, 'features', 'parking', 'yes', 0.9),
            (2, 'features', 'garden', 'yes', 0.7),
            (2, 'features', 'security', 'yes', 0.8),
            (2, 'amenities', 'near_school', 'yes', 0.6),
            (2, 'amenities', 'near_market', 'yes', 0.7)");
            echo "  ✅ Inserted sample user preferences\n";
        } else {
            echo "  ℹ️  User ID 2 not found, skipping user preferences\n";
        }
    } catch (Exception $e) {
        echo "  ❌ Error inserting user preferences: " . $e->getMessage() . "\n";
    }
    
    // Insert sample availability history
    try {
        $pdo->exec("INSERT IGNORE INTO property_availability_history (property_id, old_status, new_status, changed_by, change_reason) VALUES
        (1, 'available', 'available', 1, 'Property listed'),
        (2, 'available', 'available', 1, 'Property listed')");
        echo "  ✅ Inserted sample availability history\n";
    } catch (Exception $e) {
        echo "  ❌ Error inserting availability history: " . $e->getMessage() . "\n";
    }
} else {
    echo "  ℹ️  No existing properties found, skipping sample data insertion\n";
}

// Step 5: Create database views
echo "\n📋 Step 5: Creating database views...\n";

$createViews = [
    // View for available properties
    "CREATE OR REPLACE VIEW available_properties_view AS
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
    ORDER BY p.created_at DESC",
    
    // View for property statistics
    "CREATE OR REPLACE VIEW property_statistics_view AS
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
    ORDER BY p.created_at DESC",
    
    // View for market trends
    "CREATE OR REPLACE VIEW market_trends_view AS
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
    ORDER BY total_properties DESC"
];

$viewSuccessCount = 0;
$viewErrorCount = 0;

foreach ($createViews as $sql) {
    try {
        $pdo->exec($sql);
        $viewSuccessCount++;
        echo "  ✅ View created successfully\n";
    } catch (Exception $e) {
        $viewErrorCount++;
        echo "  ❌ Error creating view: " . $e->getMessage() . "\n";
    }
}

echo "\n📊 View Creation Summary: {$viewSuccessCount} successful, {$viewErrorCount} failed\n";

// Final summary
echo "\n🎉 Property Availability System Setup Complete!\n";
echo "\n📋 Summary:\n";
echo "  • Tables: {$tableSuccessCount}/" . count($createTables) . " created successfully\n";
echo "  • Properties Enhanced: {$alterSuccessCount}/" . count($alterProperties) . " columns added\n";
echo "  • Indexes: {$indexSuccessCount}/" . count($createIndexes) . " created successfully\n";
echo "  • Views: {$viewSuccessCount}/" . count($createViews) . " created successfully\n";

if ($tableErrorCount === 0 && $alterErrorCount === 0) {
    echo "\n✅ All critical components were created successfully!\n";
    echo "\n🔧 Next steps:\n";
    echo "  1. Test the buyer dashboard with recommendations\n";
    echo "  2. Access admin property availability management\n";
    echo "  3. Monitor property status changes\n";
    echo "  4. Test content-based filtering\n";
} else {
    echo "\n⚠️  Setup completed with some errors. Please review the error messages above.\n";
    echo "Some features may not work correctly.\n";
}

echo "\n🏠 RealEstate Property Availability System is ready!\n";
