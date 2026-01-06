<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
require_admin();
require_once __DIR__ . '/../../config/db.php';
$pdo = get_pdo();

function scalar(PDO $pdo, string $sql){
  $v = $pdo->query($sql)->fetchColumn();
  return (int)($v ?: 0);
}

$countUsers = scalar($pdo, 'SELECT COUNT(*) FROM users');
$countProps = scalar($pdo, 'SELECT COUNT(*) FROM properties');
$countOffers = scalar($pdo, 'SELECT COUNT(*) FROM offers');
$countMsgs = scalar($pdo, "SELECT COUNT(*) FROM messages WHERE status='new'");

// KYC Statistics
$countKycPending = scalar($pdo, "SELECT COUNT(*) FROM users WHERE kyc_status='pending' AND kyc_document_type IS NOT NULL");
$countKycVerified = scalar($pdo, "SELECT COUNT(*) FROM users WHERE kyc_status='verified'");
$countKycRejected = scalar($pdo, "SELECT COUNT(*) FROM users WHERE kyc_status='rejected'");

// Recent activity
$msgs = $pdo->query("SELECT id, name, email, subject, status, created_at FROM messages ORDER BY created_at DESC LIMIT 6")->fetchAll();
$recentUsers = $pdo->query("SELECT id, name, email, kyc_status, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
$pendingKyc = $pdo->query("SELECT id, name, email, kyc_document_type, created_at FROM users WHERE kyc_status='pending' AND kyc_document_type IS NOT NULL ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>REAL-ESTATE MARKETPLACE Admin Dashboard</title>
  <link rel="stylesheet" href="../assets/css/styles.css"/>
  <link rel="stylesheet" href="../assets/css/admin.css"/>
  <style>
    .action-btn.properties {
      background: #10b981;
    }
    
    .action-btn.properties:hover {
      background: #059669;
    }
    
    /* Prevent unwanted tooltips and pseudo-elements */
    .admin-logo,
    .admin-logo *,
    .side-link,
    .side-link * {
      position: relative;
    }
    
    .admin-logo::before,
    .admin-logo::after,
    .side-link::before,
    .side-link::after {
      content: none !important;
      display: none !important;
    }
    
    .admin-logo *::before,
    .admin-logo *::after,
    .side-link *::before,
    .side-link *::after {
      content: none !important;
      display: none !important;
    }
    
    /* Ensure no tooltips appear */
    .admin-logo,
    .side-link {
      pointer-events: auto;
    }
    
    /* Additional tooltip prevention */
    [title],
    [data-tooltip],
    [data-title] {
      position: relative;
    }
    
    [title]::before,
    [title]::after,
    [data-tooltip]::before,
    [data-tooltip]::after,
    [data-title]::before,
    [data-title]::after {
      content: none !important;
      display: none !important;
    }
    
    /* Remove any browser default tooltips */
    .admin-logo,
    .side-link,
    .brand,
    .brand-name {
      -webkit-user-select: none;
      -moz-user-select: none;
      -ms-user-select: none;
      user-select: none;
    }
    
    /* Ensure logo remains completely unchanged on hover */
    .admin-logo,
    .admin-logo *,
    .admin-logo:hover,
    .admin-logo:hover *,
    .brand,
    .brand:hover,
    .brand-name,
    .brand-name:hover {
      transform: none !important;
      transition: none !important;
      background: none !important;
      border: none !important;
      box-shadow: none !important;
      opacity: 1 !important;
      filter: none !important;
      color: inherit !important;
      text-decoration: none !important;
    }
    
    /* Prevent any hover effects on logo elements */
    .admin-logo img,
    .admin-logo img:hover,
    .brand-name,
    .brand-name:hover {
      transform: none !important;
      transition: none !important;
      background: none !important;
      border: none !important;
      box-shadow: none !important;
      opacity: 1 !important;
      filter: none !important;
    }
  </style>
</head>
<body>
  <div class="admin-wrap">
    <aside class="admin-side">
      <div class="admin-logo brand">
        <img src="../assets/admin.png" alt="REAL-ESTATE MARKETPLACE" style="width:28px;height:28px;border-radius:6px;">
        <div class="brand-name">Home Admin</div>
      </div>
      <nav>
        <a class="side-link active" href="index.php">Dashboard</a>
        <a class="side-link" href="users.php">Users</a>
        <a class="side-link" href="properties.php">Properties</a>
        <a class="side-link" href="kyc.php">KYC Verification</a>
        <a class="side-link" href="messages.php">Messages</a>
        <a class="side-link" href="profile-requests.php">Profile Requests</a>
        <a class="side-link" href="password-requests.php">Password Requests</a>
        <a class="side-link" href="analytics.php">Analytics</a>
        <a class="side-link" href="logout.php">Logout</a>
      </nav>
    </aside>
    <main class="admin-main">
      <div class="admin-header">
        <h2>🏔️ REAL-ESTATE MARKETPLACE Admin Dashboard</h2>
        <p class="admin-subtitle">Manage users, verify KYC documents, and oversee platform operations</p>
      </div>

      <!-- Main Statistics -->
      <div class="stats-grid">
        <div class="stat-card users">
          <div class="stat-icon">👥</div>
          <div class="stat-content">
            <div class="stat-number"><?=$countUsers?></div>
            <div class="stat-label">Total Users</div>
          </div>
        </div>
        <div class="stat-card properties">
          <div class="stat-icon">🏠</div>
          <div class="stat-content">
            <div class="stat-number"><?=$countProps?></div>
            <div class="stat-label">Properties Listed</div>
          </div>
        </div>
        <div class="stat-card offers">
          <div class="stat-icon">💰</div>
          <div class="stat-content">
            <div class="stat-number"><?=$countOffers?></div>
            <div class="stat-label">Total Offers</div>
          </div>
        </div>
        <div class="stat-card messages">
          <div class="stat-icon">📧</div>
          <div class="stat-content">
            <div class="stat-number"><?=$countMsgs?></div>
            <div class="stat-label">New Messages</div>
          </div>
        </div>
      </div>

      <!-- KYC Verification Stats -->
      <div class="kyc-overview">
        <h3>🛡️ KYC Verification Overview</h3>
        <div class="kyc-stats">
          <div class="kyc-stat pending">
            <div class="kyc-stat-number"><?=$countKycPending?></div>
            <div class="kyc-stat-label">Pending Review</div>
            <a href="kyc.php" class="kyc-stat-action">Review Now →</a>
          </div>
          <div class="kyc-stat verified">
            <div class="kyc-stat-number"><?=$countKycVerified?></div>
            <div class="kyc-stat-label">Verified Users</div>
          </div>
          <div class="kyc-stat rejected">
            <div class="kyc-stat-number"><?=$countKycRejected?></div>
            <div class="kyc-stat-label">Rejected</div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="quick-actions">
        <h3>⚡ Quick Actions</h3>
        <div class="action-buttons">
          <a href="kyc.php" class="action-btn kyc">
            <div class="action-icon">🛡️</div>
            <div class="action-text">Review KYC</div>
            <?php if($countKycPending > 0): ?>
              <div class="action-badge"><?=$countKycPending?></div>
            <?php endif; ?>
          </a>
          <a href="users.php" class="action-btn users">
            <div class="action-icon">👥</div>
            <div class="action-text">Manage Users</div>
          </a>
          <a href="properties.php" class="action-btn properties">
            <div class="action-icon">🏠</div>
            <div class="action-text">Manage Properties</div>
          </a>
          <a href="messages.php" class="action-btn messages">
            <div class="action-icon">📧</div>
            <div class="action-text">View Messages</div>
            <?php if($countMsgs > 0): ?>
              <div class="action-badge"><?=$countMsgs?></div>
            <?php endif; ?>
          </a>
          <a href="../index.php" class="action-btn site" target="_blank">
            <div class="action-icon">🌐</div>
            <div class="action-text">View Site</div>
          </a>
        </div>
      </div>

      <div class="dashboard-grid">
        <!-- Pending KYC Verifications -->
        <div class="dashboard-section">
          <h3>🔍 Pending KYC Verifications</h3>
          <?php if(empty($pendingKyc)): ?>
            <div class="empty-state">
              <div class="empty-icon">✅</div>
              <div class="empty-text">No pending KYC verifications</div>
            </div>
          <?php else: ?>
            <div class="kyc-list">
              <?php foreach($pendingKyc as $kyc): ?>
                <div class="kyc-item">
                  <div class="kyc-user">
                    <div class="kyc-name"><?=htmlspecialchars($kyc['name'])?></div>
                    <div class="kyc-email"><?=htmlspecialchars($kyc['email'])?></div>
                  </div>
                  <div class="kyc-document">
                    <span class="doc-type"><?=ucfirst($kyc['kyc_document_type'])?></span>
                    <span class="doc-date"><?=date('M j', strtotime($kyc['created_at']))?></span>
                  </div>
                  <a href="kyc.php?id=<?=$kyc['id']?>" class="kyc-review-btn">Review</a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Recent Users -->
        <div class="dashboard-section">
          <h3>👥 Recent Users</h3>
          <div class="user-list">
            <?php foreach($recentUsers as $user): ?>
              <div class="user-item">
                <div class="user-info">
                  <div class="user-name"><?=htmlspecialchars($user['name'])?></div>
                  <div class="user-email"><?=htmlspecialchars($user['email'])?></div>
                </div>
                <div class="user-status">
                  <span class="kyc-badge kyc-<?=$user['kyc_status']?>"><?=ucfirst($user['kyc_status'])?></span>
                  <span class="user-date"><?=date('M j', strtotime($user['created_at']))?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Recent Messages -->
        <div class="dashboard-section full-width">
          <h3>📧 Recent Messages</h3>
          <div class="card" style="padding:0">
            <table class="table">
              <thead>
                <tr class="tr">
                  <th class="th">From</th>
                  <th class="th">Subject</th>
                  <th class="th">Status</th>
                  <th class="th">Received</th>
                  <th class="th">Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach($msgs as $m): ?>
                <tr class="tr">
                  <td class="td"><?=htmlspecialchars($m['name'] ?: ($m['email'] ?: 'Guest'))?></td>
                  <td class="td"><?=htmlspecialchars((string)$m['subject'])?></td>
                  <td class="td"><span class="chip"><?=$m['status']?></span></td>
                  <td class="td" style="white-space:nowrap"><?=$m['created_at']?></td>
                  <td class="td"><a class="btn" href="messages.php?id=<?=$m['id']?>">Open</a></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>
</body>
</html>


