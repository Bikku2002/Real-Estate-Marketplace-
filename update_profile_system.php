<?php
// Automatic database update script for Profile System tables
declare(strict_types=1);
require_once __DIR__ . '/config/db.php';

echo "<h2>🔄 Profile System Database Update</h2>";

try {
    $pdo = get_pdo();
    echo "✅ Connected to database<br><br>";
    
    // Create profile_update_requests table
    echo "<h3>📝 Creating Profile Update Requests Table</h3>";
    $sql = "
    CREATE TABLE IF NOT EXISTS profile_update_requests (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      request_type ENUM('name', 'phone', 'profile_image', 'kyc_document') NOT NULL,
      current_data TEXT,
      requested_data TEXT,
      reason TEXT NOT NULL,
      uploaded_file VARCHAR(255) NULL,
      status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
      admin_notes TEXT NULL,
      admin_id INT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      CONSTRAINT fk_profile_update_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      CONSTRAINT fk_profile_update_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
      INDEX (user_id),
      INDEX (status),
      INDEX (created_at)
    )";
    
    $pdo->exec($sql);
    echo "✅ Profile update requests table created<br>";
    
    // Create password_reset_requests table
    echo "<h3>🔑 Creating Password Reset Requests Table</h3>";
    $sql = "
    CREATE TABLE IF NOT EXISTS password_reset_requests (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      email VARCHAR(160) NOT NULL,
      reason TEXT NOT NULL,
      status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
      admin_notes TEXT NULL,
      admin_id INT NULL,
      new_password_hash VARCHAR(255) NULL,
      reset_token VARCHAR(100) NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      CONSTRAINT fk_password_reset_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
      INDEX (user_id),
      INDEX (status),
      INDEX (email),
      INDEX (created_at)
    )";
    
    $pdo->exec($sql);
    echo "✅ Password reset requests table created<br>";
    
    // Create user_sessions table
    echo "<h3>🔐 Creating User Sessions Table</h3>";
    $sql = "
    CREATE TABLE IF NOT EXISTS user_sessions (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      session_token VARCHAR(100) NOT NULL UNIQUE,
      ip_address VARCHAR(45),
      user_agent TEXT,
      last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      CONSTRAINT fk_session_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      INDEX (user_id),
      INDEX (session_token),
      INDEX (last_activity)
    )";
    
    $pdo->exec($sql);
    echo "✅ User sessions table created<br>";
    
    // Add new columns to users table
    echo "<h3>👤 Updating Users Table</h3>";
    
    $columns_to_add = [
        'last_login' => 'TIMESTAMP NULL',
        'login_attempts' => 'INT DEFAULT 0',
        'locked_until' => 'TIMESTAMP NULL'
    ];
    
    // Check existing columns
    $stmt = $pdo->query("DESCRIBE users");
    $existing_columns = [];
    while($row = $stmt->fetch()) {
        $existing_columns[] = $row['Field'];
    }
    
    foreach($columns_to_add as $column => $definition) {
        if(!in_array($column, $existing_columns)) {
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN $column $definition");
                echo "✅ Added column: <strong>$column</strong><br>";
            } catch(Exception $e) {
                echo "❌ Failed to add column <strong>$column</strong>: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "ℹ️ Column <strong>$column</strong> already exists<br>";
        }
    }
    
    // Create upload directories
    echo "<h3>📁 Creating Upload Directories</h3>";
    $directories = [
        __DIR__ . '/public/uploads/update_requests/',
        __DIR__ . '/public/uploads/profiles/',
        __DIR__ . '/public/uploads/kyc/'
    ];
    
    foreach($directories as $dir) {
        if(!is_dir($dir)) {
            if(mkdir($dir, 0755, true)) {
                echo "✅ Created directory: <strong>" . basename($dir) . "</strong><br>";
            } else {
                echo "❌ Failed to create directory: <strong>" . basename($dir) . "</strong><br>";
            }
        } else {
            echo "ℹ️ Directory <strong>" . basename($dir) . "</strong> already exists<br>";
        }
    }
    
    echo "<br><h3>🎉 Profile System Setup Complete!</h3>";
    echo "<div style='background:#e8f5e8;padding:16px;border-radius:8px;border:1px solid #2fb070'>";
    echo "<h4>✅ What's New:</h4>";
    echo "<ul>";
    echo "<li><strong>User Profiles:</strong> Users can view and manage their profiles</li>";
    echo "<li><strong>Profile Updates:</strong> Users can request profile changes from admin</li>";
    echo "<li><strong>Password Reset:</strong> Forgot password functionality with admin approval</li>";
    echo "<li><strong>Session Management:</strong> Secure login sessions with tracking</li>";
    echo "<li><strong>Admin Management:</strong> New admin pages to handle user requests</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<br><h4>🔗 Quick Links:</h4>";
    echo "<p>";
    echo "<a href='public/login.php' style='background:#d7263d;color:white;padding:8px 16px;text-decoration:none;border-radius:4px;margin-right:8px'>User Login →</a>";
    echo "<a href='public/register.php' style='background:#2fb070;color:white;padding:8px 16px;text-decoration:none;border-radius:4px;margin-right:8px'>Register →</a>";
    echo "<a href='public/admin/login.php' style='background:#0f141c;color:white;padding:8px 16px;text-decoration:none;border-radius:4px'>Admin Login →</a>";
    echo "</p>";
    
} catch(Exception $e) {
    echo "❌ <strong>Database Error:</strong> " . $e->getMessage() . "<br>";
    echo "<p>Please check your database connection settings in <code>config/db.php</code></p>";
}
?>
