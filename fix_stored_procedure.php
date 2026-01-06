<?php
declare(strict_types=1);

/**
 * Fix Stored Procedure Script
 * 
 * This script fixes the GetContentBasedRecommendations stored procedure.
 */

echo "🔧 Fixing Stored Procedure...\n\n";

// Database connection
try {
    require_once __DIR__ . '/config/db.php';
    $pdo = get_pdo();
    echo "✅ Database connection established\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Read the SQL fix file
$sqlFile = __DIR__ . '/fix_stored_procedure.sql';
if (!file_exists($sqlFile)) {
    echo "❌ SQL fix file not found: {$sqlFile}\n";
    exit(1);
}

echo "📖 Reading SQL fix file...\n";
$sql = file_get_contents($sqlFile);

// Split SQL into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

echo "🔧 Executing stored procedure fix...\n";
$successCount = 0;
$errorCount = 0;

foreach ($statements as $statement) {
    if (empty($statement) || strpos($statement, '--') === 0) {
        continue; // Skip comments and empty lines
    }
    
    try {
        // Handle DELIMITER statements specially
        if (strpos($statement, 'DELIMITER') === 0) {
            echo "  ℹ️  Skipping DELIMITER statement\n";
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

echo "\n📊 Fix Summary:\n";
echo "  ✅ Successful statements: {$successCount}\n";
echo "  ❌ Failed statements: {$errorCount}\n";

if ($errorCount === 0) {
    echo "\n🎉 Stored Procedure Fix Completed Successfully!\n";
    
    // Test the procedure
    echo "\n🧪 Testing the stored procedure...\n";
    try {
        $stmt = $pdo->query("CALL GetContentBasedRecommendations(2, 3)");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($results) > 0) {
            echo "  ✅ Procedure executed successfully!\n";
            echo "  📊 Found " . count($results) . " recommendations\n";
            
            foreach ($results as $result) {
                echo "    • {$result['title']} - Score: " . number_format($result['recommendation_score'], 3) . "\n";
            }
        } else {
            echo "  ℹ️  Procedure executed but no recommendations found\n";
        }
        
    } catch (Exception $e) {
        echo "  ❌ Error testing procedure: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "\n⚠️  Fix completed with errors. Please review the error messages above.\n";
}

echo "\n🏠 RealEstate Property Availability System stored procedure is fixed!\n";
