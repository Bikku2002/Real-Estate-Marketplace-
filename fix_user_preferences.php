<?php
declare(strict_types=1);

/**
 * Fix User Preferences Table Script
 * 
 * This script fixes the user_preferences table structure issue.
 */

echo "🔧 Fixing User Preferences Table...\n\n";

// Database connection
try {
    require_once __DIR__ . '/config/db.php';
    $pdo = get_pdo();
    echo "✅ Database connection established\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Drop and recreate the user_preferences table with correct structure
echo "\n📋 Recreating user_preferences table...\n";

try {
    // Drop the existing table
    $pdo->exec("DROP TABLE IF EXISTS user_preferences");
    echo "  ✅ Dropped existing user_preferences table\n";
    
    // Create the table with correct structure
    $createTable = "CREATE TABLE user_preferences (
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
    )";
    
    $pdo->exec($createTable);
    echo "  ✅ Created user_preferences table with correct structure\n";
    
    // Insert sample user preferences (only if user 2 exists)
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE id = 2");
        $user2Exists = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        
        if ($user2Exists) {
            $pdo->exec("INSERT INTO user_preferences (user_id, preference_type, preference_key, preference_value, preference_weight) VALUES
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
    
} catch (Exception $e) {
    echo "  ❌ Error recreating table: " . $e->getMessage() . "\n";
    exit(1);
}

// Verify the table structure
echo "\n📋 Verifying table structure...\n";
try {
    $stmt = $pdo->query("DESCRIBE user_preferences");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "  📊 Table structure:\n";
    foreach ($columns as $column) {
        echo "    • {$column['Field']} - {$column['Type']}\n";
    }
    
    // Check if preference_type column exists and has correct ENUM values
    $preferenceTypeColumn = null;
    foreach ($columns as $column) {
        if ($column['Field'] === 'preference_type') {
            $preferenceTypeColumn = $column;
            break;
        }
    }
    
    if ($preferenceTypeColumn) {
        echo "  ✅ preference_type column exists with type: {$preferenceTypeColumn['Type']}\n";
    } else {
        echo "  ❌ preference_type column is missing!\n";
    }
    
} catch (Exception $e) {
    echo "  ❌ Error verifying table structure: " . $e->getMessage() . "\n";
}

// Test inserting a sample preference
echo "\n📋 Testing preference insertion...\n";
try {
    $pdo->exec("INSERT INTO user_preferences (user_id, preference_type, preference_key, preference_value, preference_weight) VALUES (1, 'property_type', 'land', 'land', 0.8)");
    echo "  ✅ Test preference insertion successful\n";
    
    // Clean up test data
    $pdo->exec("DELETE FROM user_preferences WHERE user_id = 1 AND preference_type = 'property_type' AND preference_key = 'land'");
    echo "  ✅ Test data cleaned up\n";
    
} catch (Exception $e) {
    echo "  ❌ Test preference insertion failed: " . $e->getMessage() . "\n";
}

echo "\n🎉 User Preferences Table Fix Complete!\n";
echo "\n🔧 The table should now work correctly for:\n";
echo "  • Content-based filtering\n";
echo "  • User preference tracking\n";
echo "  • Property recommendations\n";
echo "\n🏠 RealEstate Property Availability System is ready!\n";
