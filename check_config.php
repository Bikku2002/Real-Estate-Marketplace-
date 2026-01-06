<?php
// Check PHP configuration for file uploads
echo "<h2>PHP Configuration Check</h2>";

echo "<h3>File Upload Settings:</h3>";
echo "file_uploads: " . (ini_get('file_uploads') ? '✅ Enabled' : '❌ Disabled') . "<br>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";

echo "<h3>Directory Permissions:</h3>";
$uploadDir = __DIR__ . '/public/uploads/';
if (is_dir($uploadDir)) {
    echo "uploads directory: ✅ Exists<br>";
    if (is_writable($uploadDir)) {
        echo "uploads directory: ✅ Writable<br>";
    } else {
        echo "uploads directory: ❌ Not writable<br>";
    }
} else {
    echo "uploads directory: ❌ Does not exist<br>";
    // Try to create it
    if (mkdir($uploadDir, 0755, true)) {
        echo "uploads directory: ✅ Created successfully<br>";
    } else {
        echo "uploads directory: ❌ Failed to create<br>";
    }
}

echo "<h3>PHP Extensions:</h3>";
echo "PDO: " . (extension_loaded('pdo') ? '✅ Loaded' : '❌ Missing') . "<br>";
echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✅ Loaded' : '❌ Missing') . "<br>";
echo "GD (for image handling): " . (extension_loaded('gd') ? '✅ Loaded' : '❌ Missing') . "<br>";
echo "Fileinfo: " . (extension_loaded('fileinfo') ? '✅ Loaded' : '❌ Missing') . "<br>";

echo "<h3>Error Reporting:</h3>";
echo "display_errors: " . (ini_get('display_errors') ? '✅ On' : '❌ Off') . "<br>";
echo "error_reporting: " . error_reporting() . "<br>";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "✅ Error reporting enabled for debugging<br>";
?>
