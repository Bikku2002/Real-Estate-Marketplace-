<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
require_admin();
require_once __DIR__ . '/../../config/db.php';
$pdo = get_pdo();

// Update status/notes
if(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'){
  $id = (int)($_POST['id'] ?? 0);
  $status = $_POST['status'] ?? 'new';
  $notes = trim((string)($_POST['admin_notes'] ?? '')) ?: null;
  if($id>0){
    $stmt = $pdo->prepare("UPDATE messages SET status=:s, admin_notes=:n WHERE id=:id");
    $stmt->execute([':s'=>$status, ':n'=>$notes, ':id'=>$id]);
  }
  header('Location: messages.php?id='.$id);
  exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id>0){
  $msgStmt = $pdo->prepare("SELECT * FROM messages WHERE id=:id");
  $msgStmt->execute([':id'=>$id]);
  $m = $msgStmt->fetch();
}

$rows = $pdo->query("SELECT id,name,email,subject,status,created_at FROM messages ORDER BY created_at DESC LIMIT 200")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Messages · Admin</title>
  <link rel="stylesheet" href="../assets/css/styles.css"/>
  <link rel="stylesheet" href="../assets/css/admin.css"/>
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
        <a class="side-link active" href="messages.php">Messages</a>
        <a class="side-link" href="logout.php">Logout</a>
      </nav>
    </aside>
    <main class="admin-main">
      <h2>Messages</h2>
      <div class="grid" style="margin-top:8px">
        <div class="col-8">
          <div class="card" style="padding:0">
            <table class="table">
              <thead>
                <tr class="tr">
                  <th class="th">From</th>
                  <th class="th">Subject</th>
                  <th class="th">Status</th>
                  <th class="th">Received</th>
                  <th class="th">Action</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach($rows as $row): ?>
                <tr class="tr">
                  <td class="td"><?=htmlspecialchars($row['name'] ?: ($row['email'] ?: 'Guest'))?></td>
                  <td class="td"><?=htmlspecialchars((string)$row['subject'])?></td>
                  <td class="td"><span class="chip"><?=$row['status']?></span></td>
                  <td class="td" style="white-space:nowrap"><?=$row['created_at']?></td>
                  <td class="td"><a class="btn" href="messages.php?id=<?=$row['id']?>">Open</a></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <div class="col-4">
          <div class="card" style="padding:12px">
            <?php if(!empty($m)): ?>
              <div style="font-weight:600;margin-bottom:8px">Message #<?=$m['id']?></div>
              <div class="meta" style="margin-bottom:8px">From: <?=htmlspecialchars($m['name'] ?: ($m['email'] ?: 'Guest'))?> <?=$m['phone']? '· '.htmlspecialchars($m['phone']):''?></div>
              <div class="meta" style="margin-bottom:8px">Subject: <?=htmlspecialchars((string)$m['subject'])?></div>
              <div style="white-space:pre-wrap; margin-bottom:12px"><?=nl2br(htmlspecialchars((string)$m['body']))?></div>
              <form method="post">
                <input type="hidden" name="id" value="<?=$m['id']?>"/>
                <div class="field">
                  <label class="label">Status</label>
                  <select class="select" name="status">
                    <option value="new" <?=$m['status']==='new'?'selected':''?>>New</option>
                    <option value="in_progress" <?=$m['status']==='in_progress'?'selected':''?>>In progress</option>
                    <option value="resolved" <?=$m['status']==='resolved'?'selected':''?>>Resolved</option>
                  </select>
                </div>
                <div class="field">
                  <label class="label">Admin notes</label>
                  <input class="input" name="admin_notes" value="<?=htmlspecialchars((string)$m['admin_notes'])?>"/>
                </div>
                <button class="btn btn-primary" type="submit">Save</button>
              </form>
            <?php else: ?>
              <div class="meta">Select a message to view and update.</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>
  </div>
</body>
</html>


