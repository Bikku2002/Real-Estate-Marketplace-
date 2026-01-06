<?php
/**
 * Update Area Measurements Database Schema
 * 
 * This script updates the database to support Nepal-specific area measurements
 * and provides conversion utilities.
 */

require_once __DIR__ . '/config/db.php';

try {
    $pdo = get_pdo();
    echo "🔧 Updating database schema for Nepal Area Measurements...\n\n";
    
    // Read and execute the SQL file
    $sqlFile = __DIR__ . '/database/update_area_measurements_simple.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip comments and empty statements
        }
        
        try {
            $pdo->exec($statement);
            $successCount++;
            echo "✅ Executed: " . substr($statement, 0, 50) . "...\n";
        } catch (PDOException $e) {
            $errorCount++;
            echo "❌ Error executing: " . substr($statement, 0, 50) . "...\n";
            echo "   Error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 Database Update Summary:\n";
    echo "   ✅ Successful: $successCount\n";
    echo "   ❌ Errors: $errorCount\n";
    
    if ($errorCount === 0) {
        echo "\n🎉 Area measurement system updated successfully!\n";
        
        // Test the new system
        echo "\n🧪 Testing the new area measurement system...\n";
        
        // Test area conversions
        require_once __DIR__ . '/config/nepal_area_units.php';
        
        $testCases = [
            ['value' => 1, 'from' => 'ana', 'to' => 'sqft', 'expected' => 342.25],
            ['value' => 1, 'from' => 'ropani', 'to' => 'sqft', 'expected' => 5476],
            ['value' => 1, 'from' => 'kattha', 'to' => 'sqft', 'expected' => 1711.2],
            ['value' => 1000, 'from' => 'sqft', 'to' => 'sqm', 'expected' => 92.903],
        ];
        
        foreach ($testCases as $test) {
            $result = NepalAreaUnits::convert($test['value'], $test['from'], $test['to']);
            $expected = $test['expected'];
            $passed = abs($result - $expected) < 0.01;
            
            echo "   " . ($passed ? "✅" : "❌") . " Convert {$test['value']} {$test['from']} to {$test['to']}: ";
            echo "Expected {$expected}, Got " . round($result, 2) . "\n";
        }
        
        // Show available units
        echo "\n📏 Available Area Units:\n";
        $units = NepalAreaUnits::getAvailableUnits();
        foreach ($units as $unit => $info) {
            echo "   • {$info['name']} ({$info['short']})\n";
        }
        
        // Test area formatting
        echo "\n🎨 Area Formatting Examples:\n";
        $formatTests = [
            ['value' => 5.5, 'unit' => 'ana'],
            ['value' => 1000, 'unit' => 'sqft'],
            ['value' => 2, 'unit' => 'ropani'],
        ];
        
        foreach ($formatTests as $test) {
            $formatted = NepalAreaUnits::formatArea($test['value'], $test['unit']);
            echo "   • {$test['value']} {$test['unit']} → {$formatted}\n";
        }
        
        // Test multiple unit display
        echo "\n🔄 Multiple Unit Display Example (5 Ana):\n";
        $multiUnits = NepalAreaUnits::getAreaInMultipleUnits(5, 'ana');
        foreach ($multiUnits as $unit => $data) {
            if (in_array($unit, ['ana', 'sqft', 'sqm', 'ropani'])) {
                echo "   • {$data['formatted']}\n";
            }
        }
        
    } else {
        echo "\n⚠️  Some errors occurred. Please check the error messages above.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n🏁 Script execution completed.\n";
?>
