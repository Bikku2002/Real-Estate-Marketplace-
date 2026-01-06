<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/content_based_filtering.php';

// Check if user is logged in
$currentUser = get_logged_in_user();
$userId = $currentUser ? $currentUser['id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'track_view') {
    $propertyId = (int)($_POST['property_id'] ?? 0);
    
    if ($propertyId > 0) {
        try {
            $pdo = get_pdo();
            $contentFiltering = new ContentBasedFiltering($pdo);
            
            // Track the property view
            $context = [
                'source_page' => $_SERVER['HTTP_REFERER'] ?? 'buyer-dashboard',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
            ];
            
            $success = $contentFiltering->trackPropertyView($propertyId, $userId, $context);
            
            // Update user preferences based on this view
            if ($userId) {
                $contentFiltering->updateUserPreferences($userId, [
                    ['type' => 'property_view', 'property_id' => $propertyId]
                ]);
            }
            
            if ($success) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'View tracked successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to track view']);
            }
            
        } catch (Exception $e) {
            error_log("Error tracking property view: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid property ID']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
