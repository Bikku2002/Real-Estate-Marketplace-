<?php
declare(strict_types=1);

echo "🔍 Comprehensive System Test for RealEstate...\n\n";

// Test 1: Basic PHP and file structure
echo "📁 Testing file structure and PHP...\n";
$requiredFiles = [
    'config/db.php',
    'config/user_auth.php',
    'config/languages.php',
    'config/pricing_algorithms.php',
    'public/index.php',
    'public/components/language-switcher.php',
    'public/valuation.php',
    'public/profile.php',
    'public/login.php',
    'public/register.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists\n";
    } else {
        echo "❌ $file missing\n";
    }
}

// Test 2: Database connection and tables
echo "\n🗄️ Testing database...\n";
try {
    require_once 'config/db.php';
    $pdo = get_pdo();
    echo "✅ Database connection successful\n";
    
    // Check required tables
    $requiredTables = [
        'users',
        'properties',
        'offers',
        'messages',
        'property_valuations',
        'market_trends',
        'comparable_sales',
        'user_preferences',
        'profile_update_requests',
        'password_reset_requests'
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
    
    // Check properties table columns
    echo "\n📊 Checking properties table structure...\n";
    try {
        $stmt = $pdo->query("DESCRIBE properties");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredColumns = [
            'id', 'title', 'type', 'district', 'municipality', 'price',
            'description', 'cover_image', 'created_at', 'user_id',
            'latitude', 'longitude', 'features', 'size', 'price_per_sqft',
            'last_valuation_at', 'valuation_confidence'
        ];
        
        foreach ($requiredColumns as $column) {
            if (in_array($column, $columns)) {
                echo "✅ Column '$column' exists\n";
            } else {
                echo "❌ Column '$column' missing\n";
            }
        }
    } catch (Exception $e) {
        echo "❌ Error checking properties table: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit;
}

// Test 3: Language system
echo "\n🌐 Testing language system...\n";
try {
    require_once 'config/languages.php';
    echo "✅ languages.php loaded\n";
    
    $currentLang = get_current_language();
    echo "✅ Current language: $currentLang\n";
    
    $translation = __('home');
    echo "✅ Translation function works: home = $translation\n";
    
    // Test all required translations
    $requiredTranslations = [
        'home', 'contact', 'profile', 'logout', 'login', 'register',
        'estimated_value', 'property', 'land', 'house', 'price'
    ];
    
    foreach ($requiredTranslations as $key) {
        try {
            $trans = __($key);
            if (!empty($trans)) {
                echo "✅ Translation '$key' = '$trans'\n";
            } else {
                echo "❌ Translation '$key' is empty\n";
            }
        } catch (Exception $e) {
            echo "❌ Translation '$key' failed: " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Language system failed: " . $e->getMessage() . "\n";
}

// Test 4: User authentication
echo "\n👤 Testing user authentication...\n";
try {
    require_once 'config/user_auth.php';
    echo "✅ user_auth.php loaded\n";
    
    $isLoggedIn = is_user_logged_in();
    echo "✅ User login check: " . ($isLoggedIn ? 'Logged in' : 'Not logged in') . "\n";
    
    if ($isLoggedIn) {
        $user = get_logged_in_user();
        if ($user) {
            echo "✅ get_logged_in_user() works: " . $user['name'] . "\n";
        } else {
            echo "❌ get_logged_in_user() returned null\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ User auth failed: " . $e->getMessage() . "\n";
}

// Test 5: Pricing algorithms
echo "\n🏠 Testing pricing algorithms...\n";
try {
    require_once 'config/pricing_algorithms.php';
    echo "✅ pricing_algorithms.php loaded\n";
    
    $valuation = new PropertyValuation();
    echo "✅ PropertyValuation class instantiated\n";
    
    $marketAnalysis = new MarketAnalysis();
    echo "✅ MarketAnalysis class instantiated\n";
    
    // Test basic functionality
    $testProperty = [
        'type' => 'land',
        'district' => 'Kathmandu',
        'latitude' => 27.7172,
        'longitude' => 85.3240,
        'size' => 1000,
        'features' => ['road_access', 'water_supply']
    ];
    
    try {
        $results = $valuation->estimatePropertyValue($testProperty);
        echo "✅ Valuation algorithm works\n";
    } catch (Exception $e) {
        echo "❌ Valuation algorithm failed: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Pricing algorithms failed: " . $e->getMessage() . "\n";
}

// Test 6: File permissions and uploads
echo "\n📁 Testing file permissions...\n";
$uploadDirs = [
    'public/uploads',
    'public/uploads/profiles',
    'public/uploads/properties'
];

foreach ($uploadDirs as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "✅ Directory '$dir' is writable\n";
        } else {
            echo "❌ Directory '$dir' is not writable\n";
        }
    } else {
        echo "❌ Directory '$dir' does not exist\n";
    }
}

// Test 7: CSS and JS files
echo "\n🎨 Testing assets...\n";
$assetFiles = [
    'public/assets/css/styles.css',
    'public/assets/css/admin.css',
    'public/assets/css/register.css',
    'public/assets/css/profile.css',
    'public/assets/js/app.js'
];

foreach ($assetFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists\n";
    } else {
        echo "❌ $file missing\n";
    }
}

echo "\n🎯 Comprehensive test completed!\n";
echo "If you see any ❌ errors above, those need to be fixed.\n";
echo "If all tests pass with ✅, your system is ready!\n";
?>
