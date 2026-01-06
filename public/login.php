<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';

$pdo = get_pdo();
$error = null;

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: profile.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        try {
            // Get user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND role != 'admin'");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $error = 'Invalid email or password.';
            } else {
                // Check if account is locked
                if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                    $error = 'Account is temporarily locked. Please try again later or contact support.';
                } else {
                    // Verify password
                    if (password_verify($password, $user['password_hash'])) {
                        // Reset login attempts
                        $stmt = $pdo->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = :id");
                        $stmt->execute([':id' => $user['id']]);
                        
                        // Create session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['user_email'] = $user['email'];
                        
                        header('Location: profile.php');
                        exit;
                    } else {
                        // Increment login attempts
                        $attempts = $user['login_attempts'] + 1;
                        $lockUntil = null;
                        
                        if ($attempts >= 5) {
                            $lockUntil = date('Y-m-d H:i:s', time() + 1800); // Lock for 30 minutes
                            $error = 'Too many failed login attempts. Account locked for 30 minutes.';
                        } else {
                            $error = 'Invalid email or password. ' . (5 - $attempts) . ' attempts remaining.';
                        }
                        
                        $stmt = $pdo->prepare("UPDATE users SET login_attempts = :attempts, locked_until = :locked WHERE id = :id");
                        $stmt->execute([
                            ':attempts' => $attempts,
                            ':locked' => $lockUntil,
                            ':id' => $user['id']
                        ]);
                    }
                }
            }
        } catch (Exception $e) {
            $error = 'Login failed. Please try again.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Login · NepaEstate</title>
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
    
    <!-- Login Form -->
    <main class="register-main">
      <div class="register-card">
        <div class="card-header">
          <div class="brand-section">
            <div class="brand-logo-3d"></div>
            <h1 class="brand-title">Welcome Back</h1>
            <p class="brand-subtitle">Sign in to your NepaEstate account</p>
          </div>
        </div>
        
        <div class="card-body">
          <!-- Error Messages -->
          <?php if($error): ?>
            <div class="alert alert-error">
              <div class="alert-icon">❌</div>
              <div class="alert-content">
                <div class="alert-title">Login Failed</div>
                <div class="alert-message"><?=$error?></div>
              </div>
            </div>
          <?php endif; ?>
          
          <!-- Login Form -->
          <form method="post" class="register-form">
            <div class="form-group">
              <label class="form-label">Email Address</label>
              <div class="input-wrapper">
                <input class="form-input" type="email" name="email" required 
                       placeholder="your.email@example.com"
                       value="<?=htmlspecialchars($_POST['email'] ?? '')?>"/>
                <div class="input-icon">📧</div>
              </div>
            </div>
            
            <div class="form-group">
              <label class="form-label">Password</label>
              <div class="input-wrapper">
                <input class="form-input" type="password" name="password" required 
                       placeholder="Enter your password"/>
                <div class="input-icon">🔒</div>
                <button type="button" class="password-toggle" onclick="togglePassword('password')">👁️</button>
              </div>
            </div>
            
            <div class="form-actions">
              <button type="submit" class="btn-register">
                <span class="btn-text">Sign In</span>
                <div class="btn-bg"></div>
              </button>
            </div>
          </form>
          
          <div class="form-footer">
            <p class="login-link">
              <a href="forgot-password.php" class="link-primary">Forgot your password?</a>
            </p>
            <p class="login-link">
              Don't have an account? 
              <a href="register.php" class="link-primary">Register here</a>
            </p>
            <p class="login-link">
              <a href="index.php" class="link-primary">← Back to Home</a>
            </p>
          </div>
        </div>
      </div>
      
      <!-- Features Sidebar -->
      <div class="features-sidebar">
        <h3 class="features-title">🏔️ Your NepaEstate Account</h3>
        <div class="feature-list">
          <div class="feature-item">
            <div class="feature-icon">👤</div>
            <div class="feature-content">
              <h4>Personal Profile</h4>
              <p>Manage your account and preferences</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">🏠</div>
            <div class="feature-content">
              <h4>Property Management</h4>
              <p>List, edit, and track your properties</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">💰</div>
            <div class="feature-content">
              <h4>Offers & Deals</h4>
              <p>View and manage your property offers</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">🛡️</div>
            <div class="feature-content">
              <h4>Secure & Verified</h4>
              <p>KYC verification for trusted transactions</p>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
  
  <script>
    // Password toggle functionality
    function togglePassword(fieldName) {
      const field = document.querySelector(`input[name="${fieldName}"]`);
      const toggle = field.parentNode.querySelector('.password-toggle');
      
      if (field.type === 'password') {
        field.type = 'text';
        toggle.textContent = '🙈';
      } else {
        field.type = 'password';
        toggle.textContent = '👁️';
      }
    }
  </script>
</body>
</html>
