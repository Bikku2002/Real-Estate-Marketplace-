<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = get_pdo();
$userId = (int)$_SESSION['user_id'];
$success = null;
$error = null;

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Please fill in all fields.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'New password must be at least 6 characters long.';
    } else {
        try {
            // Verify current password
            if (password_verify($currentPassword, $user['password_hash'])) {
                // Hash new password
                $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                
                // Update password
                $stmt = $pdo->prepare("UPDATE users SET password_hash = :password WHERE id = :id");
                $result = $stmt->execute([
                    ':password' => $newPasswordHash,
                    ':id' => $userId
                ]);
                
                if ($result) {
                    $success = 'Password changed successfully!';
                } else {
                    $error = 'Failed to change password. Please try again.';
                }
            } else {
                $error = 'Current password is incorrect.';
            }
        } catch (Exception $e) {
            $error = 'Failed to change password. Please try again.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Change Password · NepaEstate</title>
  <link rel="stylesheet" href="assets/css/styles.css"/>
  <link rel="stylesheet" href="assets/css/register.css"/>
</head>
<body class="register-body">
  <div class="register-container">
    <!-- Background Animation -->
    <div class="bg-animation">
      <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
      </div>
    </div>
    
    <!-- Change Password Form -->
    <main class="register-main">
      <div class="register-card">
        <div class="card-header">
          <div class="brand-section">
            <div class="brand-logo-3d">🔑</div>
            <h1 class="brand-title">🔑 Change Password</h1>
            <p class="brand-subtitle">🔒 Update your account password</p>
          </div>
        </div>
        
        <div class="card-body">
          <!-- Success/Error Messages -->
          <?php if($success): ?>
            <div class="alert alert-success">
              <div class="alert-icon">✅</div>
              <div class="alert-content">
                <div class="alert-title">Password Changed!</div>
                <div class="alert-message"><?=$success?></div>
                <div style="margin-top:16px">
                  <a href="profile.php" class="alert-link" style="background:var(--accent);color:white;padding:8px 16px;border-radius:6px;text-decoration:none;margin-right:8px">Back to Profile →</a>
                  <a href="index.php" class="alert-link">Back to Home →</a>
                </div>
              </div>
            </div>
          <?php endif; ?>
          
          <?php if($error): ?>
            <div class="alert alert-error">
              <div class="alert-icon">❌</div>
              <div class="alert-content">
                <div class="alert-title">Password Change Failed</div>
                <div class="alert-message"><?=$error?></div>
              </div>
            </div>
          <?php endif; ?>
          
          <!-- Change Password Form -->
          <?php if(!$success): ?>
          <form method="post" class="register-form">
            <div class="form-group">
              <label class="form-label">🔒 Current Password</label>
              <div class="input-wrapper">
                <input class="form-input" type="password" name="current_password" required 
                       placeholder="Enter your current password"/>
                <div class="input-icon">🔐</div>
              </div>
            </div>
            
            <div class="form-group">
              <label class="form-label">🆕 New Password</label>
              <div class="input-wrapper">
                <input class="form-input" type="password" name="new_password" required 
                       placeholder="Enter your new password (min 6 characters)"/>
                <div class="input-icon">✨</div>
              </div>
            </div>
            
            <div class="form-group">
              <label class="form-label">✅ Confirm New Password</label>
              <div class="input-wrapper">
                <input class="form-input" type="password" name="confirm_password" required 
                       placeholder="Confirm your new password"/>
                <div class="input-icon">✅</div>
              </div>
            </div>
            
            <div class="info-box">
              <div class="info-icon">💡</div>
              <div class="info-content">
                <h4>Password Requirements:</h4>
                <ul>
                  <li>At least 6 characters long</li>
                  <li>Use a mix of letters, numbers, and symbols</li>
                  <li>Don't use easily guessable information</li>
                  <li>Keep your password secure and private</li>
                </ul>
              </div>
            </div>
            
            <div class="form-actions">
              <button type="submit" class="btn-register">
                <span class="btn-text">🔑 Change Password</span>
                <div class="btn-bg"></div>
              </button>
            </div>
          </form>
          <?php endif; ?>
          
          <div class="form-footer">
            <p class="login-link">
              <a href="profile.php" class="link-primary">← Back to Profile</a>
            </p>
          </div>
        </div>
      </div>
      
      <!-- Features Sidebar -->
      <div class="features-sidebar">
        <h3 class="features-title">🔐 Password Security</h3>
        <div class="feature-list">
          <div class="feature-item">
            <div class="feature-icon">🛡️</div>
            <div class="feature-content">
              <h4>Secure Updates</h4>
              <p>All password changes are encrypted and secure</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">🔒</div>
            <div class="feature-content">
              <h4>Account Protection</h4>
              <p>Keep your account safe with strong passwords</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">⚡</div>
            <div class="feature-content">
              <h4>Instant Changes</h4>
              <p>Password updates take effect immediately</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">📱</div>
            <div class="feature-content">
              <h4>Easy Access</h4>
              <p>Change your password anytime from your profile</p>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
  
  <style>
    .info-box {
      background: rgba(47,176,112,0.1);
      border: 1px solid rgba(47,176,112,0.3);
      border-radius: 12px;
      padding: 16px;
      margin: 16px 0;
    }
    
    .info-icon {
      font-size: 20px;
      margin-bottom: 8px;
    }
    
    .info-content h4 {
      color: var(--ink);
      margin: 0 0 12px;
      font-size: 16px;
      font-weight: 600;
    }
    
    .info-content ul {
      margin: 0;
      padding-left: 20px;
      color: var(--muted);
    }
    
    .info-content li {
      margin-bottom: 4px;
      font-size: 14px;
    }
    
    .btn-register {
      background: linear-gradient(135deg, var(--accent), #e74c3c);
      border: none;
      padding: 16px 32px;
      border-radius: 12px;
      color: white;
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    
    .btn-register:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(215, 38, 61, 0.3);
    }
    
    .btn-register:active {
      transform: translateY(0);
    }
  </style>
  
  <script>
    // Form validation
    document.querySelector('.register-form').addEventListener('submit', function(e) {
      const newPassword = document.querySelector('input[name="new_password"]').value;
      const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
      
      if (newPassword.length < 6) {
        e.preventDefault();
        alert('New password must be at least 6 characters long!');
        return false;
      }
      
      if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('New passwords do not match!');
        return false;
      }
      
      // Add loading state to button
      const btn = document.querySelector('.btn-register');
      btn.classList.add('loading');
      btn.querySelector('.btn-text').textContent = '🔑 Changing...';
    });
  </script>
</body>
</html>
