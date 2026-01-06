<?php
// Simple test to check database connection and schema
declare(strict_types=1);
require_once __DIR__ . '/config/db.php';

try {
    $pdo = get_pdo();
    echo "✅ Database connection successful<br>";
    
    // Test if users table has the new columns
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    
    echo "<h3>Users table structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    
    $requiredColumns = ['profile_image', 'kyc_status', 'kyc_document_type', 'kyc_document_number', 'kyc_document_image'];
    $foundColumns = [];
    
    foreach($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
        
        if(in_array($col['Field'], $requiredColumns)) {
            $foundColumns[] = $col['Field'];
        }
    }
    echo "</table>";
    
    echo "<h3>KYC Columns Check:</h3>";
    foreach($requiredColumns as $reqCol) {
        if(in_array($reqCol, $foundColumns)) {
            echo "✅ $reqCol - Found<br>";
        } else {
            echo "❌ $reqCol - Missing<br>";
        }
    }
    
    // Test simple insert (without files)
    echo "<h3>Testing simple user creation:</h3>";
    try {
        $testEmail = 'test_' . uniqid() . '@example.com';
        $stmt = $pdo->prepare("INSERT INTO users(name, email, role, password_hash) VALUES(:n, :e, :r, :h)");
        $result = $stmt->execute([
            ':n' => 'Test User',
            ':e' => $testEmail,
            ':r' => 'buyer',
            ':h' => password_hash('testpass', PASSWORD_BCRYPT)
        ]);
        
        if($result) {
            echo "✅ Test user created successfully<br>";
            // Clean up
            $pdo->prepare("DELETE FROM users WHERE email = :e")->execute([':e' => $testEmail]);
            echo "✅ Test user cleaned up<br>";
        } else {
            echo "❌ Failed to create test user<br>";
        }
    } catch(Exception $e) {
        echo "❌ Database insert error: " . $e->getMessage() . "<br>";
    }
    
} catch(Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    echo "Please check your database configuration in config/db.php<br>";
}
?>
