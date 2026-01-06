<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/user_auth.php';

// Check if user is logged in
$currentUser = get_logged_in_user();
if (!$currentUser) {
    header('Location: login.php');
    exit;
}

$pdo = get_pdo();

// Handle AJAX requests for favorites
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    if (isset($_POST['add_favorite'])) {
        $propertyId = (int)$_POST['property_id'] ?? 0;
        if ($propertyId > 0) {
            try {
                // Check if already in favorites
                $checkStmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = :user_id AND property_id = :property_id");
                $checkStmt->execute([':user_id' => $currentUser['id'], ':property_id' => $propertyId]);
                
                if (!$checkStmt->fetch()) {
                    $stmt = $pdo->prepare("INSERT INTO favorites (user_id, property_id, created_at) VALUES (:user_id, :property_id, NOW())");
                    $stmt->execute([':user_id' => $currentUser['id'], ':property_id' => $propertyId]);
                    $response = ['success' => true, 'message' => 'Added to favorites successfully!'];
                } else {
                    $response = ['success' => true, 'message' => 'Already in favorites!'];
                }
            } catch (Exception $e) {
                $response = ['success' => false, 'message' => 'Error adding to favorites: ' . $e->getMessage()];
            }
        }
    } elseif (isset($_POST['remove_favorite'])) {
        $propertyId = (int)$_POST['property_id'] ?? 0;
        if ($propertyId > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = :user_id AND property_id = :property_id");
                $stmt->execute([':user_id' => $currentUser['id'], ':property_id' => $propertyId]);
                $response = ['success' => true, 'message' => 'Removed from favorites successfully!'];
            } catch (Exception $e) {
                $response = ['success' => false, 'message' => 'Error removing from favorites: ' . $e->getMessage()];
            }
        }
    }
    
    // If it's an AJAX request, return JSON response
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // If it's a regular form submission, redirect back
    if (isset($_POST['remove_favorite'])) {
        header('Location: favorites.php?removed=1');
        exit;
    }
}

// Get user's favorite properties
$stmt = $pdo->prepare("
    SELECT p.*, u.name AS seller_name, u.kyc_status, 
           COUNT(DISTINCT o.id) as offer_count,
           COUNT(DISTINCT pi.id) as image_count
    FROM favorites f
    JOIN properties p ON f.property_id = p.id
    JOIN users u ON p.seller_id = u.id
    LEFT JOIN offers o ON p.id = o.property_id
    LEFT JOIN property_images pi ON p.id = pi.property_id
    WHERE f.user_id = :user_id
    GROUP BY p.id
    ORDER BY f.created_at DESC
");
$stmt->execute([':user_id' => $currentUser['id']]);
$favorites = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>My Favorites · NepaEstate</title>
    <meta name="description" content="View and manage your favorite properties on NepaEstate."/>
    <link rel="stylesheet" href="assets/css/styles.css"/>
    <style>
        .favorites-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .favorites-header h1 {
            color: var(--accent);
            margin-bottom: 8px;
        }
        .favorites-count {
            color: var(--muted);
            font-size: 18px;
        }
        .favorites-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }
        .favorite-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .favorite-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .favorite-image {
            height: 200px;
            overflow: hidden;
            position: relative;
        }
        .favorite-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .favorite-type {
            position: absolute;
            top: 12px;
            left: 12px;
            background: var(--accent);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .favorite-actions {
            position: absolute;
            top: 12px;
            right: 12px;
            display: flex;
            gap: 8px;
        }
        .favorite-btn {
            background: rgba(0,0,0,0.7);
            color: white;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .favorite-btn:hover {
            background: var(--accent);
            transform: scale(1.1);
        }
        .favorite-content {
            padding: 20px;
        }
        .favorite-price {
            font-size: 24px;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 8px;
        }
        .favorite-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 8px;
            line-height: 1.3;
        }
        .favorite-location {
            color: var(--muted);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .favorite-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }
        .stat-item {
            text-align: center;
            padding: 8px;
            background: var(--elev);
            border-radius: 8px;
        }
        .stat-value {
            font-weight: 600;
            color: var(--ink);
            font-size: 16px;
        }
        .stat-label {
            font-size: 12px;
            color: var(--muted);
            text-transform: uppercase;
        }
        .favorite-seller {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 12px;
            border-top: 1px solid var(--line);
        }
        .seller-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .seller-name {
            font-weight: 600;
            color: var(--ink);
        }
        .kyc-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .kyc-verified {
            background: rgba(47, 176, 112, 0.2);
            color: #10b981;
        }
        .kyc-pending {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
        }
        .favorite-actions-bottom {
            display: flex;
            gap: 8px;
            margin-top: 16px;
        }
        .btn-view {
            flex: 1;
            background: var(--accent);
            color: white;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-view:hover {
            background: var(--brass);
            transform: translateY(-2px);
        }
        .btn-remove {
            background: #ef4444;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-remove:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
        }
        .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }
        .empty-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 8px;
        }
        .empty-desc {
            font-size: 16px;
            margin-bottom: 24px;
        }
        .btn-explore {
            background: var(--accent);
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }
        .btn-explore:hover {
            background: var(--brass);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container nav">
            <a class="brand" href="index.php">
                <div class="brand-logo"></div>
                <div class="brand-name">NepaEstate</div>
            </a>
            <div class="nav-actions">
                <a class="btn" href="index.php">Home</a>
                <?php if($currentUser['role'] === 'seller'): ?>
                    <a class="btn" href="add-property.php">Add Property</a>
                    <a class="btn" href="my-properties.php">My Properties</a>
                <?php elseif($currentUser['role'] === 'buyer'): ?>
                    <a class="btn" href="buyer-dashboard.php">Browse Properties</a>
                    <a class="btn" href="my-offers.php">My Offers</a>
                <?php endif; ?>
                <a class="btn" href="profile.php">My Profile</a>
                <a class="btn btn-primary" href="logout.php">Logout</a>
            </div>
        </div>
    </header>

    <main class="container" style="margin-top: 20px;">
        <div class="favorites-header">
            <h1>My Favorite Properties</h1>
            <div class="favorites-count">
                <?= count($favorites) ?> property<?= count($favorites) !== 1 ? 's' : '' ?> in your favorites
            </div>
        </div>

        <?php if (empty($favorites)): ?>
            <div class="empty-state">
                <div class="empty-icon">❤</div>
                <div class="empty-title">No favorites yet</div>
                <div class="empty-desc">Start exploring properties and add them to your favorites to see them here.</div>
                <a href="index.php" class="btn-explore">Explore Properties</a>
            </div>
        <?php else: ?>
            <div class="favorites-grid">
                <?php foreach ($favorites as $property): ?>
                    <div class="favorite-card">
                        <div class="favorite-image">
                            <img src="<?= htmlspecialchars($property['cover_image'] ?: 'assets/img/placeholder.svg') ?>" 
                                 alt="<?= htmlspecialchars($property['title']) ?>"
                                 onerror="this.onerror=null;this.src='assets/img/placeholder.svg'"/>
                            <div class="favorite-type"><?= ucfirst($property['type']) ?></div>
                            <div class="favorite-actions">
                                <button class="favorite-btn" title="Remove from favorites" 
                                        onclick="removeFavorite(<?= $property['id'] ?>)">❤</button>
                            </div>
                        </div>
                        
                        <div class="favorite-content">
                            <div class="favorite-price">
                                Rs <?= number_format($property['price']) ?>
                                <?php if($property['is_negotiable']): ?>
                                    <span style="background: #f59e0b; color: white; padding: 2px 6px; border-radius: 8px; font-size: 10px; margin-left: 6px;">💬</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="favorite-title">
                                <?= htmlspecialchars($property['title']) ?>
                            </div>
                            
                            <div class="favorite-location">
                                📍 <?= htmlspecialchars($property['district']) ?>
                                <?= $property['municipality'] ? ', ' . htmlspecialchars($property['municipality']) : '' ?>
                            </div>
                            
                            <div class="favorite-stats">
                                <div class="stat-item">
                                    <div class="stat-value"><?= $property['offer_count'] ?></div>
                                    <div class="stat-label">Offers</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?= $property['image_count'] ?></div>
                                    <div class="stat-label">Images</div>
                                </div>
                                <?php if($property['type'] === 'house'): ?>
                                    <div class="stat-item">
                                        <div class="stat-value"><?= $property['bedrooms'] ?? 'N/A' ?></div>
                                        <div class="stat-label">Beds</div>
                                    </div>
                                <?php else: ?>
                                    <div class="stat-item">
                                        <div class="stat-value"><?= $property['area_sqft'] ? number_format($property['area_sqft']) : 'N/A' ?></div>
                                        <div class="stat-label">Sq Ft</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="favorite-seller">
                                <div class="seller-info">
                                    <span class="seller-name"><?= htmlspecialchars($property['seller_name']) ?></span>
                                    <span class="kyc-badge kyc-<?= $property['kyc_status'] ?>">
                                        <?= ucfirst($property['kyc_status']) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="favorite-actions-bottom">
                                <a href="property.php?id=<?= $property['id'] ?>" class="btn-view">View Details</a>
                                <button class="btn-remove" onclick="removeFavorite(<?= $property['id'] ?>)">Remove</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <div class="container">© <?= date('Y') ?> NepaEstate</div>
    </footer>

    <script>
        function removeFavorite(propertyId) {
            if (confirm('Are you sure you want to remove this property from your favorites?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="remove_favorite" value="1">
                                <input type="hidden" name="property_id" value="${propertyId}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
