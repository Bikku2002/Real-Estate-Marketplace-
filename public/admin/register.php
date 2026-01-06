<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';

$success = null;
$error = null;

if(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'){
  $name = trim((string)($_POST['name'] ?? ''));
  $email = trim((string)($_POST['email'] ?? ''));
  $phone = trim((string)($_POST['phone'] ?? ''));
  $password = (string)($_POST['password'] ?? '');
  $confirmPassword = (string)($_POST['confirm_password'] ?? '');
  $role = $_POST['role'] ?? 'buyer';
  
  // Validation
  if(empty($name) || empty($email) || empty($password)) {
    $error = 'Name, email, and password are required';
  } elseif($password !== $confirmPassword) {
    $error = 'Passwords do not match';
  } elseif(strlen($password) < 6) {
    $error = 'Password must be at least 6 characters';
  } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Please enter a valid email address';
  } else {
    try {
      $pdo = get_pdo();
      
      // Check if email already exists
      $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
      $stmt->execute([':email' => $email]);
      if($stmt->fetch()) {
        $error = 'Email already exists. Please use a different email.';
      } else {
        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users(name, email, phone, role, password_hash) VALUES(:n, :e, :p, :r, :h)");
        $stmt->execute([
          ':n' => $name,
          ':e' => $email,
          ':p' => $phone ?: null,
          ':r' => $role,
          ':h' => password_hash($password, PASSWORD_BCRYPT)
        ]);
        $success = 'Account created successfully! You can now login.';
      }
    } catch(Exception $e) {
      $error = 'Registration failed. Please try again.';
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Register · NepaEstate</title>
  <link rel="stylesheet" href="../assets/css/styles.css"/>
  <link rel="stylesheet" href="../assets/css/register.css"/>
</head>
<body class="register-body">
  <div class="register-container">
    <!-- Background Animation -->
    <div class="bg-animation">
      <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
        <div class="shape shape-5"></div>
      </div>
    </div>
    
    <!-- Register Card -->
    <main class="register-main">
      <div class="register-card">
        <div class="card-header">
          <div class="brand-section">
            <div class="brand-logo-3d"></div>
            <h1 class="brand-title">Join NepaEstate</h1>
            <p class="brand-subtitle">Start your real estate journey in Nepal</p>
          </div>
        </div>
        
        <div class="card-body">
          <!-- Success/Error Messages -->
          <?php if($success): ?>
            <div class="alert alert-success">
              <div class="alert-icon">✅</div>
              <div class="alert-content">
                <div class="alert-title">Success!</div>
                <div class="alert-message"><?=$success?></div>
                <a href="login.php" class="alert-link">Go to Login →</a>
              </div>
            </div>
          <?php endif; ?>
          
          <?php if($error): ?>
            <div class="alert alert-error">
              <div class="alert-icon">❌</div>
              <div class="alert-content">
                <div class="alert-title">Error!</div>
                <div class="alert-message"><?=$error?></div>
              </div>
            </div>
          <?php endif; ?>
          
          <!-- Registration Form -->
          <form method="post" class="register-form" id="registerForm">
            <div class="form-group">
              <label class="form-label">Full Name</label>
              <div class="input-wrapper">
                <input class="form-input" type="text" name="name" required 
                       placeholder="Enter your full name" 
                       value="<?=htmlspecialchars($_POST['name'] ?? '')?>"/>
                <div class="input-icon">👤</div>
              </div>
            </div>
            
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
              <label class="form-label">Phone Number</label>
              <div class="input-wrapper">
                <input class="form-input" type="tel" name="phone" 
                       placeholder="+977 98XX-XXXXXX (optional)"
                       value="<?=htmlspecialchars($_POST['phone'] ?? '')?>"/>
                <div class="input-icon">📱</div>
              </div>
            </div>
            
            <div class="form-group">
              <label class="form-label">Account Type</label>
              <div class="role-selector">
                <div class="role-option">
                  <input type="radio" name="role" value="buyer" id="buyer" 
                         <?=($_POST['role'] ?? 'buyer') === 'buyer' ? 'checked' : ''?>/>
                  <label for="buyer" class="role-label">
                    <div class="role-icon">🏠</div>
                    <div class="role-content">
                      <div class="role-title">Buyer</div>
                      <div class="role-desc">Looking to buy property</div>
                    </div>
                  </label>
                </div>
                <div class="role-option">
                  <input type="radio" name="role" value="seller" id="seller"
                         <?=($_POST['role'] ?? '') === 'seller' ? 'checked' : ''?>/>
                  <label for="seller" class="role-label">
                    <div class="role-icon">💼</div>
                    <div class="role-content">
                      <div class="role-title">Seller</div>
                      <div class="role-desc">Want to sell property</div>
                    </div>
                  </label>
                </div>
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-wrapper">
                  <input class="form-input" type="password" name="password" required 
                         placeholder="Create password" minlength="6"/>
                  <div class="input-icon">🔒</div>
                  <button type="button" class="password-toggle" onclick="togglePassword('password')">👁️</button>
                </div>
              </div>
              
              <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <div class="input-wrapper">
                  <input class="form-input" type="password" name="confirm_password" required 
                         placeholder="Confirm password" minlength="6"/>
                  <div class="input-icon">🔒</div>
                  <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">👁️</button>
                </div>
              </div>
            </div>
            
            <div class="form-actions">
              <button type="submit" class="btn-register">
                <span class="btn-text">Create Account</span>
                <div class="btn-bg"></div>
              </button>
            </div>
          </form>
          
          <div class="form-footer">
            <p class="login-link">
              Already have an account? 
              <a href="login.php" class="link-primary">Sign in here</a>
            </p>
          </div>
        </div>
      </div>
      
      <!-- Features Sidebar -->
      <div class="features-sidebar">
        <h3 class="features-title">Why join NepaEstate?</h3>
        <div class="feature-list">
          <div class="feature-item">
            <div class="feature-icon">🏔️</div>
            <div class="feature-content">
              <h4>Local Expertise</h4>
              <p>Access to properties across all 77 districts of Nepal</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">💰</div>
            <div class="feature-content">
              <h4>Transparent Pricing</h4>
              <p>Fair market values with budget calculator tools</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">🤝</div>
            <div class="feature-content">
              <h4>Secure Platform</h4>
              <p>Verified listings and secure communication</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">📱</div>
            <div class="feature-content">
              <h4>Real-time Updates</h4>
              <p>Live notifications for new opportunities</p>
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
    
    // Form validation and enhancement
    document.getElementById('registerForm').addEventListener('submit', function(e) {
      const password = document.querySelector('input[name="password"]').value;
      const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
      
      if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
        return false;
      }
      
      if (password.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long!');
        return false;
      }
      
      // Add loading state to button
      const btn = document.querySelector('.btn-register');
      btn.classList.add('loading');
      btn.querySelector('.btn-text').textContent = 'Creating Account...';
    });
    
    // Enhanced form interactions
    document.querySelectorAll('.form-input').forEach(input => {
      input.addEventListener('focus', function() {
        this.parentNode.parentNode.classList.add('focused');
      });
      
      input.addEventListener('blur', function() {
        this.parentNode.parentNode.classList.remove('focused');
        if (this.value) {
          this.parentNode.parentNode.classList.add('filled');
        } else {
          this.parentNode.parentNode.classList.remove('filled');
        }
      });
    });
  </script>
</body>
</html>
