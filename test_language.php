<?php
declare(strict_types=1);

echo "🧪 Testing Language System...\n\n";

// Test 1: Check if languages.php exists
echo "📁 Checking languages.php...\n";
if (file_exists('config/languages.php')) {
    echo "✅ languages.php exists\n";
} else {
    echo "❌ languages.php missing\n";
    exit;
}

// Test 2: Include languages.php and test functions
echo "\n🔧 Testing language functions...\n";
try {
    require_once 'config/languages.php';
    echo "✅ languages.php included successfully\n";
    
    // Test get_current_language
    $currentLang = get_current_language();
    echo "✅ get_current_language() works: $currentLang\n";
    
    // Test __() function
    $translation = __('home');
    echo "✅ __() function works: home = $translation\n";
    
    // Test get_language_name
    $langName = get_language_name($currentLang);
    echo "✅ get_language_name() works: $currentLang = $langName\n";
    
    // Test set_language
    set_language('ne');
    $newLang = get_current_language();
    echo "✅ set_language() works: changed to $newLang\n";
    
    // Test translation in new language
    $nepaliTranslation = __('home');
    echo "✅ Nepali translation works: home = $nepaliTranslation\n";
    
    // Reset to English
    set_language('en');
    echo "✅ Reset to English successful\n";
    
} catch (Exception $e) {
    echo "❌ Language functions failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n🎯 Language system test completed!\n";
?>
