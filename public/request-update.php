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
    $requestType = trim($_POST['request_type'] ?? '');
    $currentValue = trim($_POST['current_value'] ?? '');
    $newValue = trim($_POST['new_value'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    
    if (empty($requestType) || empty($newValue) || empty($reason)) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            // Check if there's already a pending request for this type
            $stmt = $pdo->prepare("SELECT id FROM profile_update_requests WHERE user_id = :user_id AND request_type = :type AND status = 'pending'");
            $stmt->execute([':user_id' => $userId, ':type' => $requestType]);
            
            if ($stmt->fetch()) {
                $error = 'You already have a pending request for this update. Please wait for admin approval.';
            } else {
                // Insert the update request
                $stmt = $pdo->prepare("INSERT INTO profile_update_requests (user_id, request_type, current_value, new_value, reason, status) VALUES (:user_id, :type, :current, :new, :reason, 'pending')");
                $result = $stmt->execute([
                    ':user_id' => $userId,
                    ':type' => $requestType,
                    ':current' => $currentValue,
                    ':new' => $newValue,
                    ':reason' => $reason
                ]);
                
                if ($result) {
                    $success = 'Update request submitted successfully! An admin will review your request soon.';
                } else {
                    $error = 'Failed to submit request. Please try again.';
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
  <title>Request Profile Update · NepaEstate</title>
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
    
    <!-- Request Update Form -->
    <main class="register-main">
      <div class="register-card">
        <div class="card-header">
          <div class="brand-section">
            <div class="brand-logo-3d">📝</div>
            <h1 class="brand-title">📝 Request Profile Update</h1>
            <p class="brand-subtitle">🔄 Request changes to your profile information</p>
          </div>
        </div>
        
        <div class="card-body">
          <!-- Success/Error Messages -->
          <?php if($success): ?>
            <div class="alert alert-success">
              <div class="alert-icon">✅</div>
              <div class="alert-content">
                <div class="alert-title">Request Submitted!</div>
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
                <div class="alert-title">Request Failed</div>
                <div class="alert-message"><?=$error?></div>
              </div>
            </div>
          <?php endif; ?>
          
          <!-- Request Update Form -->
          <?php if(!$success): ?>
          <form method="post" class="register-form">
            <div class="form-group">
              <label class="form-label">📋 What do you want to update?</label>
              <select class="form-input" name="request_type" required>
                <option value="">Select update type</option>
                <option value="name">Full Name</option>
                <option value="phone">Phone Number</option>
                <option value="email">Email Address</option>
                <option value="role">Account Type (Buyer/Seller)</option>
                <option value="other">Other Information</option>
              </select>
            </div>
            
            <div class="form-group">
              <label class="form-label">📝 Current Value</label>
              <div class="input-wrapper">
                <input class="form-input" type="text" name="current_value" 
                       placeholder="What is the current value?" 
                       value="<?=htmlspecialchars($_POST['current_value'] ?? '')?>"/>
                <div class="input-icon">📋</div>
              </div>
            </div>
            
            <div class="form-group">
              <label class="form-label">🆕 New Value</label>
              <div class="input-wrapper">
                <input class="form-input" type="text" name="new_value" required 
                       placeholder="What should it be changed to?" 
                       value="<?=htmlspecialchars($_POST['new_value'] ?? '')?>"/>
                <div class="input-icon">✨</div>
              </div>
            </div>
            
            <div class="form-group">
              <label class="form-label">💭 Reason for Change</label>
              <div class="input-wrapper">
                <textarea class="form-input" name="reason" required rows="4"
                          placeholder="Please explain why you need this change..."><?=htmlspecialchars($_POST['reason'] ?? '')?></textarea>
                <div class="input-icon">💭</div>
              </div>
            </div>
            
            <div class="info-box">
              <div class="info-icon">ℹ️</div>
              <div class="info-content">
                <h4>How it works:</h4>
                <ul>
                  <li>Submit your update request with details</li>
                  <li>Admin will review your request</li>
                  <li>You'll be notified when approved/rejected</li>
                  <li>Changes take effect after admin approval</li>
                </ul>
              </div>
            </div>
            
            <div class="form-actions">
              <button type="submit" class="btn-register">
                <span class="btn-text">📤 Submit Request</span>
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
        <h3 class="features-title">📋 Update Requests</h3>
        <div class="feature-list">
          <div class="feature-item">
            <div class="feature-icon">🔒</div>
            <div class="feature-content">
              <h4>Secure Process</h4>
              <p>All changes are reviewed by admin for security</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">📝</div>
            <div class="feature-content">
              <h4>Track Progress</h4>
              <p>Monitor your request status in your profile</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">⚡</div>
            <div class="feature-content">
              <h4>Quick Review</h4>
              <p>Most requests are reviewed within 24 hours</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">✅</div>
            <div class="feature-content">
              <h4>Instant Updates</h4>
              <p>Changes take effect immediately after approval</p>
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
    
    textarea.form-input {
      resize: vertical;
      min-height: 100px;
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
      const requestType = document.querySelector('select[name="request_type"]').value;
      const newValue = document.querySelector('input[name="new_value"]').value.trim();
      const reason = document.querySelector('textarea[name="reason"]').value.trim();
      
      if (!requestType) {
        e.preventDefault();
        alert('Please select what you want to update!');
        return false;
      }
      
      if (newValue.length < 2) {
        e.preventDefault();
        alert('New value must be at least 2 characters long!');
        return false;
      }
      
      if (reason.length < 10) {
        e.preventDefault();
        alert('Please provide a detailed reason (at least 10 characters)!');
        return false;
      }
      
      // Add loading state to button
      const btn = document.querySelector('.btn-register');
      btn.classList.add('loading');
      btn.querySelector('.btn-text').textContent = '📤 Submitting...';
    });
    
    // Auto-fill current value based on request type
    document.querySelector('select[name="request_type"]').addEventListener('change', function() {
      const currentValueField = document.querySelector('input[name="current_value"]');
      const type = this.value;
      
      switch(type) {
        case 'name':
          currentValueField.value = '<?=htmlspecialchars($user['name'])?>';
          break;
        case 'phone':
          currentValueField.value = '<?=htmlspecialchars($user['phone'] ?? 'Not provided')?>';
          break;
        case 'email':
          currentValueField.value = '<?=htmlspecialchars($user['email'])?>';
          break;
        case 'role':
          currentValueField.value = '<?=ucfirst($user['role'])?>';
          break;
        default:
          currentValueField.value = '';
      }
    });
  </script>
</body>
</html>
