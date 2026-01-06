<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/user_auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = get_pdo();
$userId = (int)$_SESSION['user_id'];

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Get user's properties if they're a seller
$userProperties = [];
if ($user['role'] === 'seller') {
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE seller_id = :id ORDER BY created_at DESC");
    $stmt->execute([':id' => $userId]);
    $userProperties = $stmt->fetchAll();
}

// Get user's offers if they're a buyer
$userOffers = [];
if ($user['role'] === 'buyer') {
    $stmt = $pdo->prepare("
        SELECT o.*, p.title as property_title, p.cover_image 
        FROM offers o 
        JOIN properties p ON o.property_id = p.id 
        WHERE o.buyer_id = :id 
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([':id' => $userId]);
    $userOffers = $stmt->fetchAll();
}

// Get pending update requests
$stmt = $pdo->prepare("SELECT * FROM profile_update_requests WHERE user_id = :id ORDER BY created_at DESC");
$stmt->execute([':id' => $userId]);
$updateRequests = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>My Profile · NepaEstate</title>
  <link rel="stylesheet" href="assets/css/styles.css"/>
  <link rel="stylesheet" href="assets/css/profile.css"/>
  
  <style>
    /* Ensure all links are clickable and properly styled */
    .nav-link, .action-btn, .btn {
      cursor: pointer !important;
      text-decoration: none !important;
      display: inline-block !important;
      transition: all 0.3s ease !important;
      position: relative !important;
      z-index: 10 !important;
    }
    
    .action-btn {
      display: block !important;
    }
    
    .nav-link:hover, .action-btn:hover, .btn:hover {
      transform: translateY(-2px) !important;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
    }
    
    /* Ensure proper z-index for clickable elements */
    .nav-actions, .profile-actions, .action-buttons {
      position: relative;
      z-index: 10;
    }
    
    /* Make sure all links are clickable */
    a[href] {
      pointer-events: auto !important;
      cursor: pointer !important;
    }
  </style>
</head>
<body>
  <header class="site-header">
    <div class="container nav">
      <a class="brand" href="index.php">
        <div class="brand-logo pulse"></div>
        <div class="brand-name">NepaEstate</div>
      </a>
      <div class="nav-actions">
        <a class="btn nav-link" href="index.php">🏠 Home</a>
        <a class="btn nav-link" href="contact.php">📞 Contact</a>
        <a class="btn btn-primary nav-link" href="profile.php">👤 My Profile</a>
        <a class="btn nav-link" href="logout.php">🚪 Logout</a>
      </div>
    </div>
  </header>

  <main class="profile-main">
    <div class="container">
      <div class="profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
          <div class="profile-avatar">
            <?php if($user['profile_image']): ?>
              <img src="<?=$user['profile_image']?>" alt="Profile Picture" class="avatar-image">
            <?php else: ?>
              <div class="avatar-placeholder">
                <span><?=strtoupper(substr($user['name'], 0, 1))?></span>
              </div>
            <?php endif; ?>
          </div>
          <div class="profile-info">
            <h1 class="profile-name"><?=htmlspecialchars($user['name'])?></h1>
            <div class="profile-meta">
              <span class="profile-role"><?=ucfirst($user['role'])?></span>
              <span class="profile-separator">•</span>
              <span class="profile-joined">Joined <?=date('M Y', strtotime($user['created_at']))?></span>
            </div>
            <div class="profile-kyc">
              <span class="kyc-badge kyc-<?=$user['kyc_status']?>">
                <?php if($user['kyc_status'] === 'verified'): ?>✅ Verified<?php endif; ?>
                <?php if($user['kyc_status'] === 'pending'): ?>🔄 KYC Pending<?php endif; ?>
                <?php if($user['kyc_status'] === 'rejected'): ?>❌ KYC Rejected<?php endif; ?>
              </span>
            </div>
          </div>
          <div class="profile-actions">
            <a href="edit-profile.php" class="btn btn-primary">✏️ Edit Profile</a>
            <a href="request-update.php" class="btn">📝 Request Changes</a>
          </div>
        </div>

        <!-- Profile Content -->
        <div class="profile-content">
          <!-- Personal Information -->
          <div class="profile-section">
            <h2 class="section-title">📋 Personal Information</h2>
            <div class="info-grid">
              <div class="info-item">
                <div class="info-label">Email Address</div>
                <div class="info-value"><?=htmlspecialchars($user['email'])?></div>
              </div>
              <div class="info-item">
                <div class="info-label">Phone Number</div>
                <div class="info-value"><?=htmlspecialchars($user['phone'] ?: 'Not provided')?></div>
              </div>
              <div class="info-item">
                <div class="info-label">Account Type</div>
                <div class="info-value"><?=ucfirst($user['role'])?></div>
              </div>
              <div class="info-item">
                <div class="info-label">Member Since</div>
                <div class="info-value"><?=date('F j, Y', strtotime($user['created_at']))?></div>
              </div>
            </div>
          </div>

          <!-- KYC Information -->
          <?php if($user['kyc_document_type']): ?>
          <div class="profile-section">
            <h2 class="section-title">🛡️ Identity Verification</h2>
            <div class="info-grid">
              <div class="info-item">
                <div class="info-label">Document Type</div>
                <div class="info-value"><?=ucfirst($user['kyc_document_type'])?></div>
              </div>
              <div class="info-item">
                <div class="info-label">Document Number</div>
                <div class="info-value"><?=htmlspecialchars($user['kyc_document_number'])?></div>
              </div>
              <div class="info-item">
                <div class="info-label">Verification Status</div>
                <div class="info-value">
                  <span class="kyc-badge kyc-<?=$user['kyc_status']?>">
                    <?=ucfirst($user['kyc_status'])?>
                  </span>
                </div>
              </div>
              <?php if($user['kyc_verified_at']): ?>
              <div class="info-item">
                <div class="info-label">Verified On</div>
                <div class="info-value"><?=date('F j, Y', strtotime($user['kyc_verified_at']))?></div>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- Update Requests -->
          <?php if(!empty($updateRequests)): ?>
          <div class="profile-section">
            <h2 class="section-title">📝 Recent Update Requests</h2>
            <div class="requests-list">
              <?php foreach($updateRequests as $request): ?>
                <div class="request-item">
                  <div class="request-info">
                    <div class="request-type"><?=htmlspecialchars($request['request_type'])?></div>
                    <div class="request-date"><?=date('M j, Y g:i A', strtotime($request['created_at']))?></div>
                  </div>
                  <div class="request-status">
                    <span class="status-badge status-<?=$request['status']?>"><?=ucfirst($request['status'])?></span>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- User's Properties (for sellers) -->
          <?php if($user['role'] === 'seller' && !empty($userProperties)): ?>
          <div class="profile-section">
            <h2 class="section-title">🏠 My Properties</h2>
            <div class="properties-grid">
              <?php foreach($userProperties as $property): ?>
                <div class="property-card">
                  <div class="property-image">
                    <img src="<?=$property['cover_image'] ?: 'assets/img/placeholder.svg'?>" alt="<?=htmlspecialchars($property['title'])?>">
                    <div class="property-type"><?=ucfirst($property['type'])?></div>
                  </div>
                  <div class="property-content">
                    <h3 class="property-title"><?=htmlspecialchars($property['title'])?></h3>
                    <div class="property-price">₨<?=number_format($property['price'])?></div>
                    <div class="property-location">📍 <?=htmlspecialchars($property['district'])?></div>
                    <div class="property-actions">
                      <a href="property.php?id=<?=$property['id']?>" class="btn btn-sm">View</a>
                      <a href="edit-property.php?id=<?=$property['id']?>" class="btn btn-sm btn-primary">Edit</a>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- User's Offers (for buyers) -->
          <?php if($user['role'] === 'buyer' && !empty($userOffers)): ?>
          <div class="profile-section">
            <h2 class="section-title">💰 My Offers</h2>
            <div class="offers-list">
              <?php foreach($userOffers as $offer): ?>
                <div class="offer-item">
                  <div class="offer-property">
                    <img src="<?=$offer['cover_image'] ?: 'assets/img/placeholder.svg'?>" alt="Property" class="offer-image">
                    <div class="offer-info">
                      <h4><?=htmlspecialchars($offer['property_title'])?></h4>
                      <div class="offer-amount">Offered: ₨<?=number_format($offer['offer_amount'])?></div>
                    </div>
                  </div>
                  <div class="offer-status">
                    <span class="status-badge status-<?=$offer['status']?>"><?=ucfirst($offer['status'])?></span>
                    <div class="offer-date"><?=date('M j, Y', strtotime($offer['created_at']))?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- Account Actions -->
          <div class="profile-section">
            <h2 class="section-title">⚙️ Account Actions</h2>
            <div class="action-buttons">
              <a href="edit-profile.php" class="action-btn">
                <div class="action-icon">✏️</div>
                <div class="action-text">Edit Profile</div>
              </a>
              <a href="request-update.php" class="action-btn">
                <div class="action-icon">📝</div>
                <div class="action-text">Request Changes</div>
              </a>
              <a href="change-password.php" class="action-btn">
                <div class="action-icon">🔑</div>
                <div class="action-text">Change Password</div>
              </a>
              <a href="contact.php" class="action-btn">
                <div class="action-icon">💬</div>
                <div class="action-text">Contact Support</div>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container">
      <div class="footer-bottom">
        <div class="footer-copyright">
          © <?=date('Y')?> NepaEstate. Made with ❤️ for Nepal.
        </div>
      </div>
    </div>
  </footer>
  
  <script>
    // Simple navigation debugging without interfering with links
    console.log('Profile page loaded');
    
    document.addEventListener('DOMContentLoaded', function() {
      console.log('DOM loaded');
      
      // Just log the links found without interfering
      const allLinks = document.querySelectorAll('a[href]');
      console.log('Found navigation links:', allLinks.length);
      
      // Add simple click logging without preventing navigation
      allLinks.forEach(link => {
        link.addEventListener('click', function(e) {
          console.log('Link clicked:', this.href);
          // Don't prevent default navigation
        });
      });
    });
  </script>
</body>
</html>
