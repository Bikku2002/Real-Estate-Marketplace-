<?php
declare(strict_types=1);

/**
 * Setup Script for Advanced Features
 * This will ensure all required database changes are applied
 */

echo "🚀 Setting up Advanced Features for RealEstate...\n\n";

// Step 1: Check if database update script exists and run it
echo "📊 Step 1: Running database updates...\n";
if (file_exists('update_advanced_features.php')) {
    echo "✅ Found update script, running it...\n";
    include 'update_advanced_features.php';
} else {
    echo "❌ Database update script not found!\n";
    echo "Please ensure update_advanced_features.php exists.\n";
}

echo "\n🔧 Step 2: Testing system components...\n";

// Test database connection
try {
    require_once 'config/db.php';
    $pdo = get_pdo();
    echo "✅ Database connection successful\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit;
}

// Test if required tables exist
$requiredTables = [
    'properties',
    'users',
    'property_valuations',
    'market_trends',
    'comparable_sales',
    'user_preferences'
];

echo "\n🗄️ Checking required tables...\n";
foreach ($requiredTables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists\n";
        } else {
            echo "❌ Table '$table' missing - creating it...\n";
            // Try to create the table using the SQL script
            $sqlFile = "database/advanced_features.sql";
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                // Extract the CREATE TABLE statement for this table
                if (preg_match("/CREATE TABLE IF NOT EXISTS $table\s*\([^)]+\)/s", $sql, $matches)) {
                    try {
                        $pdo->exec($matches[0]);
                        echo "✅ Table '$table' created successfully\n";
                    } catch (Exception $e) {
                        echo "❌ Failed to create table '$table': " . $e->getMessage() . "\n";
                    }
                }
            }
        }
    } catch (Exception $e) {
        echo "❌ Error checking table '$table': " . $e->getMessage() . "\n";
    }
}

// Test if new columns exist in properties table
echo "\n📊 Checking new columns in properties table...\n";
$newColumns = [
    'latitude',
    'longitude',
    'features',
    'size',
    'price_per_sqft',
    'last_valuation_at',
    'valuation_confidence'
];

try {
    $stmt = $pdo->query("DESCRIBE properties");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($newColumns as $column) {
        if (in_array($column, $existingColumns)) {
            echo "✅ Column '$column' exists\n";
        } else {
            echo "❌ Column '$column' missing - adding it...\n";
            try {
                $sql = "ALTER TABLE properties ADD COLUMN $column ";
                switch ($column) {
                    case 'latitude':
                        $sql .= "DECIMAL(10, 8) DEFAULT NULL";
                        break;
                    case 'longitude':
                        $sql .= "DECIMAL(11, 8) DEFAULT NULL";
                        break;
                    case 'features':
                        $sql .= "JSON DEFAULT NULL";
                        break;
                    case 'size':
                        $sql .= "DECIMAL(10, 2) DEFAULT NULL";
                        break;
                    case 'price_per_sqft':
                        $sql .= "DECIMAL(12, 2) DEFAULT NULL";
                        break;
                    case 'last_valuation_at':
                        $sql .= "TIMESTAMP NULL";
                        break;
                    case 'valuation_confidence':
                        $sql .= "DECIMAL(5, 4) DEFAULT NULL";
                        break;
                }
                $pdo->exec($sql);
                echo "✅ Column '$column' added successfully\n";
            } catch (Exception $e) {
                echo "❌ Failed to add column '$column': " . $e->getMessage() . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "❌ Error checking properties table: " . $e->getMessage() . "\n";
}

// Test class instantiation
echo "\n🔧 Testing class instantiation...\n";
try {
    require_once 'config/pricing_algorithms.php';
    
    $valuation = new PropertyValuation();
    echo "✅ PropertyValuation class instantiated successfully\n";
    
    $marketAnalysis = new MarketAnalysis();
    echo "✅ MarketAnalysis class instantiated successfully\n";
} catch (Exception $e) {
    echo "❌ Class instantiation failed: " . $e->getMessage() . "\n";
}

// Test language functions
echo "\n🌐 Testing language functions...\n";
try {
    require_once 'config/languages.php';
    
    $currentLang = get_current_language();
    echo "✅ Current language: $currentLang\n";
    
    $translation = __('home');
    echo "✅ Translation test: home = $translation\n";
} catch (Exception $e) {
    echo "❌ Language functions failed: " . $e->getMessage() . "\n";
}

echo "\n🎯 Setup completed!\n";
echo "Your advanced features should now be working properly.\n";
echo "If you still see errors, please run the test script: test_advanced_features.php\n";
?>
