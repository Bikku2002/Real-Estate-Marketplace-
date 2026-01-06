<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

try{
  $pdo = get_pdo();
  $type = $_GET['type'] ?? null; // land|house
  $district = $_GET['district'] ?? null;
  $q = $_GET['q'] ?? null;
  $maxPrice = isset($_GET['max_price']) ? (int)$_GET['max_price'] : null;

  $sql = "SELECT id, title, type, district, municipality, price, cover_image FROM properties WHERE 1=1";
  $params = [];
  if($type){ $sql .= " AND type = :type"; $params[':type']=$type; }
  if($district){ $sql .= " AND district = :district"; $params[':district']=$district; }
  if($q){ $sql .= " AND (title LIKE :q OR description LIKE :q)"; $params[':q']='%'.$q.'%'; }
  if($maxPrice){ $sql .= " AND price <= :maxp"; $params[':maxp']=$maxPrice; }
  $sql .= " ORDER BY created_at DESC LIMIT 50";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll();

  echo json_encode(['ok'=>true,'results'=>$rows]);
}catch(Throwable $e){
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Server error']);
}


