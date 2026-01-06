<?php
declare(strict_types=1);

echo "🏠 Testing Homepage Loading...\n\n";

// Test 1: Check if all required files can be included
echo "📁 Testing file includes...\n";
try {
    require_once 'config/db.php';
    echo "✅ config/db.php included\n";
    
    require_once 'config/user_auth.php';
    echo "✅ config/user_auth.php included\n";
    
    require_once 'config/languages.php';
    echo "✅ config/languages.php included\n";
    
} catch (Exception $e) {
    echo "❌ File include failed: " . $e->getMessage() . "\n";
    exit;
}

// Test 2: Test database connection
echo "\n🔌 Testing database connection...\n";
try {
    $pdo = get_pdo();
    echo "✅ Database connection successful\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit;
}

// Test 3: Test user authentication functions
echo "\n👤 Testing user authentication...\n";
try {
    $currentUser = get_logged_in_user();
    echo "✅ get_logged_in_user() executed successfully\n";
    echo "   Current user: " . ($currentUser ? $currentUser['name'] : 'Not logged in') . "\n";
} catch (Exception $e) {
    echo "❌ User authentication failed: " . $e->getMessage() . "\n";
}

// Test 4: Test database queries
echo "\n📊 Testing database queries...\n";
try {
    // Test properties query
    $stmt = $pdo->query("SELECT COUNT(*) FROM properties");
    $totalProperties = $stmt->fetchColumn();
    echo "✅ Properties query successful: $totalProperties properties found\n";
    
    // Test users query
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('buyer','seller')");
    $totalUsers = $stmt->fetchColumn();
    echo "✅ Users query successful: $totalUsers users found\n";
    
    // Test offers query
    $stmt = $pdo->query("SELECT COUNT(*) FROM offers");
    $totalOffers = $stmt->fetchColumn();
    echo "✅ Offers query successful: $totalOffers offers found\n";
    
} catch (Exception $e) {
    echo "❌ Database queries failed: " . $e->getMessage() . "\n";
}

// Test 5: Test language functions
echo "\n🌐 Testing language functions...\n";
try {
    $currentLang = get_current_language();
    echo "✅ Current language: $currentLang\n";
    
    // Test all required translations
    $requiredTranslations = [
        'contact', 'estimated_value', 'logout', 'login', 'register'
    ];
    
    foreach ($requiredTranslations as $key) {
        $translation = __($key);
        if (!empty($translation)) {
            echo "✅ Translation '$key' = '$translation'\n";
        } else {
            echo "❌ Translation '$key' is empty\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Language functions failed: " . $e->getMessage() . "\n";
}

// Test 6: Test language switcher component
echo "\n🔄 Testing language switcher component...\n";
try {
    if (file_exists('public/components/language-switcher.php')) {
        echo "✅ Language switcher component exists\n";
        
        // Test if it can be included without errors
        ob_start();
        include 'public/components/language-switcher.php';
        $componentOutput = ob_get_clean();
        
        if (!empty($componentOutput)) {
            echo "✅ Language switcher component loaded successfully\n";
        } else {
            echo "❌ Language switcher component output is empty\n";
        }
    } else {
        echo "❌ Language switcher component missing\n";
    }
} catch (Exception $e) {
    echo "❌ Language switcher component failed: " . $e->getMessage() . "\n";
}

echo "\n🎯 Homepage test completed!\n";
echo "If all tests pass with ✅, your homepage should load without errors.\n";
echo "If you see any ❌ errors, those need to be fixed.\n";
?>

