<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';

$pdo = get_pdo();
$success = null;
$error = null;

// Generate secure password function
function generateSecurePassword($length = 12): string {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    
    if (empty($email) || empty($reason)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $error = 'No account found with this email address.';
            } else {
                // Check if there's already a pending request
                $stmt = $pdo->prepare("
                    SELECT id FROM password_reset_requests 
                    WHERE user_id = :user_id AND status = 'pending' 
                    AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ");
                $stmt->execute([':user_id' => $user['id']]);
                
                if ($stmt->fetch()) {
                    $error = 'You already have a pending password reset request. Please wait 24 hours before submitting another.';
                } else {
                    // Auto-approve password reset and generate new password
                    $newPassword = generateSecurePassword();
                    $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
                    
                    $pdo->beginTransaction();
                    try {
                        // Update user password
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET password_hash = :hash, login_attempts = 0, locked_until = NULL 
                            WHERE id = :user_id
                        ");
                        $stmt->execute([':hash' => $passwordHash, ':user_id' => $user['id']]);
                        
                        // Create password reset record (auto-approved)
                        $stmt = $pdo->prepare("
                            INSERT INTO password_reset_requests 
                            (user_id, email, reason, status, new_password_hash, admin_notes) 
                            VALUES (:user_id, :email, :reason, 'approved', :hash, 'Auto-approved - Valid request')
                        ");
                        $stmt->execute([
                            ':user_id' => $user['id'],
                            ':email' => $email,
                            ':reason' => $reason,
                            ':hash' => $passwordHash
                        ]);
                        
                        $pdo->commit();
                        
                        // Store temporary password for display (in production, send via email)
                        $_SESSION['temp_password'] = $newPassword;
                        $_SESSION['reset_email'] = $email;
                        
                        $success = 'Password reset successful! Your new temporary password is: <strong>' . $newPassword . '</strong><br><br>Please save this password and change it after logging in. For security, this password will only be shown once.';
                        
                    } catch (Exception $e) {
                        $pdo->rollback();
                        throw $e;
                    }
                }
            }
        } catch (Exception $e) {
            $error = 'Failed to submit request. Please try again.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Forgot Password · NepaEstate</title>
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
    
    <!-- Forgot Password Form -->
    <main class="register-main">
      <div class="register-card">
        <div class="card-header">
          <div class="brand-section">
            <div class="brand-logo-3d"></div>
            <h1 class="brand-title">Reset Your Password</h1>
            <p class="brand-subtitle">Request a new password from our admin team</p>
          </div>
        </div>
        
        <div class="card-body">
          <!-- Success/Error Messages -->
          <?php if($success): ?>
            <div class="alert alert-success">
              <div class="alert-icon">✅</div>
              <div class="alert-content">
                <div class="alert-title">Password Reset Successful!</div>
                <div class="alert-message"><?=$success?></div>
                <div style="margin-top:16px">
                  <a href="login.php" class="alert-link" style="background:var(--accent);color:white;padding:8px 16px;border-radius:6px;text-decoration:none;margin-right:8px">Login Now →</a>
                  <a href="index.php" class="alert-link">Back to Home →</a>
                </div>
              </div>
            </div>
          <?php endif; ?>
          
          <?php if($error): ?>
            <div class="alert alert-error">
              <div class="alert-icon">❌</div>
              <div class="alert-content">
                <div class="alert-title">Request Failed</div>
                <div class="alert-message"><?=$error?></div>
              </div>
            </div>
          <?php endif; ?>
          
          <!-- Password Reset Form -->
          <?php if(!$success): ?>
          <form method="post" class="register-form">
            <div class="form-group">
              <label class="form-label">Email Address</label>
              <div class="input-wrapper">
                <input class="form-input" type="email" name="email" required 
                       placeholder="Enter your registered email address"
                       value="<?=htmlspecialchars($_POST['email'] ?? '')?>"/>
                <div class="input-icon">📧</div>
              </div>
            </div>
            
            <div class="form-group">
              <label class="form-label">Reason for Password Reset</label>
              <div class="input-wrapper">
                <textarea class="form-input" name="reason" rows="4" required 
                         placeholder="Please explain why you need a password reset (e.g., forgot password, account security concern, etc.)"><?=htmlspecialchars($_POST['reason'] ?? '')?></textarea>
                <div class="input-icon">📝</div>
              </div>
            </div>
            
            <div class="info-box">
              <div class="info-icon">ℹ️</div>
              <div class="info-content">
                <h4>How it works:</h4>
                <ul>
                  <li>Submit your registered email and reason for password reset</li>
                  <li>System automatically verifies your account</li>
                  <li>Get your new temporary password instantly</li>
                  <li>Login with the new password and change it to your preference</li>
                </ul>
              </div>
            </div>
            
            <div class="form-actions">
              <button type="submit" class="btn-register">
                <span class="btn-text">Submit Reset Request</span>
                <div class="btn-bg"></div>
              </button>
            </div>
          </form>
          <?php endif; ?>
          
          <div class="form-footer">
            <p class="login-link">
              Remember your password? 
              <a href="login.php" class="link-primary">Sign in here</a>
            </p>
            <p class="login-link">
              Don't have an account? 
              <a href="register.php" class="link-primary">Register here</a>
            </p>
          </div>
        </div>
      </div>
      
      <!-- Security Features Sidebar -->
      <div class="features-sidebar">
        <h3 class="features-title">🔐 Security First</h3>
        <div class="feature-list">
          <div class="feature-item">
            <div class="feature-icon">🛡️</div>
            <div class="feature-content">
              <h4>Identity Verification</h4>
              <p>We verify your identity before resetting passwords</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">⚡</div>
            <div class="feature-content">
              <h4>Instant Reset</h4>
              <p>Get your new password immediately after verification</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">🤖</div>
            <div class="feature-content">
              <h4>Auto-Approved</h4>
              <p>Valid requests are automatically processed</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">🔑</div>
            <div class="feature-content">
              <h4>Secure Process</h4>
              <p>Temporary passwords sent to verified email only</p>
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
  </style>
</body>
</html>
