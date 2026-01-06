<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

function start_session(): void {
  if(session_status() !== PHP_SESSION_ACTIVE){ session_start(); }
}

function login_admin(string $email, string $password): bool {
  $pdo = get_pdo();
  $stmt = $pdo->prepare("SELECT id, name, email, role, password_hash FROM users WHERE email=:e LIMIT 1");
  $stmt->execute([':e'=>$email]);
  $user = $stmt->fetch();
  if(!$user || $user['role'] !== 'admin'){ return false; }
  if(!password_verify($password, $user['password_hash'])){ return false; }
  start_session();
  $_SESSION['admin'] = [
    'id' => (int)$user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
  ];
  return true;
}

function current_admin(): ?array {
  start_session();
  return $_SESSION['admin'] ?? null;
}

function require_admin(): void {
  if(!current_admin()){
    header('Location: /Final6/public/admin/login.php');
    exit;
  }
}

function logout_admin(): void {
  start_session();
  unset($_SESSION['admin']);
  session_write_close();
}


