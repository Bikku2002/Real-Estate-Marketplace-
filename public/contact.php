<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
$pdo = get_pdo();
$ok = false; $err = null;
if(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'){
  $name = trim((string)($_POST['name'] ?? '')) ?: null;
  $email = trim((string)($_POST['email'] ?? '')) ?: null;
  $phone = trim((string)($_POST['phone'] ?? '')) ?: null;
  $subject = trim((string)($_POST['subject'] ?? '')) ?: null;
  $body = trim((string)($_POST['body'] ?? ''));
  if($body){
    $stmt = $pdo->prepare("INSERT INTO messages(user_id,name,email,phone,subject,body) VALUES(NULL,:n,:e,:p,:s,:b)");
    $stmt->execute([':n'=>$name,':e'=>$email,':p'=>$phone,':s'=>$subject,':b'=>$body]);
    $ok = true;
  } else {
    $err = 'Please enter your message';
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
          <title>Contact · RealEstate</title>
  <link rel="stylesheet" href="assets/css/styles.css"/>
</head>
<body>
  <header class="site-header">
    <div class="container nav">
      <a class="brand" href="index.php">
        <div class="brand-logo"></div>
        <div class="brand-name">RealEstate</div>
      </a>
      <div class="nav-actions">
        <a class="btn" href="index.php#search">Find Listings</a>
      </div>
    </div>
  </header>
  <main class="container" style="max-width:760px;margin-top:16px">
    <h2>Contact us</h2>
    <p class="meta">Questions, listing help, or negotiation guidance—send us a note and our team will respond promptly.</p>
    <?php if($ok): ?>
      <div class="toast">Thanks! Your message was received.</div>
    <?php endif; ?>
    <?php if($err): ?>
      <div class="toast" style="background:#2d1f23;border-color:#522"><?=$err?></div>
    <?php endif; ?>
    <div class="card" style="padding:16px">
      <form method="post">
        <div class="grid">
          <div class="col-6 field">
            <label class="label">Name</label>
            <input class="input" name="name"/>
          </div>
          <div class="col-6 field">
            <label class="label">Email</label>
            <input class="input" type="email" name="email"/>
          </div>
          <div class="col-6 field">
            <label class="label">Phone</label>
            <input class="input" name="phone"/>
          </div>
          <div class="col-6 field">
            <label class="label">Subject</label>
            <input class="input" name="subject"/>
          </div>
          <div class="col-12 field">
            <label class="label">Message</label>
            <textarea class="input" name="body" rows="6" style="resize:vertical"></textarea>
          </div>
        </div>
        <button class="btn btn-primary" type="submit">Send</button>
      </form>
    </div>
  </main>
  <footer class="site-footer">
            <div class="container">© <?=date('Y')?> RealEstate</div>
  </footer>
</body>
</html>


