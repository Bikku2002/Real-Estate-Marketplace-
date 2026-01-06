<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
require_admin();
require_once __DIR__ . '/../../config/db.php';
$pdo = get_pdo();

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle KYC status updates
if($action === 'update' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'){
  $status = $_POST['status'] ?? 'pending';
  $notes = trim((string)($_POST['notes'] ?? ''));
  if($id > 0){
    $stmt = $pdo->prepare("UPDATE users SET kyc_status=:status, kyc_notes=:notes, kyc_verified_at=:verified WHERE id=:id");
    $stmt->execute([
      ':status' => $status,
      ':notes' => $notes,
      ':verified' => $status === 'verified' ? date('Y-m-d H:i:s') : null,
      ':id' => $id
    ]);
    
    // Log the action
    $admin = current_admin();
    $logMsg = "KYC status updated to '{$status}' by {$admin['name']}";
    if($notes) $logMsg .= " - Notes: {$notes}";
    
    // You could log this to a separate admin_actions table if needed
  }
  header('Location: kyc.php?updated=1');
  exit;
}

// Handle bulk actions
if($action === 'bulk' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'){
  $bulkAction = $_POST['bulk_action'] ?? '';
  $selectedIds = $_POST['selected_ids'] ?? [];
  
  if($bulkAction && !empty($selectedIds)){
    $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
    $stmt = $pdo->prepare("UPDATE users SET kyc_status = ? WHERE id IN ($placeholders)");
    $stmt->execute(array_merge([$bulkAction], $selectedIds));
  }
  
  header('Location: kyc.php?bulk_updated=1');
  exit;
}

// Get KYC submissions
$stmt = $pdo->query("SELECT id, name, email, kyc_status, kyc_document_type, kyc_document_number, kyc_document_image, kyc_notes, created_at FROM users WHERE kyc_document_type IS NOT NULL ORDER BY created_at DESC");
$submissions = $stmt->fetchAll();

// Get specific user for detail view
$user = null;
if($id > 0){
  $stmt = $pdo->prepare("SELECT * FROM users WHERE id=:id");
  $stmt->execute([':id' => $id]);
  $user = $stmt->fetch();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>KYC Management · Admin</title>
  <link rel="stylesheet" href="../assets/css/styles.css"/>
  <link rel="stylesheet" href="../assets/css/admin.css"/>
  <style>
    /* KYC Status Chips */
    .kyc-status-pending { background: #f5a623; color: white; }
    .kyc-status-verified { background: #2fb070; color: white; }
    .kyc-status-rejected { background: #ff5a5f; color: white; }
    
    /* Document Preview */
    .document-preview { 
      max-width: 100%; 
      border-radius: 8px; 
      border: 1px solid var(--line);
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    /* Bulk Actions */
    .bulk-controls {
      display: flex;
      gap: 12px;
      align-items: center;
      margin-bottom: 16px;
      padding: 12px;
      background: var(--elev);
      border-radius: 8px;
      border: 1px solid var(--line);
    }
    
    /* Alert Messages */
    .alert {
      padding: 12px 16px;
      border-radius: 8px;
      margin-bottom: 16px;
      border: 1px solid;
    }
    .alert-success {
      background: rgba(47,176,112,0.1);
      border-color: rgba(47,176,112,0.3);
      color: #2fb070;
    }
    
    /* Enhanced Review Card */
    .kyc-review-card {
      padding: 0;
      overflow: hidden;
    }
    
    .kyc-review-header {
      background: var(--elev);
      padding: 20px;
      border-bottom: 1px solid var(--line);
    }
    
    .kyc-review-header h3 {
      margin: 0 0 8px;
      color: var(--ink);
      font-size: 18px;
    }
    
    .user-info .user-name {
      font-weight: 600;
      color: var(--ink);
      font-size: 16px;
    }
    
    .user-info .user-email {
      color: var(--muted);
      font-size: 14px;
    }
    
    .kyc-details {
      padding: 20px;
    }
    
    .detail-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 0;
      border-bottom: 1px solid var(--line);
    }
    
    .detail-item:last-child {
      border-bottom: none;
    }
    
    .detail-label {
      font-weight: 600;
      color: var(--muted);
      font-size: 12px;
      text-transform: uppercase;
    }
    
    .detail-value {
      color: var(--ink);
      font-weight: 500;
    }
    
    .document-preview-section {
      padding: 20px;
      border-top: 1px solid var(--line);
    }
    
    .image-container {
      position: relative;
      margin-top: 8px;
    }
    
    .document-image {
      width: 100%;
      border-radius: 8px;
      border: 1px solid var(--line);
    }
    
    .view-full {
      position: absolute;
      top: 8px;
      right: 8px;
      background: rgba(0,0,0,0.7);
      color: white;
      padding: 4px 8px;
      border-radius: 4px;
      text-decoration: none;
      font-size: 12px;
    }
    
    .kyc-form {
      padding: 20px;
      border-top: 1px solid var(--line);
    }
    
    .form-actions {
      display: flex;
      gap: 8px;
      margin-top: 16px;
    }
    
    /* Stats Card */
    .stats-card {
      padding: 20px;
    }
    
    .stats-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-bottom: 20px;
    }
    
    .stat-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px;
      background: var(--elev);
      border-radius: 8px;
      border: 1px solid var(--line);
    }
    
    .stat-item .stat-icon {
      font-size: 20px;
    }
    
    .stat-item .stat-number {
      font-size: 18px;
      font-weight: 700;
      color: var(--ink);
    }
    
    .stat-item .stat-label {
      font-size: 12px;
      color: var(--muted);
    }
    
    .quick-tips {
      background: var(--elev);
      padding: 16px;
      border-radius: 8px;
      border: 1px solid var(--line);
    }
    
    .quick-tips h4 {
      margin: 0 0 12px;
      color: var(--ink);
      font-size: 14px;
    }
    
    .quick-tips ul {
      margin: 0;
      padding-left: 16px;
      color: var(--muted);
      font-size: 13px;
    }
    
    .quick-tips li {
      margin-bottom: 4px;
    }
  </style>
</head>
<body>
  <div class="admin-wrap">
    <aside class="admin-side">
      <div class="admin-logo brand"><div class="brand-logo"></div><div class="brand-name">Admin</div></div>
      <nav>
        <a class="side-link" href="index.php">Dashboard</a>
        <a class="side-link" href="users.php">Users</a>
        <a class="side-link" href="properties.php">Properties</a>
        <a class="side-link active" href="kyc.php">KYC Verification</a>
        <a class="side-link" href="messages.php">Messages</a>
        <a class="side-link" href="logout.php">Logout</a>
      </nav>
    </aside>
    <main class="admin-main">
      <div class="admin-header">
        <h2>🛡️ KYC Verification Management</h2>
        <p class="admin-subtitle">Review and verify user identity documents</p>
      </div>

      <?php if(isset($_GET['updated'])): ?>
        <div class="alert alert-success">✅ KYC status updated successfully!</div>
      <?php endif; ?>

      <?php if(isset($_GET['bulk_updated'])): ?>
        <div class="alert alert-success">✅ Bulk action completed successfully!</div>
      <?php endif; ?>
      
      <div class="grid" style="margin-top:8px">
        <div class="col-8">
          <!-- Bulk Actions -->
          <form method="post" action="kyc.php?action=bulk" class="bulk-actions">
            <div class="bulk-controls">
              <select name="bulk_action" class="select">
                <option value="">Bulk Actions</option>
                <option value="verified">Mark as Verified</option>
                <option value="rejected">Mark as Rejected</option>
                <option value="pending">Mark as Pending</option>
              </select>
              <button type="submit" class="btn btn-primary">Apply</button>
            </div>
          
          <div class="card" style="padding:0">
            <table class="table">
              <thead>
                <tr class="tr">
                  <th class="th">
                    <input type="checkbox" id="select-all" onchange="toggleAll(this)">
                  </th>
                  <th class="th">User</th>
                  <th class="th">Document</th>
                  <th class="th">Status</th>
                  <th class="th">Submitted</th>
                  <th class="th">Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach($submissions as $sub): ?>
                <tr class="tr">
                  <td class="td">
                    <input type="checkbox" name="selected_ids[]" value="<?=$sub['id']?>" class="row-checkbox">
                  </td>
                  <td class="td">
                    <?=htmlspecialchars($sub['name'])?><br>
                    <small style="color:var(--muted)"><?=htmlspecialchars($sub['email'])?></small>
                  </td>
                  <td class="td">
                    <?=ucfirst($sub['kyc_document_type'])?><br>
                    <small style="color:var(--muted)"><?=htmlspecialchars($sub['kyc_document_number'])?></small>
                  </td>
                  <td class="td">
                    <span class="chip kyc-status-<?=$sub['kyc_status']?>"><?=ucfirst($sub['kyc_status'])?></span>
                  </td>
                  <td class="td" style="white-space:nowrap"><?=date('M j, Y', strtotime($sub['created_at']))?></td>
                  <td class="td">
                    <a class="btn" href="kyc.php?id=<?=$sub['id']?>">Review</a>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          </form>
        </div>
        
        <div class="col-4">
          <?php if($user): ?>
            <div class="card" style="padding:16px">
              <h3>Review KYC: <?=htmlspecialchars($user['name'])?></h3>
              
              <div style="margin-bottom:16px">
                <strong>Document Type:</strong> <?=ucfirst($user['kyc_document_type'])?><br>
                <strong>Document Number:</strong> <?=htmlspecialchars($user['kyc_document_number'])?><br>
                <strong>Current Status:</strong> <span class="chip kyc-status-<?=$user['kyc_status']?>"><?=ucfirst($user['kyc_status'])?></span>
              </div>
              
              <?php if($user['kyc_document_image']): ?>
                <div style="margin-bottom:16px">
                  <strong>Document Image:</strong><br>
                  <?php if(pathinfo($user['kyc_document_image'], PATHINFO_EXTENSION) === 'pdf'): ?>
                    <a href="../<?=$user['kyc_document_image']?>" target="_blank" class="btn">View PDF</a>
                  <?php else: ?>
                    <img src="../<?=$user['kyc_document_image']?>" alt="KYC Document" class="document-preview">
                  <?php endif; ?>
                </div>
              <?php endif; ?>
              
              <form method="post" action="kyc.php?action=update&id=<?=$user['id']?>">
                <div class="field">
                  <label class="label">Status</label>
                  <select class="select" name="status">
                    <option value="pending" <?=$user['kyc_status']==='pending'?'selected':''?>>Pending</option>
                    <option value="verified" <?=$user['kyc_status']==='verified'?'selected':''?>>Verified</option>
                    <option value="rejected" <?=$user['kyc_status']==='rejected'?'selected':''?>>Rejected</option>
                  </select>
                </div>
                <div class="field">
                  <label class="label">Admin Notes</label>
                  <textarea class="input" name="notes" rows="3" placeholder="Add notes about this verification"><?=htmlspecialchars($user['kyc_notes'] ?? '')?></textarea>
                </div>
                <div class="kyc-actions">
                  <button class="btn btn-primary" type="submit">Update Status</button>
                  <a class="btn" href="kyc.php">Back to List</a>
                </div>
              </form>
            </div>
          <?php else: ?>
            <div class="card" style="padding:16px">
              <h3>KYC Overview</h3>
              <p style="color:var(--muted)">Select a submission to review documents and update verification status.</p>
              
              <div style="margin-top:16px">
                <div class="stat">
                  <strong>Pending:</strong> 
                  <?= count(array_filter($submissions, fn($s) => $s['kyc_status'] === 'pending')) ?>
                </div>
                <div class="stat">
                  <strong>Verified:</strong> 
                  <?= count(array_filter($submissions, fn($s) => $s['kyc_status'] === 'verified')) ?>
                </div>
                <div class="stat">
                  <strong>Rejected:</strong> 
                  <?= count(array_filter($submissions, fn($s) => $s['kyc_status'] === 'rejected')) ?>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>

  <script>
    // Toggle all checkboxes
    function toggleAll(checkbox) {
      const checkboxes = document.querySelectorAll('.row-checkbox');
      checkboxes.forEach(cb => cb.checked = checkbox.checked);
      updateBulkControls();
    }
    
    // Update bulk controls visibility
    function updateBulkControls() {
      const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
      const bulkControls = document.querySelector('.bulk-controls');
      const bulkSelect = document.querySelector('select[name="bulk_action"]');
      const bulkBtn = document.querySelector('.bulk-controls button');
      
      if(checkedBoxes.length > 0) {
        bulkControls.style.opacity = '1';
        bulkSelect.disabled = false;
        bulkBtn.disabled = false;
        bulkBtn.textContent = `Apply to ${checkedBoxes.length} item(s)`;
      } else {
        bulkControls.style.opacity = '0.5';
        bulkSelect.disabled = true;
        bulkBtn.disabled = true;
        bulkBtn.textContent = 'Apply';
      }
    }
    
    // Add event listeners to all checkboxes
    document.addEventListener('DOMContentLoaded', function() {
      const checkboxes = document.querySelectorAll('.row-checkbox');
      checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkControls);
      });
      
      // Initial state
      updateBulkControls();
      
      // Bulk form submission confirmation
      const bulkForm = document.querySelector('.bulk-actions');
      if(bulkForm) {
        bulkForm.addEventListener('submit', function(e) {
          const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
          const action = document.querySelector('select[name="bulk_action"]').value;
          
          if(checkedBoxes.length === 0) {
            e.preventDefault();
            alert('Please select at least one item to update.');
            return;
          }
          
          if(!action) {
            e.preventDefault();
            alert('Please select an action to perform.');
            return;
          }
          
          const confirmMsg = `Are you sure you want to mark ${checkedBoxes.length} item(s) as ${action}?`;
          if(!confirm(confirmMsg)) {
            e.preventDefault();
          }
        });
      }
      
      // Auto-hide alerts after 5 seconds
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
