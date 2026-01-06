<?php
declare(strict_types=1);

function is_user_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function get_logged_in_user(): ?array {
    if (!is_user_logged_in()) {
        return null;
    }
    
    try {
        $pdo = get_pdo();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND role != 'admin'");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    } catch (Exception $e) {
        return null;
    }
}

function require_user_login(): void {
    if (!is_user_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

// Logout function removed - simplified to just destroy session
?>
