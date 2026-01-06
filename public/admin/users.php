<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
require_admin();
require_once __DIR__ . '/../../config/db.php';
$pdo = get_pdo();

$action = $_GET['action'] ?? 'list';

if($action === 'create' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'){
  $name = trim((string)($_POST['name'] ?? ''));
  $email = trim((string)($_POST['email'] ?? ''));
  $phone = trim((string)($_POST['phone'] ?? ''));
  $role = $_POST['role'] ?? 'buyer';
  $password = (string)($_POST['password'] ?? '');
  if($name && $email && $password){
    $stmt = $pdo->prepare("INSERT INTO users(name,email,phone,role,password_hash) VALUES(:n,:e,:p,:r,:h)");
    $stmt->execute([':n'=>$name,':e'=>$email,':p'=>$phone?:null,':r'=>$role,':h'=>password_hash($password, PASSWORD_BCRYPT)]);
  }
  header('Location: users.php');
  exit;
}

if($action === 'delete'){
  $id = (int)($_GET['id'] ?? 0);
  if($id>0){
    $pdo->prepare("DELETE FROM users WHERE id=:id")->execute([':id'=>$id]);
  }
  header('Location: users.php');
  exit;
}

if($action === 'update' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'){
  $id = (int)($_POST['id'] ?? 0);
  $name = trim((string)($_POST['name'] ?? ''));
  $email = trim((string)($_POST['email'] ?? ''));
  $phone = trim((string)($_POST['phone'] ?? ''));
  $role = $_POST['role'] ?? 'buyer';
  $password = (string)($_POST['password'] ?? '');
  if($id>0){
    if($password){
      $stmt = $pdo->prepare("UPDATE users SET name=:n,email=:e,phone=:p,role=:r,password_hash=:h WHERE id=:id");
      $stmt->execute([':n'=>$name,':e'=>$email,':p'=>$phone?:null,':r'=>$role,':h'=>password_hash($password, PASSWORD_BCRYPT),':id'=>$id]);
    } else {
      $stmt = $pdo->prepare("UPDATE users SET name=:n,email=:e,phone=:p,role=:r WHERE id=:id");
      $stmt->execute([':n'=>$name,':e'=>$email,':p'=>$phone?:null,':r'=>$role,':id'=>$id]);
    }
  }
  header('Location: users.php');
  exit;
}

$rows = $pdo->query("SELECT id,name,email,phone,role,kyc_status,created_at FROM users ORDER BY created_at DESC LIMIT 200")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Users · Admin</title>
  <link rel="stylesheet" href="../assets/css/styles.css"/>
  <link rel="stylesheet" href="../assets/css/admin.css"/>
</head>
<body>
  <div class="admin-wrap">
    <aside class="admin-side">
      <div class="admin-logo brand"><div class="brand-logo"></div><div class="brand-name">Admin</div></div>
      <nav>
        <a class="side-link" href="index.php">Dashboard</a>
        <a class="side-link active" href="users.php">Users</a>
        <a class="side-link" href="properties.php">Properties</a>
        <a class="side-link" href="kyc.php">KYC Verification</a>
        <a class="side-link" href="messages.php">Messages</a>
        <a class="side-link" href="logout.php">Logout</a>
      </nav>
    </aside>
    <main class="admin-main">
      <h2>Users</h2>
      <div class="card" style="padding:12px;margin-bottom:12px">
        <form class="form-inline" method="post" action="users.php?action=create">
          <input class="input" name="name" placeholder="Name" required>
          <input class="input" type="email" name="email" placeholder="Email" required>
          <input class="input" name="phone" placeholder="Phone">
          <select class="select" name="role">
            <option value="buyer">Buyer</option>
            <option value="seller">Seller</option>
            <option value="admin">Admin</option>
          </select>
          <input class="input" type="password" name="password" placeholder="Password" required>
          <button class="btn btn-primary" type="submit">Add user</button>
        </form>
      </div>

      <div class="card" style="padding:0">
        <table class="table">
          <thead>
            <tr class="tr">
              <th class="th">Name</th>
              <th class="th">Email</th>
              <th class="th">Phone</th>
              <th class="th">Role</th>
              <th class="th">KYC Status</th>
              <th class="th">Created</th>
              <th class="th">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($rows as $u): ?>
            <tr class="tr">
              <td class="td"><?=htmlspecialchars($u['name'])?></td>
              <td class="td"><?=htmlspecialchars($u['email'])?></td>
              <td class="td"><?=htmlspecialchars((string)$u['phone'])?></td>
              <td class="td"><?=$u['role']?></td>
              <td class="td"><span class="chip" style="background:<?= $u['kyc_status']==='verified'?'#2fb070':($u['kyc_status']==='rejected'?'#ff5a5f':'#f5a623') ?>;color:white"><?=ucfirst($u['kyc_status'])?></span></td>
              <td class="td" style="white-space:nowrap"><?=$u['created_at']?></td>
              <td class="td actions">
                <details>
                  <summary class="btn">Edit</summary>
                  <form class="form-inline" method="post" action="users.php?action=update">
                    <input type="hidden" name="id" value="<?=$u['id']?>">
                    <input class="input" name="name" value="<?=htmlspecialchars($u['name'])?>">
                    <input class="input" name="email" value="<?=htmlspecialchars($u['email'])?>">
                    <input class="input" name="phone" value="<?=htmlspecialchars((string)$u['phone'])?>">
                    <select class="select" name="role">
                      <option value="buyer" <?=$u['role']==='buyer'?'selected':''?>>Buyer</option>
                      <option value="seller" <?=$u['role']==='seller'?'selected':''?>>Seller</option>
                      <option value="admin" <?=$u['role']==='admin'?'selected':''?>>Admin</option>
                    </select>
                    <input class="input" type="password" name="password" placeholder="New password (optional)">
                    <button class="btn btn-primary" type="submit">Save</button>
                  </form>
                </details>
                <a class="btn" href="users.php?action=delete&id=<?=$u['id']?>" onclick="return confirm('Delete user?')">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</body>
</html>


