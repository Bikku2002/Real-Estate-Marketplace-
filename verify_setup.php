<?php
declare(strict_types=1);

/**
 * Verification Script for Property Availability System
 * 
 * This script checks if all required tables and columns were created successfully.
 */

echo "🔍 Verifying Property Availability System Setup...\n\n";

// Database connection
try {
    require_once __DIR__ . '/config/db.php';
    $pdo = get_pdo();
    echo "✅ Database connection established\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Check required tables
$requiredTables = [
    'property_availability_history',
    'property_features',
    'user_preferences',
    'property_views',
    'property_recommendations',
    'property_search_history'
];

echo "\n📋 Checking required tables...\n";
$tableStatus = [];

foreach ($requiredTables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        $exists = $stmt->rowCount() > 0;
        $tableStatus[$table] = $exists;
        
        if ($exists) {
            echo "  ✅ Table '{$table}' exists\n";
        } else {
            echo "  ❌ Table '{$table}' missing\n";
        }
    } catch (Exception $e) {
        $tableStatus[$table] = false;
        echo "  ❌ Error checking table '{$table}': " . $e->getMessage() . "\n";
    }
}

// Check properties table columns
echo "\n📋 Checking properties table columns...\n";
$requiredColumns = [
    'availability_status',
    'available_from',
    'available_until',
    'last_status_change',
    'status_change_reason',
    'property_features',
    'property_tags',
    'view_count',
    'favorite_count'
];

$columnStatus = [];

try {
    $stmt = $pdo->query("DESCRIBE properties");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($requiredColumns as $column) {
        $exists = in_array($column, $existingColumns);
        $columnStatus[$column] = $exists;
        
        if ($exists) {
            echo "  ✅ Column '{$column}' exists\n";
        } else {
            echo "  ❌ Column '{$column}' missing\n";
        }
    }
} catch (Exception $e) {
    echo "  ❌ Error checking properties table: " . $e->getMessage() . "\n";
    foreach ($requiredColumns as $column) {
        $columnStatus[$column] = false;
    }
}

// Check sample data
echo "\n📋 Checking sample data...\n";

// Check if we have any properties
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM properties");
    $propertyCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "  ℹ️  Properties table has {$propertyCount} records\n";
} catch (Exception $e) {
    echo "  ❌ Error checking properties: " . $e->getMessage() . "\n";
}

// Check if we have any users
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "  ℹ️  Users table has {$userCount} records\n";
} catch (Exception $e) {
    echo "  ❌ Error checking users: " . $e->getMessage() . "\n";
}

// Check if we have any property features
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM property_features");
    $featureCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "  ℹ️  Property features table has {$featureCount} records\n";
} catch (Exception $e) {
    echo "  ❌ Error checking property features: " . $e->getMessage() . "\n";
}

// Check if we have any user preferences
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user_preferences");
    $preferenceCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "  ℹ️  User preferences table has {$preferenceCount} records\n";
} catch (Exception $e) {
    echo "  ❌ Error checking user preferences: " . $e->getMessage() . "\n";
}

// Check database views
echo "\n📋 Checking database views...\n";
$requiredViews = [
    'available_properties_view',
    'property_statistics_view',
    'market_trends_view'
];

$viewStatus = [];

foreach ($requiredViews as $view) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$view}'");
        $exists = $stmt->rowCount() > 0;
        $viewStatus[$view] = $exists;
        
        if ($exists) {
            echo "  ✅ View '{$view}' exists\n";
        } else {
            echo "  ❌ View '{$view}' missing\n";
        }
    } catch (Exception $e) {
        $viewStatus[$view] = false;
        echo "  ❌ Error checking view '{$view}': " . $e->getMessage() . "\n";
    }
}

// Final summary
echo "\n📊 Verification Summary:\n";
echo "  • Tables: " . count(array_filter($tableStatus)) . "/" . count($requiredTables) . " exist\n";
echo "  • Columns: " . count(array_filter($columnStatus)) . "/" . count($requiredColumns) . " exist\n";
echo "  • Views: " . count(array_filter($viewStatus)) . "/" . count($requiredViews) . " exist\n";

$allTablesExist = count(array_filter($tableStatus)) === count($requiredTables);
$allColumnsExist = count(array_filter($columnStatus)) === count($requiredColumns);
$allViewsExist = count(array_filter($viewStatus)) === count($requiredViews);

if ($allTablesExist && $allColumnsExist) {
    echo "\n🎉 All critical components are properly set up!\n";
    echo "\n🔧 You can now:\n";
    echo "  1. Test the buyer dashboard with recommendations\n";
    echo "  2. Access admin property availability management\n";
    echo "  3. Monitor property status changes\n";
    echo "  4. Test content-based filtering\n";
} else {
    echo "\n⚠️  Some components are missing or incomplete.\n";
    
    if (!$allTablesExist) {
        echo "  • Missing tables: " . implode(', ', array_keys(array_filter($tableStatus, function($exists) { return !$exists; }))) . "\n";
    }
    
    if (!$allColumnsExist) {
        echo "  • Missing columns: " . implode(', ', array_keys(array_filter($columnStatus, function($exists) { return !$exists; }))) . "\n";
    }
    
    if (!$allViewsExist) {
        echo "  • Missing views: " . implode(', ', array_keys(array_filter($viewStatus, function($exists) { return !$exists; }))) . "\n";
    }
    
    echo "\n💡 Run the setup script again to fix missing components.\n";
}

echo "\n🏠 RealEstate Property Availability System verification complete!\n";
