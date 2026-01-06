<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';

$error = null;
if(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'){
  $email = trim((string)($_POST['email'] ?? ''));
  $password = (string)($_POST['password'] ?? '');
  if(login_admin($email, $password)){
    header('Location: index.php');
    exit;
  } else {
    $error = 'Invalid credentials or not an admin';
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Admin Login · NepaEstate</title>
  <link rel="stylesheet" href="../assets/css/styles.css"/>
</head>
<body>
  <main class="container" style="max-width:420px;padding-top:80px">
    <div class="card" style="padding:16px">
      <div class="brand" style="margin-bottom:12px">
        <div class="brand-logo"></div>
        <div class="brand-name">NepaEstate Admin</div>
      </div>
      <?php if($error): ?><div class="toast" style="margin-bottom:10px;background:#2d1f23;border-color:#522"><?=$error?></div><?php endif; ?>
      <form method="post">
        <div class="field">
          <label class="label">Email</label>
          <input class="input" type="email" name="email" required placeholder="admin@example.com"/>
        </div>
        <div class="field">
          <label class="label">Password</label>
          <input class="input" type="password" name="password" required placeholder="password"/>
        </div>
        <button class="btn btn-primary" type="submit" style="width:100%">Sign in</button>
      </form>
      <div class="meta" style="margin-top:8px">Use admin@example.com / password (change in DB).</div>
      <div class="form-footer" style="text-align:center;margin-top:16px;padding-top:16px;border-top:1px solid var(--line)">
        <p style="color:var(--muted);margin:0">
          Don't have an account? 
          <a href="register.php" style="color:var(--accent);text-decoration:none;font-weight:600">Create one here</a>
        </p>
      </div>
    </div>
  </main>
</body>
</html>


