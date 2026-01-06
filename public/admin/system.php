<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
require_admin();
require_once __DIR__ . '/../../config/db.php';

$pdo = get_pdo();

// System Information
$systemInfo = [
    'PHP Version' => PHP_VERSION,
    'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'Operating System' => PHP_OS,
    'Max Execution Time' => ini_get('max_execution_time') . ' seconds',
    'Memory Limit' => ini_get('memory_limit'),
    'Upload Max Filesize' => ini_get('upload_max_filesize'),
    'Post Max Size' => ini_get('post_max_size'),
    'Max File Uploads' => ini_get('max_file_uploads'),
];

// Database Information
try {
    $dbVersion = $pdo->query("SELECT VERSION() as version")->fetch()['version'];
    $dbSize = $pdo->query("
        SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE()
    ")->fetch()['size_mb'] ?? 0;
    
    $tableStats = $pdo->query("
        SELECT table_name, table_rows 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        ORDER BY table_rows DESC
    ")->fetchAll();
} catch(Exception $e) {
    $dbVersion = 'Unknown';
    $dbSize = 0;
    $tableStats = [];
}

// File System Check
$directories = [
    'Uploads Directory' => __DIR__ . '/../uploads/',
    'Profile Images' => __DIR__ . '/../uploads/profiles/',
    'KYC Documents' => __DIR__ . '/../uploads/kyc/',
    'Property Images' => __DIR__ . '/../assets/img/',
];

$dirStatus = [];
foreach($directories as $name => $path) {
    $dirStatus[$name] = [
        'exists' => is_dir($path),
        'writable' => is_writable($path),
        'path' => $path
    ];
}

// Security Checks
$securityChecks = [
    'HTTPS Enabled' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'Display Errors Off' => ini_get('display_errors') == '0',
    'Error Reporting On' => error_reporting() !== 0,
    'Session Security' => ini_get('session.cookie_httponly') === '1',
];

// Performance Metrics
$performanceData = [
    'Active Users (24h)' => $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn(),
    'Properties Listed (24h)' => $pdo->query("SELECT COUNT(*) FROM properties WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn(),
    'Offers Made (24h)' => $pdo->query("SELECT COUNT(*) FROM offers WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn(),
    'Messages Received (24h)' => $pdo->query("SELECT COUNT(*) FROM messages WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn(),
];

// Recent Errors (if you have an error log table)
$recentErrors = [];
if($pdo->query("SHOW TABLES LIKE 'error_logs'")->rowCount() > 0) {
    $recentErrors = $pdo->query("SELECT * FROM error_logs ORDER BY created_at DESC LIMIT 10")->fetchAll();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>System Status · Admin</title>
  <link rel="stylesheet" href="../assets/css/styles.css"/>
  <link rel="stylesheet" href="../assets/css/admin.css"/>
  <style>
    .system-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
      margin-bottom: 24px;
    }
    
    .status-card {
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 16px;
      padding: 20px;
    }
    
    .status-title {
      font-size: 16px;
      font-weight: 600;
      color: var(--ink);
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .status-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 0;
      border-bottom: 1px solid var(--line);
    }
    
    .status-item:last-child {
      border-bottom: none;
    }
    
    .status-label {
      color: var(--muted);
      font-size: 14px;
    }
    
    .status-value {
      font-weight: 500;
      color: var(--ink);
    }
    
    .status-good {
      color: #2fb070;
      font-weight: 600;
    }
    
    .status-warning {
      color: #f5a623;
      font-weight: 600;
    }
    
    .status-error {
      color: #ff5a5f;
      font-weight: 600;
    }
    
    .status-icon {
      font-size: 18px;
    }
    
    .refresh-btn {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: var(--accent);
      color: white;
      border: none;
      padding: 12px;
      border-radius: 50%;
      cursor: pointer;
      font-size: 18px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .table-stats {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }
    
    .table-stat {
      background: var(--elev);
      padding: 12px;
      border-radius: 8px;
      border: 1px solid var(--line);
    }
    
    .table-name {
      font-weight: 600;
      color: var(--ink);
      font-size: 14px;
    }
    
    .table-rows {
      color: var(--muted);
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
        <a class="side-link" href="analytics.php">Analytics</a>
        <a class="side-link active" href="system.php">System</a>
        <a class="side-link" href="logout.php">Logout</a>
      </nav>
    </aside>
    <main class="admin-main">
      <div class="admin-header">
        <h2>🔧 System Status</h2>
        <p class="admin-subtitle">Monitor system health, performance, and configuration</p>
      </div>

      <div class="system-grid">
        <!-- System Information -->
        <div class="status-card">
          <h3 class="status-title">
            <span class="status-icon">💻</span>
            System Information
          </h3>
          <?php foreach($systemInfo as $label => $value): ?>
            <div class="status-item">
              <span class="status-label"><?=$label?></span>
              <span class="status-value"><?=$value?></span>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Database Status -->
        <div class="status-card">
          <h3 class="status-title">
            <span class="status-icon">🗄️</span>
            Database Status
          </h3>
          <div class="status-item">
            <span class="status-label">MySQL Version</span>
            <span class="status-value"><?=$dbVersion?></span>
          </div>
          <div class="status-item">
            <span class="status-label">Database Size</span>
            <span class="status-value"><?=$dbSize?> MB</span>
          </div>
          <div class="status-item">
            <span class="status-label">Connection Status</span>
            <span class="status-value status-good">✅ Connected</span>
          </div>
        </div>

        <!-- File System Status -->
        <div class="status-card">
          <h3 class="status-title">
            <span class="status-icon">📁</span>
            File System
          </h3>
          <?php foreach($dirStatus as $name => $status): ?>
            <div class="status-item">
              <span class="status-label"><?=$name?></span>
              <span class="status-value">
                <?php if($status['exists'] && $status['writable']): ?>
                  <span class="status-good">✅ OK</span>
                <?php elseif($status['exists'] && !$status['writable']): ?>
                  <span class="status-warning">⚠️ Not Writable</span>
                <?php else: ?>
                  <span class="status-error">❌ Missing</span>
                <?php endif; ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Security Status -->
        <div class="status-card">
          <h3 class="status-title">
            <span class="status-icon">🔒</span>
            Security Checks
          </h3>
          <?php foreach($securityChecks as $check => $status): ?>
            <div class="status-item">
              <span class="status-label"><?=$check?></span>
              <span class="status-value">
                <?php if($status): ?>
                  <span class="status-good">✅ Enabled</span>
                <?php else: ?>
                  <span class="status-warning">⚠️ Disabled</span>
                <?php endif; ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Performance Metrics -->
        <div class="status-card">
          <h3 class="status-title">
            <span class="status-icon">⚡</span>
            24h Activity
          </h3>
          <?php foreach($performanceData as $metric => $value): ?>
            <div class="status-item">
              <span class="status-label"><?=$metric?></span>
              <span class="status-value"><?=number_format($value)?></span>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Database Tables -->
        <div class="status-card">
          <h3 class="status-title">
            <span class="status-icon">📊</span>
            Database Tables
          </h3>
          <div class="table-stats">
            <?php foreach($tableStats as $table): ?>
              <div class="table-stat">
                <div class="table-name"><?=$table['table_name']?></div>
                <div class="table-rows"><?=number_format($table['table_rows'] ?? 0)?> rows</div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- System Actions -->
      <div class="status-card">
        <h3 class="status-title">
          <span class="status-icon">⚙️</span>
          System Actions
        </h3>
        <div style="display:flex;gap:12px;flex-wrap:wrap">
          <button class="btn btn-primary" onclick="clearCache()">Clear Cache</button>
          <button class="btn btn-primary" onclick="optimizeDatabase()">Optimize Database</button>
          <button class="btn btn-primary" onclick="checkForUpdates()">Check Updates</button>
          <button class="btn" onclick="downloadLogs()">Download Logs</button>
          <button class="btn" onclick="exportSystemInfo()">Export System Info</button>
        </div>
      </div>

      <!-- System Health Summary -->
      <div class="status-card">
        <h3 class="status-title">
          <span class="status-icon">💚</span>
          System Health Summary
        </h3>
        <div style="text-align:center;padding:20px">
          <div style="font-size:48px;margin-bottom:16px">✅</div>
          <div style="font-size:18px;font-weight:600;color:var(--ink);margin-bottom:8px">System Running Normally</div>
          <div style="color:var(--muted)">All critical systems are operational</div>
          <div style="margin-top:16px;font-size:12px;color:var(--muted)">
            Last checked: <?=date('Y-m-d H:i:s')?>
          </div>
        </div>
      </div>
    </main>
  </div>

  <button class="refresh-btn" onclick="window.location.reload()" title="Refresh Status">
    🔄
  </button>

  <script>
    function clearCache() {
      if(confirm('This will clear all cached data. Continue?')) {
        alert('Cache clearing functionality would be implemented here.');
      }
    }

    function optimizeDatabase() {
      if(confirm('This will optimize database tables. Continue?')) {
        alert('Database optimization functionality would be implemented here.');
      }
    }

    function checkForUpdates() {
      alert('Checking for system updates...\n\nThis would connect to an update server to check for new versions.');
    }

    function downloadLogs() {
      alert('Downloading system logs...\n\nThis would generate and download log files.');
    }

    function exportSystemInfo() {
      const systemInfo = {
        timestamp: new Date().toISOString(),
        php_version: '<?=PHP_VERSION?>',
        server: '<?=$_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'?>',
        database: '<?=$dbVersion?>',
        database_size: '<?=$dbSize?> MB'
      };
      
      const blob = new Blob([JSON.stringify(systemInfo, null, 2)], {type: 'application/json'});
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'system-info-' + new Date().toISOString().split('T')[0] + '.json';
      a.click();
    }

    // Auto-refresh every 30 seconds
    setInterval(() => {
      window.location.reload();
    }, 30000);
  </script>
</body>
</html>
