<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
require_admin();
require_once __DIR__ . '/../../config/db.php';
$pdo = get_pdo();

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle password reset approval/rejection
if($action === 'update' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'){
  $status = $_POST['status'] ?? 'pending';
  $adminNotes = trim((string)($_POST['admin_notes'] ?? ''));
  $newPassword = $_POST['new_password'] ?? '';
  
  if($id > 0){
    $pdo->beginTransaction();
    try {
      // Get request details
      $stmt = $pdo->prepare("SELECT * FROM password_reset_requests WHERE id = :id");
      $stmt->execute([':id' => $id]);
      $request = $stmt->fetch();
      
      if ($request && $status === 'approved' && !empty($newPassword)) {
        // Update user password
        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash, login_attempts = 0, locked_until = NULL WHERE id = :user_id");
        $stmt->execute([':hash' => $passwordHash, ':user_id' => $request['user_id']]);
        
        // Store the new password hash in request for email sending (you'd typically email this)
        $stmt = $pdo->prepare("UPDATE password_reset_requests SET new_password_hash = :hash WHERE id = :id");
        $stmt->execute([':hash' => $passwordHash, ':id' => $id]);
      }
      
      // Update request status
      $admin = current_admin();
      $stmt = $pdo->prepare("
        UPDATE password_reset_requests 
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
  header('Location: password-requests.php?updated=1');
  exit;
}

// Get password reset requests
$stmt = $pdo->query("
  SELECT pr.*, u.name as user_name 
  FROM password_reset_requests pr 
  JOIN users u ON pr.user_id = u.id 
  ORDER BY pr.created_at DESC
");
$requests = $stmt->fetchAll();

// Get specific request for detail view
$selectedRequest = null;
if($id > 0){
  $stmt = $pdo->prepare("
    SELECT pr.*, u.name as user_name 
    FROM password_reset_requests pr 
    JOIN users u ON pr.user_id = u.id 
    WHERE pr.id = :id
  ");
  $stmt->execute([':id' => $id]);
  $selectedRequest = $stmt->fetch();
}

// Generate random password function
function generatePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    return substr(str_shuffle($chars), 0, $length);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Password Reset Requests · Admin</title>
  <link rel="stylesheet" href="../assets/css/styles.css"/>
  <link rel="stylesheet" href="../assets/css/admin.css"/>
  <style>
    .password-generator {
      background: var(--elev);
      padding: 12px;
      border-radius: 8px;
      border: 1px solid var(--line);
      margin: 12px 0;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .generated-password {
      font-family: monospace;
      background: var(--card);
      padding: 8px;
      border-radius: 4px;
      border: 1px solid var(--line);
      flex: 1;
    }
    .copy-btn {
      padding: 4px 8px;
      font-size: 12px;
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
        <a class="side-link" href="profile-requests.php">Profile Requests</a>
        <a class="side-link active" href="password-requests.php">Password Requests</a>
        <a class="side-link" href="analytics.php">Analytics</a>
        <a class="side-link" href="logout.php">Logout</a>
      </nav>
    </aside>
    <main class="admin-main">
      <div class="admin-header">
        <h2>🔑 Password Reset Requests</h2>
        <p class="admin-subtitle">Review and process user password reset requests</p>
      </div>

      <?php if(isset($_GET['updated'])): ?>
        <div class="alert alert-success">✅ Password request updated successfully!</div>
      <?php endif; ?>

      <div class="grid" style="margin-top:8px">
        <div class="col-8">
          <div class="card" style="padding:0">
            <table class="table">
              <thead>
                <tr class="tr">
                  <th class="th">User</th>
                  <th class="th">Email</th>
                  <th class="th">Status</th>
                  <th class="th">Requested</th>
                  <th class="th">Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach($requests as $req): ?>
                <tr class="tr">
                  <td class="td"><?=htmlspecialchars($req['user_name'])?></td>
                  <td class="td"><?=htmlspecialchars($req['email'])?></td>
                  <td class="td">
                    <span class="chip status-<?=$req['status']?>"><?=ucfirst($req['status'])?></span>
                  </td>
                  <td class="td" style="white-space:nowrap"><?=date('M j, Y g:i A', strtotime($req['created_at']))?></td>
                  <td class="td">
                    <a class="btn" href="password-requests.php?id=<?=$req['id']?>">Review</a>
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
                <strong>User:</strong> <?=htmlspecialchars($selectedRequest['user_name'])?><br>
                <strong>Email:</strong> <?=htmlspecialchars($selectedRequest['email'])?><br>
                <strong>Submitted:</strong> <?=date('M j, Y g:i A', strtotime($selectedRequest['created_at']))?><br>
                <strong>Status:</strong> <span class="chip status-<?=$selectedRequest['status']?>"><?=ucfirst($selectedRequest['status'])?></span>
              </div>

              <div style="margin:16px 0">
                <strong>User's Reason:</strong><br>
                <div class="request-preview" style="background:var(--elev);padding:12px;border-radius:8px;border:1px solid var(--line)">
                  <?=nl2br(htmlspecialchars($selectedRequest['reason']))?>
                </div>
              </div>

              <?php if($selectedRequest['status'] === 'pending'): ?>
              <form method="post" action="password-requests.php?action=update&id=<?=$selectedRequest['id']?>">
                <div class="field">
                  <label class="label">Decision</label>
                  <select class="select" name="status" required onchange="togglePasswordField(this.value)">
                    <option value="">Select decision</option>
                    <option value="approved">Approve & Reset Password</option>
                    <option value="rejected">Reject Request</option>
                  </select>
                </div>
                
                <div class="field" id="password-field" style="display:none">
                  <label class="label">New Password</label>
                  <div class="password-generator">
                    <input type="text" name="new_password" id="new_password" class="generated-password" readonly>
                    <button type="button" class="btn copy-btn" onclick="generatePassword()">Generate</button>
                    <button type="button" class="btn copy-btn" onclick="copyPassword()">Copy</button>
                  </div>
                  <small style="color:var(--muted)">This password will be sent to the user via email</small>
                </div>
                
                <div class="field">
                  <label class="label">Admin Notes</label>
                  <textarea class="input" name="admin_notes" rows="3" placeholder="Add notes about your decision..."></textarea>
                </div>
                <div style="display:flex;gap:8px;margin-top:16px">
                  <button class="btn btn-primary" type="submit">Process Request</button>
                  <a class="btn" href="password-requests.php">Back to List</a>
                </div>
              </form>
              <?php else: ?>
                <div style="margin-top:16px">
                  <strong>Admin Decision:</strong> <?=ucfirst($selectedRequest['status'])?><br>
                  <?php if($selectedRequest['admin_notes']): ?>
                    <strong>Admin Notes:</strong><br>
                    <div class="request-preview" style="background:var(--elev);padding:12px;border-radius:8px;border:1px solid var(--line)">
                      <?=nl2br(htmlspecialchars($selectedRequest['admin_notes']))?>
                    </div>
                  <?php endif; ?>
                  <?php if($selectedRequest['status'] === 'approved'): ?>
                    <div style="margin-top:12px;padding:12px;background:rgba(47,176,112,0.1);border-radius:8px;border:1px solid rgba(47,176,112,0.3)">
                      <strong>✅ Password has been reset</strong><br>
                      <small style="color:var(--muted)">User should receive new password via email</small>
                    </div>
                  <?php endif; ?>
                  <a class="btn" href="password-requests.php" style="margin-top:12px">Back to List</a>
                </div>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <div class="card" style="padding:16px">
              <h3>📊 Request Statistics</h3>
              <div class="stat">Pending: <?=count(array_filter($requests, fn($r) => $r['status'] === 'pending'))?></div>
              <div class="stat">Approved: <?=count(array_filter($requests, fn($r) => $r['status'] === 'approved'))?></div>
              <div class="stat">Rejected: <?=count(array_filter($requests, fn($r) => $r['status'] === 'rejected'))?></div>
              
              <div style="margin-top:20px">
                <h4>🤖 Auto-Approval System</h4>
                <p style="font-size:14px;color:var(--muted);margin:0 0 12px">Most password reset requests are now automatically approved when users provide valid reasons.</p>
                
                <h4>💡 Security Guidelines</h4>
                <ul style="font-size:14px;color:var(--muted);margin:0;padding-left:16px">
                  <li>System automatically verifies registered email</li>
                  <li>Strong passwords generated automatically</li>
                  <li>Users receive temporary passwords instantly</li>
                  <li>Users are encouraged to change password after login</li>
                </ul>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>

  <script>
    function togglePasswordField(status) {
      const passwordField = document.getElementById('password-field');
      if (status === 'approved') {
        passwordField.style.display = 'block';
        generatePassword(); // Auto-generate password
      } else {
        passwordField.style.display = 'none';
      }
    }
    
    function generatePassword() {
      const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
      let password = '';
      for (let i = 0; i < 12; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
      }
      document.getElementById('new_password').value = password;
    }
    
    function copyPassword() {
      const passwordField = document.getElementById('new_password');
      passwordField.select();
      document.execCommand('copy');
      
      // Show feedback
      const btn = event.target;
      const originalText = btn.textContent;
      btn.textContent = 'Copied!';
      setTimeout(() => {
        btn.textContent = originalText;
      }, 2000);
    }

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
