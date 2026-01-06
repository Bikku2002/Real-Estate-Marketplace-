<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try{
  $pdo = get_pdo();
  if($method === 'GET'){
    $propertyId = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;
    if($propertyId<=0){ http_response_code(400); echo json_encode(['ok'=>false,'error'=>'property_id required']); exit; }
    $stmt = $pdo->prepare("SELECT o.id, o.offer_amount, o.status, o.message, o.created_at, u.name AS buyer_name FROM offers o JOIN users u ON u.id=o.buyer_id WHERE o.property_id=:pid ORDER BY o.created_at DESC LIMIT 50");
    $stmt->execute([':pid'=>$propertyId]);
    echo json_encode(['ok'=>true,'offers'=>$stmt->fetchAll()]);
    exit;
  }

  if($method === 'POST'){
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $propertyId = isset($input['property_id']) ? (int)$input['property_id'] : 0;
    $buyerId = isset($input['buyer_id']) ? (int)$input['buyer_id'] : 2; // demo buyer
    $amount = isset($input['offer_amount']) ? (int)$input['offer_amount'] : 0;
    $message = isset($input['message']) ? trim((string)$input['message']) : null;
    if($propertyId<=0 || $amount<=0){ http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid payload']); exit; }

    $stmt = $pdo->prepare("INSERT INTO offers(property_id,buyer_id,offer_amount,message) VALUES(:p,:b,:a,:m)");
    $stmt->execute([':p'=>$propertyId,':b'=>$buyerId,':a'=>$amount,':m'=>$message]);
    $offerId = (int)$pdo->lastInsertId();

    // Push activity for real-time ticker
    $prop = $pdo->prepare("SELECT title FROM properties WHERE id=:id");
    $prop->execute([':id'=>$propertyId]);
    $title = ($prop->fetch()['title'] ?? null);
    $meta = json_encode(['title'=>$title,'price'=>$amount]);
    $act = $pdo->prepare("INSERT INTO activities(type,ref_id,meta) VALUES('new_offer', :rid, :meta)");
    $act->execute([':rid'=>$offerId, ':meta'=>$meta]);

    echo json_encode(['ok'=>true,'id'=>$offerId]);
    exit;
  }

  http_response_code(405);
  echo json_encode(['ok'=>false,'error'=>'Method not allowed']);
}catch(Throwable $e){
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Server error']);
}


