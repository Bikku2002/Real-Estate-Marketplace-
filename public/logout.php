<?php
declare(strict_types=1);
session_start();

// Clear all session data
session_destroy();

// Redirect to homepage
header('Location: index.php');
exit;
?>
