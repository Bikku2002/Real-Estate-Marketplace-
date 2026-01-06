<?php
// Automatic database update script for KYC columns
declare(strict_types=1);
require_once __DIR__ . '/config/db.php';

echo "<h2>🔄 Database Update Script</h2>";

try {
    $pdo = get_pdo();
    echo "✅ Connected to database<br><br>";
    
    // List of KYC columns to add
    $columns = [
        'profile_image' => "VARCHAR(255) NULL",
        'kyc_status' => "ENUM('pending','verified','rejected') DEFAULT 'pending'",
        'kyc_document_type' => "ENUM('citizenship','passport','license') NULL",
        'kyc_document_number' => "VARCHAR(50) NULL", 
        'kyc_document_image' => "VARCHAR(255) NULL",
        'kyc_verified_at' => "TIMESTAMP NULL",
        'kyc_notes' => "TEXT NULL"
    ];
    
    // Check which columns exist
    $stmt = $pdo->query("DESCRIBE users");
    $existingColumns = [];
    while($row = $stmt->fetch()) {
        $existingColumns[] = $row['Field'];
    }
    
    echo "<h3>📋 Column Status Check:</h3>";
    $columnsToAdd = [];
    
    foreach($columns as $columnName => $columnDef) {
        if(in_array($columnName, $existingColumns)) {
            echo "✅ <strong>$columnName</strong> - Already exists<br>";
        } else {
            echo "❌ <strong>$columnName</strong> - Missing (will be added)<br>";
            $columnsToAdd[$columnName] = $columnDef;
        }
    }
    
    // Add missing columns
    if(!empty($columnsToAdd)) {
        echo "<br><h3>🔧 Adding Missing Columns:</h3>";
        
        foreach($columnsToAdd as $columnName => $columnDef) {
            try {
                $sql = "ALTER TABLE users ADD COLUMN $columnName $columnDef";
                $pdo->exec($sql);
                echo "✅ Added column: <strong>$columnName</strong><br>";
            } catch(Exception $e) {
                echo "❌ Failed to add column <strong>$columnName</strong>: " . $e->getMessage() . "<br>";
            }
        }
        
        // Add index for kyc_status if it was added
        if(isset($columnsToAdd['kyc_status'])) {
            try {
                $pdo->exec("ALTER TABLE users ADD INDEX kyc_status (kyc_status)");
                echo "✅ Added index for kyc_status<br>";
            } catch(Exception $e) {
                echo "ℹ️ Index kyc_status: " . $e->getMessage() . "<br>";
            }
        }
        
    } else {
        echo "<br>✅ <strong>All KYC columns already exist!</strong><br>";
    }
    
    // Show final table structure
    echo "<br><h3>📊 Final Users Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    
    $stmt = $pdo->query("DESCRIBE users");
    while($row = $stmt->fetch()) {
        $isKycColumn = in_array($row['Field'], array_keys($columns));
        $rowStyle = $isKycColumn ? 'background: #e8f5e8;' : '';
        
        echo "<tr style='$rowStyle'>";
        echo "<td><strong>{$row['Field']}</strong></td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><h3>🎉 Database Update Completed Successfully!</h3>";
    echo "<p>You can now use the registration form with profile image and KYC features.</p>";
    echo "<p><a href='public/register.php' style='background: #d7263d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Registration →</a></p>";
    
} catch(Exception $e) {
    echo "❌ <strong>Database Error:</strong> " . $e->getMessage() . "<br>";
    echo "<p>Please check your database connection settings in <code>config/db.php</code></p>";
}
?>
