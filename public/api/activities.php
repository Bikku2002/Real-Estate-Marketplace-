<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

try{
  $pdo = get_pdo();
  $sinceId = isset($_GET['since_id']) ? (int)$_GET['since_id'] : 0;
  $stmt = $pdo->prepare("SELECT id, type, ref_id, meta, created_at FROM activities WHERE id > :id ORDER BY id ASC LIMIT 10");
  $stmt->execute([':id'=>$sinceId]);
  $rows = $stmt->fetchAll();
  foreach($rows as &$r){
    if(isset($r['meta'])){
      $decoded = json_decode($r['meta'], true);
      $r['meta'] = $decoded ?: null;
    }
  }
  echo json_encode(['ok'=>true,'activities'=>$rows]);
}catch(Throwable $e){
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Server error']);
}


