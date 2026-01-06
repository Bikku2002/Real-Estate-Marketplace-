<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
require_admin();
require_once __DIR__ . '/../../config/db.php';
$pdo = get_pdo();

// Test database connection
try {
  $pdo->query("SELECT 1");
} catch (PDOException $e) {
  die("Database connection failed: " . $e->getMessage());
}

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle property status updates
if($action === 'toggle_status' && $id > 0){
  $stmt = $pdo->prepare("SELECT status FROM properties WHERE id = :id");
  $stmt->execute([':id' => $id]);
  $property = $stmt->fetch();
  
  if($property) {
    $newStatus = $property['status'] === 'active' ? 'inactive' : 'active';
    $stmt = $pdo->prepare("UPDATE properties SET status = :status WHERE id = :id");
    $stmt->execute([':status' => $newStatus, ':id' => $id]);
  }
  
  header('Location: properties.php?updated=1');
  exit;
}

// Handle property deletion
if($action === 'delete' && $id > 0){
  // Delete property images first
  $stmt = $pdo->prepare("DELETE FROM property_images WHERE property_id = :id");
  $stmt->execute([':id' => $id]);
  
  // Delete offers
  $stmt = $pdo->prepare("DELETE FROM offers WHERE property_id = :id");
  $stmt->execute([':id' => $id]);
  
  // Delete property
  $stmt = $pdo->prepare("DELETE FROM properties WHERE id = :id");
  $stmt->execute([':id' => $id]);
  
  header('Location: properties.php?deleted=1');
  exit;
}

// Get properties with seller info
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build the SQL query with proper parameter handling
$sql = "
  SELECT p.*, u.name as seller_name, u.email as seller_email,
         0 as offer_count
  FROM properties p 
  JOIN users u ON p.seller_id = u.id 
  WHERE 1=1
";

$params = [];

// Add filter condition
if($filter !== 'all') {
  $sql .= " AND p.type = :filter";
  $params[':filter'] = $filter;
}

// Add search condition
if($search) {
  $sql .= " AND (p.title LIKE :search_title OR p.district LIKE :search_district OR u.name LIKE :search_name)";
  $params[':search_title'] = "%$search%";
  $params[':search_district'] = "%$search%";
  $params[':search_name'] = "%$search%";
}

$sql .= " ORDER BY p.created_at DESC";

// Prepare and execute the statement
$stmt = $pdo->prepare($sql);

// Execute with parameters if we have any, otherwise execute without parameters
try {
  if (!empty($params)) {
    $stmt->execute($params);
  } else {
    $stmt->execute();
  }
  $properties = $stmt->fetchAll();
} catch (PDOException $e) {
  // Log the error and show a user-friendly message
  error_log("Database error in properties.php: " . $e->getMessage());
  error_log("SQL: " . $sql);
  error_log("Params: " . print_r($params, true));
  
  // Set empty properties array and show error message
  $properties = [];
  $error_message = "Database error occurred. Please try again later.";
}

// Get statistics
$stats = $pdo->query("
  SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN type = 'house' THEN 1 ELSE 0 END) as houses,
    SUM(CASE WHEN type = 'land' THEN 1 ELSE 0 END) as lands,
    COALESCE(AVG(price), 0) as avg_price
  FROM properties
")->fetch();

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
  <title>Properties Management · REAL-ESTATE MARKETPLACE Admin</title>
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
    
    .property-image {
      width: 60px;
      height: 40px;
      object-fit: cover;
      border-radius: 4px;
      border: 1px solid var(--line);
    }
    .property-actions {
      display: flex;
      gap: 4px;
    }
    .area-display {
      text-align: center;
    }
    .area-primary {
      font-weight: 600;
      color: var(--ink);
    }
    .area-conversion {
      font-size: 11px;
      color: var(--muted);
      opacity: 0.8;
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
    
    .status-active { background: #2fb070; color: white; }
    .status-inactive { background: #ff5a5f; color: white; }
    .filters {
      display: flex;
      gap: 12px;
      align-items: center;
      margin-bottom: 16px;
      padding: 16px;
      background: var(--elev);
      border-radius: 8px;
      border: 1px solid var(--line);
      flex-wrap: wrap;
    }
    .search-box {
      flex: 1;
      max-width: 300px;
    }
    .search-box input {
      width: 100%;
      padding: 8px 12px;
      border: 1px solid var(--line);
      border-radius: 6px;
      background: var(--card);
      color: var(--ink);
      font-size: 14px;
    }
    .search-box input:focus {
      outline: none;
      border-color: var(--accent);
    }
    .search-box input::placeholder {
      color: var(--muted);
    }
    .select {
      padding: 8px 12px;
      border: 1px solid var(--line);
      border-radius: 6px;
      background: var(--card);
      color: var(--ink);
      font-size: 14px;
      cursor: pointer;
    }
    .select:focus {
      outline: none;
      border-color: var(--accent);
    }
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
      border: none;
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
    .chip {
      display: inline-block;
      padding: 4px 8px;
      background: var(--elev);
      border: 1px solid var(--line);
      border-radius: 4px;
      font-size: 12px;
      font-weight: 600;
      color: var(--ink);
      text-transform: capitalize;
    }
    .property-type-display {
      font-size: 12px;
      color: var(--muted);
      text-transform: capitalize;
      font-weight: 500;
    }
    .property-title {
      font-weight: 600;
      color: var(--ink);
      margin-bottom: 4px;
    }
    .property-info {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }
    
    /* Alert styling */
    .alert {
      padding: 12px 16px;
      border-radius: 8px;
      margin-bottom: 16px;
      border: 1px solid var(--line);
    }
    .alert-success {
      background: var(--success);
      color: white;
      border-color: var(--success);
    }
    .alert-danger {
      background: var(--danger);
      color: white;
      border-color: var(--danger);
    }
    
    /* Responsive design */
    @media (max-width: 768px) {
      .filters {
        flex-direction: column;
        align-items: stretch;
      }
      .search-box {
        max-width: 100%;
      }
      .filters > * {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <div class="admin-wrap">
    <aside class="admin-side">
      <div class="admin-logo brand">
        <img src="../assets/Main.png" alt="REAL-ESTATE MARKETPLACE" style="width:28px;height:28px;border-radius:6px;">
        <div class="brand-name">Home Admin</div>
      </div>
      <nav>
        <a class="side-link" href="index.php">Dashboard</a>
        <a class="side-link" href="users.php">Users</a>
        <a class="side-link active" href="properties.php">Properties</a>
        <a class="side-link" href="kyc.php">KYC Verification</a>
        <a class="side-link" href="messages.php">Messages</a>
        <a class="side-link" href="analytics.php">Analytics</a>
        <a class="side-link" href="logout.php">Logout</a>
      </nav>
    </aside>
    <main class="admin-main">
      <div class="admin-header">
        <h2>🏠 Properties Management</h2>
        <p class="admin-subtitle">Monitor and manage property listings</p>
      </div>

      <?php if(isset($_GET['updated'])): ?>
        <div class="alert alert-success">✅ Property status updated successfully!</div>
      <?php endif; ?>

      <?php if(isset($_GET['deleted'])): ?>
        <div class="alert alert-success">✅ Property deleted successfully!</div>
      <?php endif; ?>

      <?php if(isset($error_message)): ?>
        <div class="alert alert-danger">❌ <?= htmlspecialchars($error_message) ?></div>
      <?php endif; ?>

      <!-- Statistics -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">🏠</div>
          <div class="stat-content">
            <div class="stat-number"><?=safeNumberFormat($stats['total'] ?? 0)?></div>
            <div class="stat-label">Total Properties</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">🏡</div>
          <div class="stat-content">
            <div class="stat-number"><?=safeNumberFormat($stats['houses'] ?? 0)?></div>
            <div class="stat-label">Houses</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">🏞️</div>
          <div class="stat-content">
            <div class="stat-number"><?=safeNumberFormat($stats['lands'] ?? 0)?></div>
            <div class="stat-label">Land Plots</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">💰</div>
          <div class="stat-content">
            <div class="stat-number">₨<?=safeNumberFormat($stats['avg_price'] ?? 0)?></div>
            <div class="stat-label">Average Price</div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <form method="get" class="filters">
        <select name="filter" class="select" onchange="this.form.submit()">
          <option value="all" <?=$filter === 'all' ? 'selected' : ''?>>All Properties</option>
          <option value="house" <?=$filter === 'house' ? 'selected' : ''?>>Houses Only</option>
          <option value="land" <?=$filter === 'land' ? 'selected' : ''?>>Land Only</option>
        </select>
        
        <div class="search-box">
          <input type="text" name="search" placeholder="Search properties, districts, or sellers..." 
                 value="<?=htmlspecialchars($search)?>" class="form-input">
        </div>
        
        <button type="submit" class="btn btn-primary">Search</button>
        
        <?php if($search || $filter !== 'all'): ?>
          <a href="properties.php" class="btn">Clear</a>
        <?php endif; ?>
      </form>

      <!-- Properties Table -->
      <div class="card" style="padding:0">
        <table class="table">
          <thead>
            <tr class="tr">
              <th class="th">Property</th>
              <th class="th">Type</th>
              <th class="th">Location</th>
              <th class="th">Price</th>
              <th class="th">Area</th>
              <th class="th">Seller</th>
              <th class="th">Offers</th>
              <th class="th">Listed</th>
              <th class="th">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($properties as $prop): ?>
            <tr class="tr">
              <td class="td">
                <div style="display:flex;align-items:center;gap:12px">
                  <?php if($prop['cover_image']): ?>
                    <img src="../<?=$prop['cover_image']?>" alt="Property" class="property-image">
                  <?php else: ?>
                    <div class="property-image" style="background:var(--elev);display:flex;align-items:center;justify-content:center;font-size:20px">🏠</div>
                  <?php endif; ?>
                  <div class="property-info">
                    <div class="property-title"><?=htmlspecialchars($prop['title'])?></div>
                    <div class="property-type-display"><?=ucfirst($prop['type'])?></div>
                  </div>
                </div>
              </td>
              <td class="td">
                <span class="chip"><?=ucfirst($prop['type'])?></span>
              </td>
              <td class="td">
                <?=htmlspecialchars($prop['district'])?>
                <?php if($prop['municipality']): ?>
                  <br><small style="color:var(--muted)"><?=htmlspecialchars($prop['municipality'])?></small>
                <?php endif; ?>
              </td>
              <td class="td">
                <strong>₨<?=safeNumberFormat($prop['price'] ?? 0)?></strong>
              </td>
              <td class="td">
                <div class="area-display">
                  <?php if($prop['area_unit'] && $prop['area_unit'] !== 'sqft'): ?>
                    <?php 
                    // Use the primary area unit for display
                    $primaryArea = $prop['area_unit'] === 'ana' ? $prop['area_ana'] : $prop['area_sqft'];
                    ?>
                    <div class="area-primary"><?=safeNumberFormat($primaryArea, 2)?> <?=ucfirst($prop['area_unit'])?></div>
                    <?php if($prop['area_sqft']): ?>
                      <div class="area-conversion">≈ <?=safeNumberFormat($prop['area_sqft'])?> sq ft</div>
                    <?php endif; ?>
                  <?php elseif($prop['area_sqft']): ?>
                    <div class="area-primary"><?=safeNumberFormat($prop['area_sqft'])?> sq ft</div>
                    <?php if($prop['area_ana']): ?>
                      <div class="area-conversion">≈ <?=safeNumberFormat($prop['area_ana'], 2)?> Ana</div>
                    <?php endif; ?>
                  <?php else: ?>
                    <div class="area-conversion">N/A</div>
                  <?php endif; ?>
                </div>
              </td>
              <td class="td">
                <?=htmlspecialchars($prop['seller_name'])?><br>
                <small style="color:var(--muted)"><?=htmlspecialchars($prop['seller_email'])?></small>
              </td>
              <td class="td">
                <?php if($prop['offer_count'] > 0): ?>
                  <span class="chip" style="background:var(--accent)"><?=safeNumberFormat($prop['offer_count'])?> offers</span>
                <?php else: ?>
                  <span style="color:var(--muted)">No offers</span>
                <?php endif; ?>
              </td>
              <td class="td" style="white-space:nowrap">
                <?=date('M j, Y', strtotime($prop['created_at']))?>
              </td>
              <td class="td">
                <div class="property-actions">
                  <a href="../property.php?id=<?=$prop['id']?>" class="btn" style="font-size:12px" target="_blank">View</a>
                  <a href="properties.php?action=delete&id=<?=$prop['id']?>" 
                     class="btn" style="font-size:12px;background:#ff5a5f;color:white"
                     onclick="return confirm('Are you sure you want to delete this property?')">Delete</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if(empty($properties)): ?>
        <div class="empty-state">
          <div class="empty-icon">🏠</div>
          <div class="empty-text">
            <?php if($search || $filter !== 'all'): ?>
              No properties found matching your criteria.
            <?php else: ?>
              No properties have been listed yet.
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </main>
  </div>

  <script>
    // Auto-hide alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
      const alerts = document.querySelectorAll('.alert');
      alerts.forEach(alert => {
        setTimeout(() => {
          alert.style.opacity = '0';
          alert.style.transform = 'translateY(-10px)';
          setTimeout(() => alert.remove(), 300);
        }, 5000);
      });
    });
  </script>
</body>
</html>
