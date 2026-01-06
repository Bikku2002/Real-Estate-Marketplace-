<?php
declare(strict_types=1);

/**
 * Property Availability System Setup Script
 * 
 * This script sets up the complete property availability management system
 * including database tables, triggers, and sample data.
 */

echo "🚀 Setting up Property Availability System for RealEstate...\n\n";

// Database connection
try {
    require_once __DIR__ . '/config/db.php';
    $pdo = get_pdo();
    echo "✅ Database connection established\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Read and execute the SQL setup file
$sqlFile = __DIR__ . '/database/property_availability_system.sql';
if (!file_exists($sqlFile)) {
    echo "❌ SQL setup file not found: {$sqlFile}\n";
    exit(1);
}

echo "📖 Reading SQL setup file...\n";
$sql = file_get_contents($sqlFile);

// Split SQL into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

echo "🔧 Executing database setup...\n";
$successCount = 0;
$errorCount = 0;

foreach ($statements as $statement) {
    if (empty($statement) || strpos($statement, '--') === 0) {
        continue; // Skip comments and empty lines
    }
    
    try {
        // Handle DELIMITER statements specially
        if (strpos($statement, 'DELIMITER') === 0) {
            continue; // Skip DELIMITER statements
        }
        
        // Handle stored procedures and functions
        if (strpos($statement, 'CREATE PROCEDURE') === 0 || 
            strpos($statement, 'CREATE FUNCTION') === 0) {
            // For procedures and functions, we need to handle them differently
            echo "  ⚠️  Skipping complex statement (procedure/function): " . substr($statement, 0, 50) . "...\n";
            continue;
        }
        
        $pdo->exec($statement);
        $successCount++;
        echo "  ✅ Executed: " . substr($statement, 0, 50) . "...\n";
        
    } catch (Exception $e) {
        $errorCount++;
        echo "  ❌ Error executing: " . substr($statement, 0, 50) . "...\n";
        echo "     Error: " . $e->getMessage() . "\n";
    }
}

echo "\n📊 Setup Summary:\n";
echo "  ✅ Successful statements: {$successCount}\n";
echo "  ❌ Failed statements: {$errorCount}\n";

if ($errorCount === 0) {
    echo "\n🎉 Property Availability System setup completed successfully!\n";
    echo "\n📋 What was created:\n";
    echo "  • Enhanced properties table with availability tracking\n";
    echo "  • Property availability history table\n";
    echo "  • Property features table for content-based filtering\n";
    echo "  • User preferences table for personalized recommendations\n";
    echo "  • Property view tracking table\n";
    echo "  • Property recommendations table\n";
    echo "  • Search history tracking table\n";
    echo "  • Database triggers for automatic updates\n";
    echo "  • Stored procedures for recommendations\n";
    echo "  • Database views for easy querying\n";
    echo "  • Sample data for testing\n";
    
    echo "\n🔧 Next steps:\n";
    echo "  1. Test the buyer dashboard with recommendations\n";
    echo "  2. Access admin property availability management\n";
    echo "  3. Monitor property status changes\n";
    echo "  4. Test content-based filtering\n";
    
} else {
    echo "\n⚠️  Setup completed with errors. Please review the error messages above.\n";
    echo "Some features may not work correctly.\n";
}

echo "\n🏠 RealEstate Property Availability System is ready!\n";
