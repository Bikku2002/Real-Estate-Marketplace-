<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';

$success = null;
$error = null;

if(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'){
  $name = trim((string)($_POST['name'] ?? ''));
  $email = trim((string)($_POST['email'] ?? ''));
  $phone = trim((string)($_POST['phone'] ?? ''));
  $password = (string)($_POST['password'] ?? '');
  $confirmPassword = (string)($_POST['confirm_password'] ?? '');
  $role = $_POST['role'] ?? 'buyer';
  $kycDocType = $_POST['kyc_document_type'] ?? null;
  $kycDocNumber = trim((string)($_POST['kyc_document_number'] ?? ''));
  
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
        // Create uploads directory if it doesn't exist
        $uploadDir = __DIR__ . '/uploads/profiles/';
        $kycUploadDir = __DIR__ . '/uploads/kyc/';
        
        // Create directories with proper permissions
        if (!is_dir($uploadDir)) {
          if (!mkdir($uploadDir, 0755, true)) {
            $error = 'Failed to create upload directory. Please contact admin.';
            throw new Exception('Directory creation failed');
          }
        }
        if (!is_dir($kycUploadDir)) {
          if (!mkdir($kycUploadDir, 0755, true)) {
            $error = 'Failed to create KYC upload directory. Please contact admin.';
            throw new Exception('KYC directory creation failed');
          }
        }
        
        $profileImage = null;
        $kycDocImage = null;
        $uploadError = false;
        
        // Handle profile image upload
        if (!empty($_FILES['profile_image']['name']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
          $profileExt = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
          if (in_array($profileExt, ['jpg', 'jpeg', 'png', 'gif'])) {
            if ($_FILES['profile_image']['size'] <= 2097152) { // 2MB
              $profileImage = 'uploads/profiles/' . uniqid() . '.' . $profileExt;
              if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], __DIR__ . '/' . $profileImage)) {
                $profileImage = null; // Reset if upload failed
              }
            } else {
              $error = 'Profile image must be less than 2MB.';
              $uploadError = true;
            }
          } else {
            $error = 'Profile image must be JPG, PNG, or GIF.';
            $uploadError = true;
          }
        }
        
        // Handle KYC document upload
        if (!empty($_FILES['kyc_document']['name']) && $_FILES['kyc_document']['error'] === UPLOAD_ERR_OK && $kycDocType && $kycDocNumber) {
          $kycExt = strtolower(pathinfo($_FILES['kyc_document']['name'], PATHINFO_EXTENSION));
          if (in_array($kycExt, ['jpg', 'jpeg', 'png', 'pdf'])) {
            if ($_FILES['kyc_document']['size'] <= 5242880) { // 5MB
              $kycDocImage = 'uploads/kyc/' . uniqid() . '.' . $kycExt;
              if (!move_uploaded_file($_FILES['kyc_document']['tmp_name'], __DIR__ . '/' . $kycDocImage)) {
                $kycDocImage = null; // Reset if upload failed
              }
            } else {
              $error = 'KYC document must be less than 5MB.';
              $uploadError = true;
            }
          } else {
            $error = 'KYC document must be JPG, PNG, or PDF.';
            $uploadError = true;
          }
        }
        
        // Only proceed if no upload errors
        if (!$uploadError && !$error) {
          // Insert new user
          $stmt = $pdo->prepare("INSERT INTO users(name, email, phone, role, password_hash, profile_image, kyc_document_type, kyc_document_number, kyc_document_image) VALUES(:n, :e, :p, :r, :h, :pi, :kdt, :kdn, :kdi)");
          $result = $stmt->execute([
            ':n' => $name,
            ':e' => $email,
            ':p' => $phone ?: null,
            ':r' => $role,
            ':h' => password_hash($password, PASSWORD_BCRYPT),
            ':pi' => $profileImage,
            ':kdt' => $kycDocType ?: null,
            ':kdn' => $kycDocNumber ?: null,
            ':kdi' => $kycDocImage
          ]);
          
          if ($result) {
            $success = 'Account created successfully! Welcome to NepaEstate.';
            if ($kycDocType && $kycDocImage) {
              $success .= ' Your KYC verification is pending review.';
            }
          } else {
            $error = 'Database error occurred. Please try again.';
          }
        }
      }
    } catch(Exception $e) {
      // More detailed error for debugging
      $error = 'Registration failed: ' . $e->getMessage();
      // For production, use: $error = 'Registration failed. Please try again.';
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Join NepaEstate · Create Your Account</title>
  <meta name="description" content="Join NepaEstate and start your real estate journey in Nepal. Buy or sell properties with confidence."/>
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
        <div class="shape shape-4"></div>
        <div class="shape shape-5"></div>
      </div>
    </div>
    
    <!-- Register Card -->
    <main class="register-main">
      <div class="register-card">
        <div class="card-header">
          <div class="brand-section">
            <div class="brand-logo-3d">🏔️</div>
            <h1 class="brand-title">🌟 Join NepaEstate</h1>
            <p class="brand-subtitle">🚀 Start your real estate journey in Nepal today!</p>
          </div>
        </div>
        
        <div class="card-body">
          <!-- Success/Error Messages -->
          <?php if($success): ?>
            <div class="alert alert-success">
              <div class="alert-icon">✅</div>
              <div class="alert-content">
                <div class="alert-title">Welcome to NepaEstate!</div>
                <div class="alert-message"><?=$success?></div>
                <a href="index.php" class="alert-link">Explore Properties →</a>
              </div>
            </div>
          <?php endif; ?>
          
          <?php if($error): ?>
            <div class="alert alert-error">
              <div class="alert-icon">❌</div>
              <div class="alert-content">
                <div class="alert-title">Registration Error</div>
                <div class="alert-message"><?=$error?></div>
              </div>
            </div>
          <?php endif; ?>
          
          <!-- Registration Form -->
          <form method="post" class="register-form" id="registerForm" enctype="multipart/form-data">
            <div class="form-group">
              <label class="form-label">👤 Full Name</label>
              <div class="input-wrapper">
                <input class="form-input" type="text" name="name" required 
                       placeholder="Enter your beautiful name" 
                       value="<?=htmlspecialchars($_POST['name'] ?? '')?>"/>
                <div class="input-icon">✨</div>
              </div>
            </div>
            
            <div class="form-group">
              <label class="form-label">📧 Email Address</label>
              <div class="input-wrapper">
                <input class="form-input" type="email" name="email" required 
                       placeholder="your.awesome@email.com"
                       value="<?=htmlspecialchars($_POST['email'] ?? '')?>"/>
                <div class="input-icon">💌</div>
              </div>
            </div>
            
            <div class="form-group">
              <label class="form-label">📱 Phone Number</label>
              <div class="input-wrapper">
                <input class="form-input" type="tel" name="phone" 
                       placeholder="+977 98XX-XXXXXX (optional)"
                       value="<?=htmlspecialchars($_POST['phone'] ?? '')?>"/>
                <div class="input-icon">📞</div>
              </div>
            </div>
            
            <div class="form-group">
              <label class="form-label">📸 Profile Picture</label>
              <div class="file-upload-wrapper">
                <input type="file" name="profile_image" id="profile_image" class="file-input" accept="image/*">
                <label for="profile_image" class="file-upload-label">
                  <div class="file-upload-icon">🖼️</div>
                  <div class="file-upload-text">
                    <div class="file-upload-title">📱 Upload Your Best Photo</div>
                    <div class="file-upload-desc">✨ JPG, PNG or GIF (max 2MB)</div>
                  </div>
                </label>
                <div class="file-preview" id="profile-preview"></div>
              </div>
            </div>
            
            <div class="form-group">
              <label class="form-label">🎯 I want to...</label>
              <div class="role-selector">
                <div class="role-option">
                  <input type="radio" name="role" value="buyer" id="buyer" 
                         <?=($_POST['role'] ?? 'buyer') === 'buyer' ? 'checked' : ''?>/>
                  <label for="buyer" class="role-label">
                    <div class="role-icon">🏠</div>
                    <div class="role-content">
                      <div class="role-title">🏠 Buy Property</div>
                      <div class="role-desc">🔍 Looking for your dream land or house</div>
                    </div>
                  </label>
                </div>
                <div class="role-option">
                  <input type="radio" name="role" value="seller" id="seller"
                         <?=($_POST['role'] ?? '') === 'seller' ? 'checked' : ''?>/>
                  <label for="seller" class="role-label">
                    <div class="role-icon">💼</div>
                    <div class="role-content">
                      <div class="role-title">💼 Sell Property</div>
                      <div class="role-desc">💰 Have property to sell? Let's find buyers!</div>
                    </div>
                  </label>
                </div>
              </div>
            </div>
            
            <!-- KYC Verification Section -->
            <div class="kyc-section">
              <h3 class="kyc-title">🛡️ Identity Verification (Optional)</h3>
              <p class="kyc-subtitle">🔐 Verify your identity to build trust and unlock premium features</p>
              
              <div class="form-group">
                <label class="form-label">📋 Document Type</label>
                <select class="form-input" name="kyc_document_type" id="kyc_document_type">
                  <option value="">🔽 Select document type</option>
                  <option value="citizenship">🆔 Nepali Citizenship</option>
                  <option value="passport">📘 Passport</option>
                  <option value="license">🚗 Driving License</option>
                </select>
              </div>
              
              <div class="form-group" id="kyc_number_group" style="display:none">
                <label class="form-label">🔢 Document Number</label>
                <div class="input-wrapper">
                  <input class="form-input" type="text" name="kyc_document_number" 
                         placeholder="Enter your document number"
                         value="<?=htmlspecialchars($_POST['kyc_document_number'] ?? '')?>"/>
                  <div class="input-icon">🔢</div>
                </div>
              </div>
              
              <div class="form-group" id="kyc_upload_group" style="display:none">
                <label class="form-label">📄 Document Photo</label>
                <div class="file-upload-wrapper">
                  <input type="file" name="kyc_document" id="kyc_document" class="file-input" accept="image/*,.pdf">
                  <label for="kyc_document" class="file-upload-label kyc-upload">
                    <div class="file-upload-icon">📁</div>
                    <div class="file-upload-text">
                      <div class="file-upload-title">📤 Upload Your Document</div>
                      <div class="file-upload-desc">📸 Clear photo or PDF (max 5MB)</div>
                    </div>
                  </label>
                  <div class="file-preview" id="kyc-preview"></div>
                </div>
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">🔐 Password</label>
                <div class="input-wrapper">
                  <input class="form-input" type="password" name="password" required 
                         placeholder="Create a super secure password" minlength="6"/>
                  <div class="input-icon">🔒</div>
                  <button type="button" class="password-toggle" onclick="togglePassword('password')">👁️</button>
                </div>
              </div>
              
              <div class="form-group">
                <label class="form-label">🔐 Confirm Password</label>
                <div class="input-wrapper">
                  <input class="form-input" type="password" name="confirm_password" required 
                         placeholder="Confirm your password" minlength="6"/>
                  <div class="input-icon">🔒</div>
                  <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">👁️</button>
                </div>
              </div>
            </div>
            
            <div class="form-actions">
              <button type="submit" class="btn-register">
                <span class="btn-text">🚀 Create My Account</span>
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
        <h3 class="features-title">🌟 Why join NepaEstate?</h3>
        <div class="feature-list">
          <div class="feature-item">
            <div class="feature-icon">🏔️</div>
            <div class="feature-content">
              <h4>🗺️ Nepal-wide Reach</h4>
              <p>🌍 Access properties across all 77 districts from Terai to Himalayas</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">💰</div>
            <div class="feature-content">
              <h4>💎 Fair Pricing</h4>
              <p>📊 Transparent market values and smart negotiation tools</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">🛡️</div>
            <div class="feature-content">
              <h4>✅ Identity Verified</h4>
              <p>🔐 KYC verification for trusted and secure transactions</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">📱</div>
            <div class="feature-content">
              <h4>🧠 Smart Tools</h4>
              <p>⚡ Budget calculator, live updates, and AI-powered insights</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">🎯</div>
            <div class="feature-content">
              <h4>🚀 Fast & Easy</h4>
              <p>⚡ Quick registration, instant access, and seamless experience</p>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
  
  <style>
    /* Enhanced styling for better visual appeal */
    .brand-logo-3d {
      font-size: 48px;
      text-align: center;
      margin-bottom: 16px;
      animation: float 3s ease-in-out infinite;
    }
    
    @keyframes float {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-10px); }
    }
    
    .form-label {
      font-weight: 600;
      color: var(--ink);
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .input-wrapper {
      position: relative;
      transition: all 0.3s ease;
    }
    
    .input-wrapper:hover {
      transform: translateY(-2px);
    }
    
    .form-input:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(215, 38, 61, 0.1);
    }
    
    .role-selector {
      display: grid;
      gap: 16px;
      margin-top: 12px;
    }
    
    .role-option {
      transition: all 0.3s ease;
    }
    
    .role-option:hover {
      transform: translateY(-2px);
    }
    
    .role-label {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 20px;
      background: var(--elev);
      border: 2px solid var(--line);
      border-radius: 16px;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .role-label:hover {
      border-color: var(--accent);
      background: rgba(215, 38, 61, 0.05);
    }
    
    .role-icon {
      font-size: 32px;
      width: 60px;
      height: 60px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, var(--accent), var(--brass));
      border-radius: 50%;
      color: white;
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
    
    .features-sidebar {
      background: linear-gradient(135deg, var(--elev), var(--bg));
      border: 1px solid var(--line);
      border-radius: 20px;
      padding: 32px;
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
    }
    
    .feature-item {
      padding: 20px;
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 16px;
      margin-bottom: 16px;
      transition: all 0.3s ease;
    }
    
    .feature-item:hover {
      transform: translateX(8px);
      border-color: var(--accent);
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    
    .feature-icon {
      font-size: 24px;
      margin-bottom: 12px;
    }
    
    .kyc-section {
      background: rgba(47,176,112,0.05);
      border: 1px solid rgba(47,176,112,0.2);
      border-radius: 16px;
      padding: 24px;
      margin: 24px 0;
    }
    
    .kyc-title {
      color: var(--accent);
      margin-bottom: 8px;
    }
    
    .file-upload-label {
      border: 2px dashed var(--accent);
      border-radius: 12px;
      padding: 24px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .file-upload-label:hover {
      border-color: var(--brass);
      background: rgba(215, 38, 61, 0.05);
    }
    
    .file-upload-icon {
      font-size: 32px;
      margin-bottom: 12px;
    }
    
    .file-upload-title {
      font-weight: 600;
      color: var(--ink);
      margin-bottom: 4px;
    }
    
    .file-upload-desc {
      color: var(--muted);
      font-size: 14px;
    }
  </style>
  
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
      btn.querySelector('.btn-text').textContent = '🚀 Creating Account...';
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
    
    // File upload handling
    function handleFileUpload(inputId, previewId) {
      const input = document.getElementById(inputId);
      const preview = document.getElementById(previewId);
      
      input.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
          const reader = new FileReader();
          reader.onload = function(e) {
            if (file.type.startsWith('image/')) {
              preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width:100px;max-height:100px;border-radius:8px;object-fit:cover;">`;
            } else {
              preview.innerHTML = `<div class="file-name">📄 ${file.name}</div>`;
            }
            preview.style.display = 'block';
          };
          reader.readAsDataURL(file);
        }
      });
    }
    
    // KYC form interactions
    document.getElementById('kyc_document_type').addEventListener('change', function() {
      const numberGroup = document.getElementById('kyc_number_group');
      const uploadGroup = document.getElementById('kyc_upload_group');
      
      if (this.value) {
        numberGroup.style.display = 'block';
        uploadGroup.style.display = 'block';
      } else {
        numberGroup.style.display = 'none';
        uploadGroup.style.display = 'none';
      }
    });
    
    // Auto-fill demo data (for testing)
    document.addEventListener('DOMContentLoaded', function() {
      handleFileUpload('profile_image', 'profile-preview');
      handleFileUpload('kyc_document', 'kyc-preview');
      
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('demo') === '1') {
        document.querySelector('input[name="name"]').value = 'John Doe';
        document.querySelector('input[name="email"]').value = 'john@example.com';
        document.querySelector('input[name="phone"]').value = '+977 9812345678';
        document.querySelector('input[name="password"]').value = 'password123';
        document.querySelector('input[name="confirm_password"]').value = 'password123';
      }
    });
  </script>
</body>
</html>
