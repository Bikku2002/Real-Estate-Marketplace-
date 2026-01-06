<?php
declare(strict_types=1);

/**
 * Simple PDO factory. Update credentials for your local MySQL.
 */
function get_pdo(): PDO {
  $host = '127.0.0.1';
  $database = 'final6';
  $username = 'root';
  $password = '';
  $charset = 'utf8mb4';

  $dsn = "mysql:host={$host};dbname={$database};charset={$charset}";
  $options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ];

  return new PDO($dsn, $username, $password, $options);
}


