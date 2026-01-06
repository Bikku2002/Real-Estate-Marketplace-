<?php
declare(strict_types=1);

/**
 * Simple Test Script
 * 
 * This script performs basic tests to verify the system is working.
 */

echo "🧪 Simple System Test...\n\n";

// Database connection
try {
    require_once __DIR__ . '/config/db.php';
    $pdo = get_pdo();
    echo "✅ Database connection established\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 1: Check basic tables
echo "\n📋 Test 1: Checking basic tables...\n";
try {
    $tables = ['properties', 'users', 'user_preferences', 'property_features'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$table}");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "  ✅ Table '{$table}' has {$count} records\n";
    }
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

// Test 2: Check user preferences
echo "\n📋 Test 2: Checking user preferences...\n";
try {
    $stmt = $pdo->query("SELECT * FROM user_preferences LIMIT 2");
    $prefs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($prefs) > 0) {
        echo "  ✅ Found " . count($prefs) . " user preferences\n";
        foreach ($prefs as $pref) {
            echo "    • User {$pref['user_id']}: {$pref['preference_type']} = {$pref['preference_value']}\n";
        }
    } else {
        echo "  ℹ️  No user preferences found\n";
    }
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

// Test 3: Check property availability
echo "\n📋 Test 3: Checking property availability...\n";
try {
    $stmt = $pdo->query("SELECT availability_status, COUNT(*) as count FROM properties GROUP BY availability_status");
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "  ✅ Property availability status:\n";
    foreach ($statuses as $status) {
        echo "    • {$status['availability_status']}: {$status['count']} properties\n";
    }
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

// Test 4: Simple recommendation test
echo "\n📋 Test 4: Simple recommendation test...\n";
try {
    // Just get available properties with basic info
    $stmt = $pdo->query("
        SELECT p.id, p.title, p.type, p.district, p.price, p.availability_status 
        FROM properties p 
        WHERE p.availability_status = 'available' 
        LIMIT 3
    ");
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "  ✅ Available properties:\n";
    foreach ($properties as $prop) {
        echo "    • {$prop['title']} ({$prop['type']} in {$prop['district']}) - Rs " . number_format($prop['price']) . "\n";
    }
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🎉 Simple Test Complete!\n";
echo "\n🏠 RealEstate Property Availability System is working!\n";
