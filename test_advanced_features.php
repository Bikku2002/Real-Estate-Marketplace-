<?php
declare(strict_types=1);

/**
 * Test Script for Advanced Features
 * Run this to check if everything is working properly
 */

echo "🧪 Testing Advanced Features...\n\n";

// Test 1: Check if required files exist
echo "📁 Checking required files...\n";
$requiredFiles = [
    'config/db.php',
    'config/user_auth.php',
    'config/languages.php',
    'config/pricing_algorithms.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists\n";
    } else {
        echo "❌ $file missing\n";
    }
}

// Test 2: Check database connection
echo "\n🔌 Testing database connection...\n";
try {
    require_once 'config/db.php';
    $pdo = get_pdo();
    echo "✅ Database connection successful\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit;
}

// Test 3: Check if required tables exist
echo "\n🗄️ Checking required tables...\n";
$requiredTables = [
    'properties',
    'users',
    'property_valuations',
    'market_trends',
    'comparable_sales',
    'user_preferences'
];

foreach ($requiredTables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists\n";
        } else {
            echo "❌ Table '$table' missing\n";
        }
    } catch (Exception $e) {
        echo "❌ Error checking table '$table': " . $e->getMessage() . "\n";
    }
}

// Test 4: Check if new columns exist in properties table
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
            echo "❌ Column '$column' missing\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error checking properties table: " . $e->getMessage() . "\n";
}

// Test 5: Test class instantiation
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

// Test 6: Test language functions
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

echo "\n🎯 Test completed!\n";
echo "If you see any ❌ errors above, those need to be fixed.\n";
echo "If all tests pass with ✅, your advanced features are ready!\n";
?>
