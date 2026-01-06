<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/user_auth.php';

// Check if user is logged in
require_user_login();

$pdo = get_pdo();
$userId = (int)$_SESSION['user_id'];
$success = null;
$error = null;

// Get user data
$user = get_logged_in_user();
if (!$user) {
    header('Location: login.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($name)) {
        $error = 'Name is required.';
    } else {
        try {
            // Handle profile image upload
            $profileImage = $user['profile_image']; // Keep existing image by default
            if (!empty($_FILES['profile_image']['name']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/uploads/profiles/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileExt = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif'])) {
                    if ($_FILES['profile_image']['size'] <= 2097152) { // 2MB
                        $newImageName = 'uploads/profiles/' . uniqid() . '.' . $fileExt;
                        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], __DIR__ . '/' . $newImageName)) {
                            $profileImage = $newImageName;
                        }
                    } else {
                        $error = 'Profile image must be less than 2MB.';
                    }
                } else {
                    $error = 'Profile image must be JPG, PNG, or GIF.';
                }
            }
            
            if (!$error) {
                // Update user profile
                $stmt = $pdo->prepare("UPDATE users SET name = :name, phone = :phone, profile_image = :image WHERE id = :id");
                $result = $stmt->execute([
                    ':name' => $name,
                    ':phone' => $phone ?: null,
                    ':image' => $profileImage,
                    ':id' => $userId
                ]);
                
                if ($result) {
                                           $success = 'Profile updated successfully!';
                       // Refresh user data
                       $user = get_logged_in_user();
                } else {
                    $error = 'Failed to update profile. Please try again.';
                }
            }
        } catch (Exception $e) {
            $error = 'Failed to update profile. Please try again.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Edit Profile · NepaEstate</title>
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
    
    <!-- Edit Profile Form -->
    <main class="register-main">
      <div class="register-card">
        <div class="card-header">
          <div class="brand-section">
            <div class="brand-logo-3d">✏️</div>
            <h1 class="brand-title">✏️ Edit Profile</h1>
            <p class="brand-subtitle">🔄 Update your profile information</p>
          </div>
        </div>
        
        <div class="card-body">
          <!-- Success/Error Messages -->
          <?php if($success): ?>
            <div class="alert alert-success">
              <div class="alert-icon">✅</div>
              <div class="alert-content">
                <div class="alert-title">Profile Updated!</div>
                <div class="alert-message"><?=$success?></div>
                <div style="margin-top:16px">
                  <a href="profile.php" class="alert-link" style="background:var(--accent);color:white;padding:8px 16px;border-radius:6px;text-decoration:none;margin-right:8px">View Profile →</a>
                  <a href="index.php" class="alert-link">Back to Home →</a>
                </div>
              </div>
            </div>
          <?php endif; ?>
          
          <?php if($error): ?>
            <div class="alert alert-error">
              <div class="alert-icon">❌</div>
              <div class="alert-content">
                <div class="alert-title">Update Failed</div>
                <div class="alert-message"><?=$error?></div>
              </div>
            </div>
          <?php endif; ?>
          
          <!-- Edit Profile Form -->
          <?php if(!$success): ?>
          <form method="post" class="register-form" enctype="multipart/form-data">
            <div class="form-group">
              <label class="form-label">👤 Full Name</label>
              <div class="input-wrapper">
                <input class="form-input" type="text" name="name" required 
                       placeholder="Enter your beautiful name" 
                       value="<?=htmlspecialchars($user['name'])?>"/>
                <div class="input-icon">✨</div>
              </div>
            </div>
            
            <div class="form-group">
              <label class="form-label">📱 Phone Number</label>
              <div class="input-wrapper">
                <input class="form-input" type="tel" name="phone" 
                       placeholder="+977 98XX-XXXXXX (optional)"
                       value="<?=htmlspecialchars($user['phone'] ?? '')?>"/>
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
                    <div class="file-upload-title">📱 Upload New Photo</div>
                    <div class="file-upload-desc">✨ JPG, PNG or GIF (max 2MB)</div>
                  </div>
                </label>
                <?php if($user['profile_image']): ?>
                  <div style="margin-top:16px;text-align:center">
                    <strong>Current Photo:</strong><br>
                    <img src="<?=$user['profile_image']?>" alt="Current Profile" style="width:100px;height:100px;border-radius:50%;object-fit:cover;margin-top:8px;border:3px solid var(--accent)">
                  </div>
                <?php endif; ?>
                <div class="file-preview" id="profile-preview"></div>
              </div>
            </div>
            
            <div class="form-actions">
              <button type="submit" class="btn-register">
                <span class="btn-text">💾 Save Changes</span>
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
        <h3 class="features-title">🔧 Profile Management</h3>
        <div class="feature-list">
          <div class="feature-item">
            <div class="feature-icon">👤</div>
            <div class="feature-content">
              <h4>Personal Info</h4>
              <p>Update your name and contact details</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">📸</div>
            <div class="feature-content">
              <h4>Profile Photo</h4>
              <p>Change your profile picture anytime</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">🔒</div>
            <div class="feature-content">
              <h4>Secure Updates</h4>
              <p>All changes are saved securely</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">⚡</div>
            <div class="feature-content">
              <h4>Instant Changes</h4>
              <p>Updates take effect immediately</p>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
  
  <style>
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
    // File preview
    document.getElementById('profile_image').addEventListener('change', function(e) {
      const file = e.target.files[0];
      const preview = document.getElementById('profile-preview');
      
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width:100px;max-height:100px;border-radius:8px;object-fit:cover;margin-top:8px;">`;
          preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
      }
    });
    
    // Form validation
    document.querySelector('.register-form').addEventListener('submit', function(e) {
      const name = document.querySelector('input[name="name"]').value.trim();
      
      if (name.length < 2) {
        e.preventDefault();
        alert('Name must be at least 2 characters long!');
        return false;
      }
      
      // Add loading state to button
      const btn = document.querySelector('.btn-register');
      btn.classList.add('loading');
      btn.querySelector('.btn-text').textContent = '💾 Saving...';
    });
  </script>
</body>
</html>
