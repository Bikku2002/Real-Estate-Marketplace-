<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
require_admin();
require_once __DIR__ . '/../../config/db.php';
$pdo = get_pdo();

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle request approval/rejection
if($action === 'update' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'){
  $status = $_POST['status'] ?? 'pending';
  $adminNotes = trim((string)($_POST['admin_notes'] ?? ''));
  
  if($id > 0){
    $pdo->beginTransaction();
    try {
      // Get request details
      $stmt = $pdo->prepare("SELECT * FROM profile_update_requests WHERE id = :id");
      $stmt->execute([':id' => $id]);
      $request = $stmt->fetch();
      
      if ($request && $status === 'approved') {
        // Apply the update to user profile
        switch ($request['request_type']) {
          case 'name':
            $stmt = $pdo->prepare("UPDATE users SET name = :value WHERE id = :user_id");
            $stmt->execute([':value' => $request['requested_data'], ':user_id' => $request['user_id']]);
            break;
          case 'phone':
            $stmt = $pdo->prepare("UPDATE users SET phone = :value WHERE id = :user_id");
            $stmt->execute([':value' => $request['requested_data'], ':user_id' => $request['user_id']]);
            break;
          case 'profile_image':
            if ($request['uploaded_file']) {
              $stmt = $pdo->prepare("UPDATE users SET profile_image = :value WHERE id = :user_id");
              $stmt->execute([':value' => $request['uploaded_file'], ':user_id' => $request['user_id']]);
            }
            break;
          case 'kyc_document':
            if ($request['uploaded_file']) {
              // Reset KYC status to pending for review
              $stmt = $pdo->prepare("UPDATE users SET kyc_document_image = :value, kyc_status = 'pending' WHERE id = :user_id");
              $stmt->execute([':value' => $request['uploaded_file'], ':user_id' => $request['user_id']]);
            }
            break;
        }
      }
      
      // Update request status
      $admin = current_admin();
      $stmt = $pdo->prepare("
        UPDATE profile_update_requests 
        SET status = :status, admin_notes = :notes, admin_id = :admin_id, updated_at = NOW() 
        WHERE id = :id
      ");
      $stmt->execute([
        ':status' => $status,
        ':notes' => $adminNotes,
        ':admin_id' => $admin['id'],
        ':id' => $id
      ]);
      
      $pdo->commit();
    } catch (Exception $e) {
      $pdo->rollback();
      throw $e;
    }
  }
  header('Location: profile-requests.php?updated=1');
  exit;
}

// Get profile update requests
$stmt = $pdo->query("
  SELECT pr.*, u.name as user_name, u.email as user_email 
  FROM profile_update_requests pr 
  JOIN users u ON pr.user_id = u.id 
  ORDER BY pr.created_at DESC
");
$requests = $stmt->fetchAll();

// Get specific request for detail view
$selectedRequest = null;
if($id > 0){
  $stmt = $pdo->prepare("
    SELECT pr.*, u.name as user_name, u.email as user_email 
    FROM profile_update_requests pr 
    JOIN users u ON pr.user_id = u.id 
    WHERE pr.id = :id
  ");
  $stmt->execute([':id' => $id]);
  $selectedRequest = $stmt->fetch();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Profile Update Requests · Admin</title>
  <link rel="stylesheet" href="../assets/css/styles.css"/>
  <link rel="stylesheet" href="../assets/css/admin.css"/>
  <style>
    .request-preview {
      background: var(--elev);
      padding: 16px;
      border-radius: 8px;
      border: 1px solid var(--line);
      margin: 12px 0;
    }
    .file-preview img {
      max-width: 200px;
      max-height: 200px;
      border-radius: 8px;
      border: 1px solid var(--line);
    }
    .data-comparison {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
      margin: 16px 0;
    }
    .data-section {
      background: var(--elev);
      padding: 12px;
      border-radius: 8px;
      border: 1px solid var(--line);
    }
    .data-label {
      font-size: 12px;
      font-weight: 600;
      color: var(--muted);
      text-transform: uppercase;
      margin-bottom: 8px;
    }
    .data-value {
      color: var(--ink);
      font-weight: 500;
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
        <a class="side-link" href="kyc.php">KYC Verification</a>
        <a class="side-link" href="messages.php">Messages</a>
        <a class="side-link active" href="profile-requests.php">Profile Requests</a>
        <a class="side-link" href="password-requests.php">Password Requests</a>
        <a class="side-link" href="analytics.php">Analytics</a>
        <a class="side-link" href="logout.php">Logout</a>
      </nav>
    </aside>
    <main class="admin-main">
      <div class="admin-header">
        <h2>📝 Profile Update Requests</h2>
        <p class="admin-subtitle">Review and approve user profile change requests</p>
      </div>

      <?php if(isset($_GET['updated'])): ?>
        <div class="alert alert-success">✅ Request updated successfully!</div>
      <?php endif; ?>

      <div class="grid" style="margin-top:8px">
        <div class="col-8">
          <div class="card" style="padding:0">
            <table class="table">
              <thead>
                <tr class="tr">
                  <th class="th">User</th>
                  <th class="th">Request Type</th>
                  <th class="th">Status</th>
                  <th class="th">Requested</th>
                  <th class="th">Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach($requests as $req): ?>
                <tr class="tr">
                  <td class="td">
                    <?=htmlspecialchars($req['user_name'])?><br>
                    <small style="color:var(--muted)"><?=htmlspecialchars($req['user_email'])?></small>
                  </td>
                  <td class="td">
                    <span class="chip"><?=ucfirst(str_replace('_', ' ', $req['request_type']))?></span>
                  </td>
                  <td class="td">
                    <span class="chip status-<?=$req['status']?>"><?=ucfirst($req['status'])?></span>
                  </td>
                  <td class="td" style="white-space:nowrap"><?=date('M j, Y g:i A', strtotime($req['created_at']))?></td>
                  <td class="td">
                    <a class="btn" href="profile-requests.php?id=<?=$req['id']?>">Review</a>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        
        <div class="col-4">
          <?php if($selectedRequest): ?>
            <div class="card" style="padding:16px">
              <h3>Review Request: <?=htmlspecialchars($selectedRequest['user_name'])?></h3>
              
              <div style="margin:16px 0">
                <strong>Request Type:</strong> <?=ucfirst(str_replace('_', ' ', $selectedRequest['request_type']))?><br>
                <strong>Submitted:</strong> <?=date('M j, Y g:i A', strtotime($selectedRequest['created_at']))?><br>
                <strong>Status:</strong> <span class="chip status-<?=$selectedRequest['status']?>"><?=ucfirst($selectedRequest['status'])?></span>
              </div>

              <div class="data-comparison">
                <div class="data-section">
                  <div class="data-label">Current Data</div>
                  <div class="data-value"><?=htmlspecialchars($selectedRequest['current_data'])?></div>
                </div>
                <div class="data-section">
                  <div class="data-label">Requested Data</div>
                  <div class="data-value"><?=htmlspecialchars($selectedRequest['requested_data'])?></div>
                </div>
              </div>

              <?php if($selectedRequest['uploaded_file']): ?>
                <div style="margin:16px 0">
                  <strong>Uploaded File:</strong><br>
                  <div class="file-preview">
                    <?php if(str_ends_with($selectedRequest['uploaded_file'], '.pdf')): ?>
                      <a href="../<?=$selectedRequest['uploaded_file']?>" target="_blank" class="btn">View PDF</a>
                    <?php else: ?>
                      <img src="../<?=$selectedRequest['uploaded_file']?>" alt="Uploaded file">
                    <?php endif; ?>
                  </div>
                </div>
              <?php endif; ?>

              <div style="margin:16px 0">
                <strong>User's Reason:</strong><br>
                <div class="request-preview"><?=nl2br(htmlspecialchars($selectedRequest['reason']))?></div>
              </div>

              <?php if($selectedRequest['status'] === 'pending'): ?>
              <form method="post" action="profile-requests.php?action=update&id=<?=$selectedRequest['id']?>">
                <div class="field">
                  <label class="label">Decision</label>
                  <select class="select" name="status" required>
                    <option value="approved">Approve Request</option>
                    <option value="rejected">Reject Request</option>
                  </select>
                </div>
                <div class="field">
                  <label class="label">Admin Notes</label>
                  <textarea class="input" name="admin_notes" rows="3" placeholder="Add notes about your decision..."></textarea>
                </div>
                <div style="display:flex;gap:8px;margin-top:16px">
                  <button class="btn btn-primary" type="submit">Update Request</button>
                  <a class="btn" href="profile-requests.php">Back to List</a>
                </div>
              </form>
              <?php else: ?>
                <div style="margin-top:16px">
                  <strong>Admin Decision:</strong> <?=ucfirst($selectedRequest['status'])?><br>
                  <?php if($selectedRequest['admin_notes']): ?>
                    <strong>Admin Notes:</strong><br>
                    <div class="request-preview"><?=nl2br(htmlspecialchars($selectedRequest['admin_notes']))?></div>
                  <?php endif; ?>
                  <a class="btn" href="profile-requests.php">Back to List</a>
                </div>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <div class="card" style="padding:16px">
              <h3>📊 Request Statistics</h3>
              <div class="stat">Pending: <?=count(array_filter($requests, fn($r) => $r['status'] === 'pending'))?></div>
              <div class="stat">Approved: <?=count(array_filter($requests, fn($r) => $r['status'] === 'approved'))?></div>
              <div class="stat">Rejected: <?=count(array_filter($requests, fn($r) => $r['status'] === 'rejected'))?></div>
            </div>
          <?php endif; ?>
        </div>
      </div>
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
