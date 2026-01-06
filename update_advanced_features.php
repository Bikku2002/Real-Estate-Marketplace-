<?php
declare(strict_types=1);

/**
 * Advanced Features Database Update Script
 * Run this to add new tables and columns for advanced pricing algorithms
 */

require_once __DIR__ . '/config/db.php';

echo "🚀 Starting Advanced Features Database Update...\n\n";

try {
    $pdo = get_pdo();
    
    // 1. Add new columns to properties table
    echo "📊 Adding new columns to properties table...\n";
    
    $alterQueries = [
        "ALTER TABLE properties ADD COLUMN IF NOT EXISTS latitude DECIMAL(10, 8) DEFAULT NULL",
        "ALTER TABLE properties ADD COLUMN IF NOT EXISTS longitude DECIMAL(11, 8) DEFAULT NULL",
        "ALTER TABLE properties ADD COLUMN IF NOT EXISTS features JSON DEFAULT NULL",
        "ALTER TABLE properties ADD COLUMN IF NOT EXISTS size DECIMAL(10, 2) DEFAULT NULL",
        "ALTER TABLE properties ADD COLUMN IF NOT EXISTS price_per_sqft DECIMAL(12, 2) DEFAULT NULL",
        "ALTER TABLE properties ADD COLUMN IF NOT EXISTS last_valuation_at TIMESTAMP NULL",
        "ALTER TABLE properties ADD COLUMN IF NOT EXISTS valuation_confidence DECIMAL(5, 4) DEFAULT NULL"
    ];
    
    foreach ($alterQueries as $query) {
        $pdo->exec($query);
        echo "✅ " . substr($query, 0, 50) . "...\n";
    }
    
    // 2. Create property_valuations table
    echo "\n🏠 Creating property_valuations table...\n";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS property_valuations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            property_id INT NOT NULL,
            gwr_price DECIMAL(15, 2) NOT NULL,
            gwr_confidence DECIMAL(5, 4) NOT NULL,
            knn_price DECIMAL(15, 2) NOT NULL,
            knn_confidence DECIMAL(5, 4) NOT NULL,
            cosine_price DECIMAL(15, 2) NOT NULL,
            cosine_confidence DECIMAL(5, 4) NOT NULL,
            ensemble_price DECIMAL(15, 2) NOT NULL,
            ensemble_confidence DECIMAL(5, 4) NOT NULL,
            nearby_properties_count INT DEFAULT 0,
            neighbors_used INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
        )
    ");
    echo "✅ property_valuations table created\n";
    
    // 3. Create market_trends table
    echo "\n📈 Creating market_trends table...\n";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS market_trends (
            id INT AUTO_INCREMENT PRIMARY KEY,
            district VARCHAR(100) NOT NULL,
            property_type ENUM('land', 'house') NOT NULL,
            month_year VARCHAR(7) NOT NULL,
            avg_price DECIMAL(15, 2) NOT NULL,
            min_price DECIMAL(15, 2) NOT NULL,
            max_price DECIMAL(15, 2) NOT NULL,
            sales_count INT NOT NULL,
            price_change_percent DECIMAL(5, 2) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_trend (district, property_type, month_year)
        )
    ");
    echo "✅ market_trends table created\n";
    
    // 4. Create comparable_sales table
    echo "\n🏘️ Creating comparable_sales table...\n";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS comparable_sales (
            id INT AUTO_INCREMENT PRIMARY KEY,
            property_id INT NOT NULL,
            comparable_property_id INT NOT NULL,
            similarity_score DECIMAL(5, 4) NOT NULL,
            distance_km DECIMAL(8, 4) DEFAULT NULL,
            price_difference_percent DECIMAL(8, 4) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
            FOREIGN KEY (comparable_property_id) REFERENCES properties(id) ON DELETE CASCADE
        )
    ");
    echo "✅ comparable_sales table created\n";
    
    // 5. Create user_preferences table
    echo "\n👤 Creating user_preferences table...\n";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_preferences (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            language VARCHAR(2) DEFAULT 'en',
            currency VARCHAR(3) DEFAULT 'NPR',
            measurement_unit ENUM('sqft', 'sqm', 'ropani') DEFAULT 'sqft',
            notifications_enabled BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_pref (user_id)
        )
    ");
    echo "✅ user_preferences table created\n";
    
    // 6. Create valuation_requests table
    echo "\n🔍 Creating valuation_requests table...\n";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS valuation_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            property_type ENUM('land', 'house') NOT NULL,
            size DECIMAL(10, 2) NOT NULL,
            district VARCHAR(100) NOT NULL,
            municipality VARCHAR(100) DEFAULT NULL,
            features JSON DEFAULT NULL,
            estimated_price DECIMAL(15, 2) DEFAULT NULL,
            confidence_score DECIMAL(5, 4) DEFAULT NULL,
            status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "✅ valuation_requests table created\n";
    
    // 7. Create indexes for better performance
    echo "\n⚡ Creating performance indexes...\n";
    
    $indexQueries = [
        "CREATE INDEX IF NOT EXISTS idx_properties_location ON properties(latitude, longitude)",
        "CREATE INDEX IF NOT EXISTS idx_properties_district_type ON properties(district, type)",
        "CREATE INDEX IF NOT EXISTS idx_properties_price ON properties(price)",
        "CREATE INDEX IF NOT EXISTS idx_properties_created ON properties(created_at)",
        "CREATE INDEX IF NOT EXISTS idx_valuations_property ON property_valuations(property_id)",
        "CREATE INDEX IF NOT EXISTS idx_valuations_created ON property_valuations(created_at)",
        "CREATE INDEX IF NOT EXISTS idx_trends_district_type ON market_trends(district, property_type)",
        "CREATE INDEX IF NOT EXISTS idx_trends_month ON market_trends(month_year)",
        "CREATE INDEX IF NOT EXISTS idx_comparable_property ON comparable_sales(property_id)",
        "CREATE INDEX IF NOT EXISTS idx_comparable_similarity ON comparable_sales(similarity_score)"
    ];
    
    foreach ($indexQueries as $query) {
        $pdo->exec($query);
        echo "✅ Index created\n";
    }
    
    // 8. Insert sample market trend data
    echo "\n📊 Inserting sample market trend data...\n";
    
    $sampleTrends = [
        ['Kathmandu', 'land', '2024-01', 15000000, 8000000, 25000000, 45],
        ['Kathmandu', 'land', '2024-02', 15500000, 8200000, 26000000, 52],
        ['Kathmandu', 'land', '2024-03', 15800000, 8500000, 26500000, 48],
        ['Kathmandu', 'house', '2024-01', 25000000, 15000000, 40000000, 28],
        ['Kathmandu', 'house', '2024-02', 25500000, 15500000, 41000000, 32],
        ['Kathmandu', 'house', '2024-03', 26000000, 16000000, 42000000, 30],
        ['Lalitpur', 'land', '2024-01', 14000000, 7500000, 23000000, 38],
        ['Lalitpur', 'land', '2024-02', 14200000, 7800000, 23500000, 42],
        ['Lalitpur', 'land', '2024-03', 14500000, 8000000, 24000000, 40]
    ];
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO market_trends (district, property_type, month_year, avg_price, min_price, max_price, sales_count) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($sampleTrends as $trend) {
        $stmt->execute($trend);
    }
    echo "✅ Sample market trends inserted\n";
    
    // 9. Update existing properties with sample coordinates and size
    echo "\n📍 Updating properties with sample coordinates...\n";
    
    $updateCount = $pdo->exec("
        UPDATE properties 
        SET latitude = 27.7172 + (RAND() - 0.5) * 0.1,
            longitude = 85.3240 + (RAND() - 0.5) * 0.1,
            size = price / (5000 + RAND() * 3000),
            features = JSON_ARRAY('road_access', 'electricity', 'water')
        WHERE latitude IS NULL 
        LIMIT 100
    ");
    echo "✅ Updated $updateCount properties with coordinates\n";
    
    // 10. Calculate price per sqft
    echo "\n💰 Calculating price per sqft...\n";
    
    $priceUpdateCount = $pdo->exec("
        UPDATE properties 
        SET price_per_sqft = price / size 
        WHERE size > 0 AND price > 0
    ");
    echo "✅ Updated $priceUpdateCount properties with price per sqft\n";
    
    // 11. Insert sample valuation data
    echo "\n🧠 Inserting sample valuation data...\n";
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO property_valuations 
        (property_id, gwr_price, gwr_confidence, knn_price, knn_confidence, cosine_price, cosine_confidence, ensemble_price, ensemble_confidence, nearby_properties_count, neighbors_used)
        SELECT 
            p.id,
            p.price * (0.9 + RAND() * 0.2) as gwr_price,
            0.7 + RAND() * 0.25 as gwr_confidence,
            p.price * (0.85 + RAND() * 0.3) as knn_price,
            0.65 + RAND() * 0.3 as knn_confidence,
            p.price * (0.8 + RAND() * 0.4) as cosine_price,
            0.6 + RAND() * 0.35 as cosine_confidence,
            p.price * (0.88 + RAND() * 0.24) as ensemble_price,
            0.75 + RAND() * 0.2 as ensemble_confidence,
            15 + FLOOR(RAND() * 35) as nearby_properties_count,
            3 + FLOOR(RAND() * 7) as neighbors_used
        FROM properties p
        WHERE p.price > 0
        LIMIT 50
    ");
    
    $stmt->execute();
    echo "✅ Sample valuation data inserted\n";
    
    // 12. Update properties with valuation data
    echo "\n🔄 Updating properties with valuation data...\n";
    
    $valuationUpdateCount = $pdo->exec("
        UPDATE properties p
        JOIN property_valuations v ON p.id = v.property_id
        SET p.last_valuation_at = v.created_at,
            p.valuation_confidence = v.ensemble_confidence
    ");
    echo "✅ Updated $valuationUpdateCount properties with valuation data\n";
    
    echo "\n🎉 Advanced Features Database Update Completed Successfully!\n";
    echo "✨ New features available:\n";
    echo "   - Advanced pricing algorithms (GWR, KNN, Cosine)\n";
    echo "   - Market trend analysis\n";
    echo "   - Comparable sales analysis\n";
    echo "   - Language preferences\n";
    echo "   - Property valuation requests\n";
    echo "\n🚀 You can now use the advanced valuation system!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error during database update: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
