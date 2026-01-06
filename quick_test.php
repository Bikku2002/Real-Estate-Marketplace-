<?php
declare(strict_types=1);

echo "🚀 Quick Test for RealEstate...\n\n";

// Test 1: Basic PHP
echo "📱 PHP Version: " . PHP_VERSION . "\n";
echo "✅ Basic PHP working\n\n";

// Test 2: Database connection
echo "🔌 Testing database connection...\n";
try {
    require_once 'config/db.php';
    $pdo = get_pdo();
    echo "✅ Database connection successful\n";
    
    // Test basic query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✅ Database query successful: " . $result['count'] . " users found\n";
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
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
    
} catch (Exception $e) {
    echo "❌ Language system failed: " . $e->getMessage() . "\n";
}

// Test 4: User auth
echo "\n👤 Testing user auth...\n";
try {
    require_once 'config/user_auth.php';
    echo "✅ user_auth.php loaded\n";
    
    $isLoggedIn = is_user_logged_in();
    echo "✅ User login check: " . ($isLoggedIn ? 'Logged in' : 'Not logged in') . "\n";
    
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
    
} catch (Exception $e) {
    echo "❌ Pricing algorithms failed: " . $e->getMessage() . "\n";
}

echo "\n🎯 Quick test completed!\n";
echo "If you see any ❌ errors above, those need to be fixed.\n";
echo "If all tests pass with ✅, your system is ready!\n";
?>
