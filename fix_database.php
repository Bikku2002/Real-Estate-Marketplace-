<?php
declare(strict_types=1);

echo "🔧 Fixing Database Issues...\n\n";

try {
    require_once 'config/db.php';
    $pdo = get_pdo();
    echo "✅ Database connection successful\n";
    
    // Check and create missing tables
    echo "\n🗄️ Checking and creating missing tables...\n";
    
    // 1. Check if profile_update_requests table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'profile_update_requests'");
    if ($stmt->rowCount() == 0) {
        echo "❌ profile_update_requests table missing - creating it...\n";
        $pdo->exec("
            CREATE TABLE profile_update_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                field_name VARCHAR(50) NOT NULL,
                current_value TEXT,
                new_value TEXT NOT NULL,
                reason TEXT,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                admin_notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        echo "✅ profile_update_requests table created\n";
    } else {
        echo "✅ profile_update_requests table exists\n";
    }
    
    // 2. Check if password_reset_requests table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'password_reset_requests'");
    if ($stmt->rowCount() == 0) {
        echo "❌ password_reset_requests table missing - creating it...\n";
        $pdo->exec("
            CREATE TABLE password_reset_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                reason TEXT NOT NULL,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                admin_notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        echo "✅ password_reset_requests table created\n";
    } else {
        echo "✅ password_reset_requests table exists\n";
    }
    
    // 3. Check if user_sessions table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_sessions'");
    if ($stmt->rowCount() == 0) {
        echo "❌ user_sessions table missing - creating it...\n";
        $pdo->exec("
            CREATE TABLE user_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                session_token VARCHAR(64) NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_token (session_token)
            )
        ");
        echo "✅ user_sessions table created\n";
    } else {
        echo "✅ user_sessions table exists\n";
    }
    
    // 4. Check if advanced feature tables exist
    $advancedTables = [
        'property_valuations' => "
            CREATE TABLE property_valuations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                property_id INT NOT NULL,
                gwr_price DECIMAL(15, 2) NOT NULL,
                gwr_confidence DECIMAL(5, 4) NOT NULL,
                knn_price DECIMAL(15, 2) NOT NULL,
                knn_confidence DECIMAL(5, 4) NOT NULL,
                cosine_price DECIMAL(15, 2) NOT NULL,
                cosine_confidence DECIMAL(5, 4) NOT NULL,
                ensemble_price DECIMAL(15, 2) NOT NULL,
                ensemble_confidence DECIMAL(5, 4) NOT NULL,
                nearby_properties_count INT DEFAULT 0,
                neighbors_used INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
            )
        ",
        'market_trends' => "
            CREATE TABLE market_trends (
                id INT AUTO_INCREMENT PRIMARY KEY,
                district VARCHAR(100) NOT NULL,
                property_type ENUM('land', 'house') NOT NULL,
                month_year VARCHAR(7) NOT NULL,
                avg_price DECIMAL(15, 2) NOT NULL,
                min_price DECIMAL(15, 2) NOT NULL,
                max_price DECIMAL(15, 2) NOT NULL,
                sales_count INT NOT NULL,
                price_change_percent DECIMAL(5, 2) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_trend (district, property_type, month_year)
            )
        ",
        'comparable_sales' => "
            CREATE TABLE comparable_sales (
                id INT AUTO_INCREMENT PRIMARY KEY,
                property_id INT NOT NULL,
                comparable_property_id INT NOT NULL,
                similarity_score DECIMAL(5, 4) NOT NULL,
                distance_km DECIMAL(8, 4) DEFAULT NULL,
                price_difference_percent DECIMAL(8, 4) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
                FOREIGN KEY (comparable_property_id) REFERENCES properties(id) ON DELETE CASCADE
            )
        ",
        'user_preferences' => "
            CREATE TABLE user_preferences (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                language VARCHAR(2) DEFAULT 'en',
                currency VARCHAR(3) DEFAULT 'NPR',
                measurement_unit ENUM('sqft', 'sqm', 'ropani') DEFAULT 'sqft',
                notifications_enabled BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_user_pref (user_id)
            )
        "
    ];
    
    foreach ($advancedTables as $tableName => $createSQL) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
        if ($stmt->rowCount() == 0) {
            echo "❌ $tableName table missing - creating it...\n";
            $pdo->exec($createSQL);
            echo "✅ $tableName table created\n";
        } else {
            echo "✅ $tableName table exists\n";
        }
    }
    
    // 5. Check and add missing columns to properties table
    echo "\n📊 Checking properties table columns...\n";
    $stmt = $pdo->query("DESCRIBE properties");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $missingColumns = [
        'latitude' => 'DECIMAL(10, 8) DEFAULT NULL',
        'longitude' => 'DECIMAL(11, 8) DEFAULT NULL',
        'features' => 'JSON DEFAULT NULL',
        'size' => 'DECIMAL(10, 2) DEFAULT NULL',
        'price_per_sqft' => 'DECIMAL(12, 2) DEFAULT NULL',
        'last_valuation_at' => 'TIMESTAMP NULL',
        'valuation_confidence' => 'DECIMAL(5, 4) DEFAULT NULL'
    ];
    
    foreach ($missingColumns as $column => $definition) {
        if (!in_array($column, $existingColumns)) {
            echo "❌ Column '$column' missing - adding it...\n";
            try {
                $pdo->exec("ALTER TABLE properties ADD COLUMN $column $definition");
                echo "✅ Column '$column' added successfully\n";
            } catch (Exception $e) {
                echo "❌ Failed to add column '$column': " . $e->getMessage() . "\n";
            }
        } else {
            echo "✅ Column '$column' exists\n";
        }
    }
    
    // 6. Check and add missing columns to users table
    echo "\n👤 Checking users table columns...\n";
    $stmt = $pdo->query("DESCRIBE users");
    $existingUserColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $missingUserColumns = [
        'profile_image' => 'VARCHAR(255) DEFAULT NULL',
        'kyc_verified' => 'BOOLEAN DEFAULT FALSE',
        'kyc_document' => 'VARCHAR(255) DEFAULT NULL',
        'login_attempts' => 'INT DEFAULT 0',
        'last_login_attempt' => 'TIMESTAMP NULL',
        'last_login' => 'TIMESTAMP NULL'
    ];
    
    foreach ($missingUserColumns as $column => $definition) {
        if (!in_array($column, $existingUserColumns)) {
            echo "❌ Column '$column' missing - adding it...\n";
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN $column $definition");
                echo "✅ Column '$column' added successfully\n";
            } catch (Exception $e) {
                echo "❌ Failed to add column '$column': " . $e->getMessage() . "\n";
            }
        } else {
            echo "✅ Column '$column' exists\n";
        }
    }
    
    // 7. Create indexes for better performance
    echo "\n⚡ Creating performance indexes...\n";
    $indexes = [
        'idx_properties_location' => 'CREATE INDEX idx_properties_location ON properties(latitude, longitude)',
        'idx_properties_district_type' => 'CREATE INDEX idx_properties_district_type ON properties(district, type)',
        'idx_properties_price' => 'CREATE INDEX idx_properties_price ON properties(price)',
        'idx_properties_created' => 'CREATE INDEX idx_properties_created ON properties(created_at)',
        'idx_users_email' => 'CREATE INDEX idx_users_email ON users(email)',
        'idx_users_role' => 'CREATE INDEX idx_users_role ON users(role)'
    ];
    
    foreach ($indexes as $indexName => $createIndexSQL) {
        try {
            $pdo->exec($createIndexSQL);
            echo "✅ Index '$indexName' created\n";
        } catch (Exception $e) {
            // Index might already exist, which is fine
            echo "ℹ️ Index '$indexName' already exists or failed: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n🎯 Database fixes completed!\n";
    echo "All required tables and columns should now be available.\n";
    
} catch (Exception $e) {
    echo "❌ Database fix failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>

