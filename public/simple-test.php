<?php
session_start();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Simple Test · NepaEstate</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 40px; }
    .btn { display: inline-block; padding: 10px 20px; margin: 10px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
    .btn:hover { background: #0056b3; }
    .error { color: red; }
    .success { color: green; }
  </style>
</head>
<body>
  <h1>🧪 Simple Navigation Test</h1>
  
  <p>This is a basic test page to check if navigation works.</p>
  
  <div style="margin: 20px 0;">
    <h3>Test Links:</h3>
    <a href="index.php" class="btn">🏠 Home</a>
    <a href="profile.php" class="btn">👤 Profile</a>
    <a href="edit-profile.php" class="btn">✏️ Edit Profile</a>
    <a href="change-password.php" class="btn">🔑 Change Password</a>
    <a href="request-update.php" class="btn">📝 Request Update</a>
    <a href="contact.php" class="btn">📞 Contact</a>
    <a href="logout.php" class="btn">🚪 Logout</a>
  </div>
  
  <div style="margin: 20px 0; padding: 20px; background: #f5f5f5; border-radius: 8px;">
    <h3>🔍 Debug Info:</h3>
    <p><strong>Current URL:</strong> <?= $_SERVER['REQUEST_URI'] ?></p>
    <p><strong>Session ID:</strong> <?= session_id() ?></p>
    <p><strong>User ID:</strong> <?= $_SESSION['user_id'] ?? 'Not set' ?></p>
    <p><strong>PHP Version:</strong> <?= PHP_VERSION ?></p>
  </div>
  
  <script>
    console.log('Simple test page loaded');
    
    // Test if links are clickable
    document.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', function(e) {
        console.log('Link clicked:', this.href);
        // Let the link work normally
      });
    });
  </script>
</body>
</html>
