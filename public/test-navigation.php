<?php
session_start();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Navigation Test · NepaEstate</title>
  <link rel="stylesheet" href="assets/css/styles.css"/>
</head>
<body>
  <header class="site-header">
    <div class="container nav">
      <a class="brand" href="index.php">
        <div class="brand-logo pulse"></div>
        <div class="brand-name">NepaEstate</div>
      </a>
      <div class="nav-actions">
        <a class="btn" href="index.php">🏠 Home</a>
        <a class="btn" href="contact.php">📞 Contact</a>
        <a class="btn btn-primary" href="profile.php">👤 Profile</a>
        <a class="btn" href="logout.php">🚪 Logout</a>
      </div>
    </div>
  </header>

  <main style="padding: 50px 0;">
    <div class="container">
      <h1>🧪 Navigation Test Page</h1>
      <p>This page is to test if navigation links are working properly.</p>
      
      <div style="margin: 30px 0;">
        <h2>Test Links:</h2>
        <div style="display: grid; gap: 20px; max-width: 600px;">
          <a href="index.php" class="btn" style="text-align: center; padding: 20px;">
            🏠 Go to Homepage
          </a>
          <a href="profile.php" class="btn" style="text-align: center; padding: 20px;">
            👤 Go to Profile
          </a>
          <a href="edit-profile.php" class="btn" style="text-align: center; padding: 20px;">
            ✏️ Go to Edit Profile
          </a>
          <a href="change-password.php" class="btn" style="text-align: center; padding: 20px;">
            🔑 Go to Change Password
          </a>
          <a href="request-update.php" class="btn" style="text-align: center; padding: 20px;">
            📝 Go to Request Update
          </a>
          <a href="contact.php" class="btn" style="text-align: center; padding: 20px;">
            📞 Go to Contact
          </a>
          <a href="logout.php" class="btn" style="text-align: center; padding: 20px;">
            🚪 Go to Logout
          </a>
        </div>
      </div>
      
      <div style="margin: 30px 0; padding: 20px; background: #f5f5f5; border-radius: 8px;">
        <h3>🔍 Debug Information:</h3>
        <p><strong>Current URL:</strong> <?= $_SERVER['REQUEST_URI'] ?></p>
        <p><strong>Session ID:</strong> <?= session_id() ?></p>
        <p><strong>User ID:</strong> <?= $_SESSION['user_id'] ?? 'Not set' ?></p>
        <p><strong>PHP Version:</strong> <?= PHP_VERSION ?></p>
      </div>
    </div>
  </main>

  <script>
    console.log('Test page loaded');
    
    // Simple click logging
    document.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', function(e) {
        console.log('Link clicked:', this.href);
        // Let the link work normally
      });
    });
  </script>
</body>
</html>
