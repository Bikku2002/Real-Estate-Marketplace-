<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
require_admin();
require_once __DIR__ . '/../../config/db.php';
$pdo = get_pdo();

// Get date range from query params
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$endDate = $_GET['end_date'] ?? date('Y-m-d'); // Today

// User Analytics
$userStats = $pdo->prepare("
  SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN role = 'buyer' THEN 1 ELSE 0 END) as buyers,
    SUM(CASE WHEN role = 'seller' THEN 1 ELSE 0 END) as sellers,
    SUM(CASE WHEN created_at >= :start AND created_at <= :end THEN 1 ELSE 0 END) as new_users,
    SUM(CASE WHEN kyc_status = 'verified' THEN 1 ELSE 0 END) as verified_users
  FROM users WHERE role != 'admin'
");
$userStats->execute([':start' => $startDate, ':end' => $endDate]);
$users = $userStats->fetch();

// Property Analytics
$propertyStats = $pdo->prepare("
  SELECT 
    COUNT(*) as total_properties,
    SUM(CASE WHEN type = 'house' THEN 1 ELSE 0 END) as houses,
    SUM(CASE WHEN type = 'land' THEN 1 ELSE 0 END) as lands,
    AVG(price) as avg_price,
    MIN(price) as min_price,
    MAX(price) as max_price,
    SUM(CASE WHEN created_at >= :start AND created_at <= :end THEN 1 ELSE 0 END) as new_properties
  FROM properties
");
$propertyStats->execute([':start' => $startDate, ':end' => $endDate]);
$properties = $propertyStats->fetch();

// Offer Analytics
$offerStats = $pdo->prepare("
  SELECT 
    COUNT(*) as total_offers,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_offers,
    SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_offers,
    AVG(offer_amount) as avg_offer,
    SUM(CASE WHEN created_at >= :start AND created_at <= :end THEN 1 ELSE 0 END) as new_offers
  FROM offers
");
$offerStats->execute([':start' => $startDate, ':end' => $endDate]);
$offers = $offerStats->fetch();

// Top Districts
$topDistricts = $pdo->query("
  SELECT district, COUNT(*) as property_count, AVG(price) as avg_price
  FROM properties 
  GROUP BY district 
  ORDER BY property_count DESC 
  LIMIT 10
")->fetchAll();

// Recent Activity
$recentActivity = $pdo->query("
  SELECT 'user_registered' as type, name as title, created_at, 'User' as category
  FROM users 
  WHERE role != 'admin' 
  UNION ALL
  SELECT 'property_listed' as type, title, created_at, 'Property' as category
  FROM properties
  UNION ALL
  SELECT 'offer_made' as type, CONCAT('Offer of ₨', FORMAT(offer_amount, 0)) as title, created_at, 'Offer' as category
  FROM offers
  ORDER BY created_at DESC 
  LIMIT 15
")->fetchAll();

// KYC Statistics
$kycStats = $pdo->query("
  SELECT 
    kyc_status,
    COUNT(*) as count
  FROM users 
  WHERE kyc_document_type IS NOT NULL
  GROUP BY kyc_status
")->fetchAll();

// Helper function to safely format numbers
function safeNumberFormat($value, $decimals = 0) {
    if ($value === null || $value === '') {
        return '0';
    }
    // Convert to float/int and then format
    $numericValue = is_numeric($value) ? (float)$value : 0;
    return number_format($numericValue, $decimals);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Analytics & Reports · REAL-ESTATE MARKETPLACE Admin</title>
  <link rel="stylesheet" href="../assets/css/styles.css"/>
  <link rel="stylesheet" href="../assets/css/admin.css"/>
  <style>
    /* CSS Variables for consistent theming */
    :root {
      --ink: #e8f1ff;
      --muted: #9fb3d8;
      --accent: #d7263d;
      --accent-2: #00856f;
      --gold: #d4a20a;
      --bg: #0c0f14;
      --card: #1a2130;
      --elev: #242b3d;
      --line: rgba(255, 255, 255, 0.1);
      --success: #2fb070;
      --warning: #f5a623;
      --danger: #ff5a5f;
    }
    
    .analytics-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
      margin-bottom: 32px;
    }
    
    .chart-card {
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 16px;
      padding: 20px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }
    
    .chart-title {
      font-size: 16px;
      font-weight: 600;
      color: var(--ink);
      margin-bottom: 16px;
    }
    
    .metric-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 0;
      border-bottom: 1px solid var(--line);
    }
    
    .metric-row:last-child {
      border-bottom: none;
    }
    
    .metric-label {
      color: var(--muted);
      font-size: 14px;
    }
    
    .metric-value {
      font-weight: 600;
      color: var(--ink);
    }
    
    .progress-bar {
      width: 100%;
      height: 6px;
      background: var(--elev);
      border-radius: 3px;
      overflow: hidden;
      margin-top: 4px;
    }
    
    .progress-fill {
      height: 100%;
      background: var(--accent);
      transition: width 0.3s ease;
    }
    
    .date-filters {
      display: flex;
      gap: 12px;
      align-items: center;
      margin-bottom: 24px;
      padding: 16px;
      background: var(--elev);
      border-radius: 8px;
      border: 1px solid var(--line);
      flex-wrap: wrap;
    }
    
    .date-filters label {
      color: var(--ink);
      font-weight: 600;
      margin-right: 8px;
    }
    
    .date-filters input[type="date"] {
      padding: 8px 12px;
      border: 1px solid var(--line);
      border-radius: 6px;
      background: var(--card);
      color: var(--ink);
      font-size: 14px;
    }
    
    .date-filters input[type="date"]:focus {
      outline: none;
      border-color: var(--accent);
    }
    
    .date-filters span {
      color: var(--muted);
      font-size: 14px;
    }
    
    .activity-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px;
      background: var(--elev);
      border-radius: 8px;
      margin-bottom: 8px;
      border: 1px solid var(--line);
    }
    
    .activity-icon {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
    }
    
    .activity-icon.user { background: rgba(47,176,112,0.1); }
    .activity-icon.property { background: rgba(245,166,35,0.1); }
    .activity-icon.offer { background: rgba(215,38,61,0.1); }
    
    .activity-content {
      flex: 1;
    }
    
    .activity-title {
      font-weight: 600;
      color: var(--ink);
      font-size: 14px;
    }
    
    .activity-time {
      color: var(--muted);
      font-size: 12px;
    }
    
    /* Form styling */
    .form-input {
      padding: 8px 12px;
      border: 1px solid var(--line);
      border-radius: 6px;
      background: var(--card);
      color: var(--ink);
      font-size: 14px;
    }
    
    .form-input:focus {
      outline: none;
      border-color: var(--accent);
    }
    
    /* Button styling */
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border: 1px solid var(--line);
      padding: 8px 16px;
      border-radius: 8px;
      background: var(--card);
      color: var(--ink);
      text-decoration: none;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    
    .btn:hover {
      background: var(--elev);
      border-color: var(--accent);
    }
    
    .btn-primary {
      background: var(--accent);
      border-color: var(--accent);
      color: white;
    }
    
    .btn-primary:hover {
      background: #b01d31;
    }
    
    /* Responsive design */
    @media (max-width: 768px) {
      .date-filters {
        flex-direction: column;
        align-items: stretch;
      }
      
      .date-filters > * {
        width: 100%;
      }
      
      .analytics-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="admin-wrap">
    <aside class="admin-side">
      <div class="admin-logo brand">
        <img src="../assets/admin.png" alt="REAL-ESTATE MARKETPLACE" style="width:28px;height:28px;border-radius:6px;">
        <div class="brand-name">Admin</div>
      </div>
      <nav>
        <a class="side-link" href="index.php">Dashboard</a>
        <a class="side-link" href="users.php">Users</a>
        <a class="side-link" href="properties.php">Properties</a>
        <a class="side-link" href="kyc.php">KYC Verification</a>
        <a class="side-link" href="messages.php">Messages</a>
        <a class="side-link active" href="analytics.php">Analytics</a>
        <a class="side-link" href="logout.php">Logout</a>
      </nav>
    </aside>
    <main class="admin-main">
      <div class="admin-header">
        <h2>📊 Analytics & Reports</h2>
        <p class="admin-subtitle">Comprehensive insights and platform metrics</p>
      </div>

      <!-- Date Range Filter -->
      <form method="get" class="date-filters">
        <label>Date Range:</label>
        <input type="date" name="start_date" value="<?=$startDate?>" class="form-input">
        <span>to</span>
        <input type="date" name="end_date" value="<?=$endDate?>" class="form-input">
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="analytics.php" class="btn">Reset</a>
      </form>

      <!-- Key Metrics -->
      <div class="analytics-grid">
        <!-- User Analytics -->
        <div class="chart-card">
          <h3 class="chart-title">👥 User Analytics</h3>
          <div class="metric-row">
            <span class="metric-label">Total Users</span>
            <span class="metric-value"><?=safeNumberFormat($users['total_users'] ?? 0)?></span>
          </div>
          <div class="metric-row">
            <span class="metric-label">New Users (Period)</span>
            <span class="metric-value"><?=safeNumberFormat($users['new_users'] ?? 0)?></span>
          </div>
          <div class="metric-row">
            <span class="metric-label">Buyers</span>
            <span class="metric-value"><?=safeNumberFormat($users['buyers'] ?? 0)?></span>
          </div>
          <div class="metric-row">
            <span class="metric-label">Sellers</span>
            <span class="metric-value"><?=safeNumberFormat($users['sellers'] ?? 0)?></span>
          </div>
          <div class="metric-row">
            <span class="metric-label">KYC Verified</span>
            <span class="metric-value"><?=safeNumberFormat($users['verified_users'] ?? 0)?></span>
          </div>
        </div>

        <!-- Property Analytics -->
        <div class="chart-card">
          <h3 class="chart-title">🏠 Property Analytics</h3>
          <div class="metric-row">
            <span class="metric-label">Total Properties</span>
            <span class="metric-value"><?=safeNumberFormat($properties['total_properties'] ?? 0)?></span>
          </div>
          <div class="metric-row">
            <span class="metric-label">New Listings (Period)</span>
            <span class="metric-value"><?=safeNumberFormat($properties['new_properties'] ?? 0)?></span>
          </div>
          <div class="metric-row">
            <span class="metric-label">Houses</span>
            <span class="metric-value"><?=safeNumberFormat($properties['houses'] ?? 0)?></span>
          </div>
          <div class="metric-row">
            <span class="metric-label">Land Plots</span>
            <span class="metric-value"><?=safeNumberFormat($properties['lands'] ?? 0)?></span>
          </div>
          <div class="metric-row">
            <span class="metric-label">Average Price</span>
            <span class="metric-value">₨<?=safeNumberFormat($properties['avg_price'] ?? 0)?></span>
          </div>
        </div>

        <!-- Offer Analytics -->
        <div class="chart-card">
          <h3 class="chart-title">💰 Offer Analytics</h3>
          <div class="metric-row">
            <span class="metric-label">Total Offers</span>
            <span class="metric-value"><?=safeNumberFormat($offers['total_offers'] ?? 0)?></span>
          </div>
          <div class="metric-row">
            <span class="metric-label">New Offers (Period)</span>
            <span class="metric-value"><?=safeNumberFormat($offers['new_offers'] ?? 0)?></span>
          </div>
          <div class="metric-row">
            <span class="metric-label">Pending Offers</span>
            <span class="metric-value"><?=safeNumberFormat($offers['pending_offers'] ?? 0)?></span>
          </div>
          <div class="metric-row">
            <span class="metric-label">Accepted Offers</span>
            <span class="metric-value"><?=safeNumberFormat($offers['accepted_offers'] ?? 0)?></span>
          </div>
          <div class="metric-row">
            <span class="metric-label">Average Offer</span>
            <span class="metric-value">₨<?=safeNumberFormat($offers['avg_offer'] ?? 0)?></span>
          </div>
        </div>

        <!-- KYC Status -->
        <div class="chart-card">
          <h3 class="chart-title">🛡️ KYC Verification</h3>
          <?php if (!empty($kycStats)): ?>
            <?php foreach($kycStats as $stat): ?>
              <div class="metric-row">
                <span class="metric-label"><?=ucfirst($stat['kyc_status'])?></span>
                <span class="metric-value"><?=safeNumberFormat($stat['count'])?></span>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="metric-row">
              <span class="metric-label">No KYC data</span>
              <span class="metric-value">-</span>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Top Districts & Recent Activity -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px">
        <!-- Top Districts -->
        <div class="chart-card">
          <h3 class="chart-title">📍 Top Districts by Properties</h3>
          <?php if (!empty($topDistricts)): ?>
            <?php foreach($topDistricts as $district): ?>
              <div class="metric-row">
                <div>
                  <div class="metric-label"><?=htmlspecialchars($district['district'])?></div>
                  <div style="font-size:12px;color:var(--muted)">Avg: ₨<?=safeNumberFormat($district['avg_price'] ?? 0)?></div>
                </div>
                <span class="metric-value"><?=safeNumberFormat($district['property_count'])?></span>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="metric-row">
              <span class="metric-label">No district data</span>
              <span class="metric-value">-</span>
            </div>
          <?php endif; ?>
        </div>

        <!-- Recent Activity -->
        <div class="chart-card">
          <h3 class="chart-title">⚡ Recent Activity</h3>
          <div style="max-height:400px;overflow-y:auto">
            <?php if (!empty($recentActivity)): ?>
              <?php foreach($recentActivity as $activity): ?>
                <div class="activity-item">
                  <div class="activity-icon <?=strtolower($activity['category'])?>">
                    <?php if($activity['category'] === 'User'): ?>👤<?php endif; ?>
                    <?php if($activity['category'] === 'Property'): ?>🏠<?php endif; ?>
                    <?php if($activity['category'] === 'Offer'): ?>💰<?php endif; ?>
                  </div>
                  <div class="activity-content">
                    <div class="activity-title"><?=htmlspecialchars($activity['title'])?></div>
                    <div class="activity-time"><?=date('M j, Y g:i A', strtotime($activity['created_at']))?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="activity-item">
                <div class="activity-content">
                  <div class="activity-title">No recent activity</div>
                  <div class="activity-time">-</div>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Export Options -->
      <div class="chart-card" style="margin-top:24px">
        <h3 class="chart-title">📥 Export Data</h3>
        <p style="color:var(--muted);margin-bottom:16px">Download reports and analytics data</p>
        <div style="display:flex;gap:12px;flex-wrap:wrap">
          <button class="btn btn-primary" onclick="exportData('users')">Export Users</button>
          <button class="btn btn-primary" onclick="exportData('properties')">Export Properties</button>
          <button class="btn btn-primary" onclick="exportData('offers')">Export Offers</button>
          <button class="btn" onclick="window.print()">Print Report</button>
        </div>
      </div>
    </main>
  </div>

  <script>
    function exportData(type) {
      // Simple CSV export functionality
      const startDate = '<?=$startDate?>';
      const endDate = '<?=$endDate?>';
      
      // This would typically make an AJAX call to a separate export endpoint
      alert(`Exporting ${type} data from ${startDate} to ${endDate}.\n\nThis feature would generate a CSV file with the requested data.`);
    }

    // Auto-refresh data every 5 minutes
    setTimeout(() => {
      window.location.reload();
    }, 300000);
  </script>
</body>
</html>
