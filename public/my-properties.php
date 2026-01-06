<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/user_auth.php';

// Check if user is logged in and is a seller
$currentUser = get_logged_in_user();
if (!$currentUser || $currentUser['role'] !== 'seller') {
    header('Location: login.php');
    exit;
}

$pdo = get_pdo();

// Handle property deletion
if (isset($_POST['delete_property']) && isset($_POST['property_id'])) {
    $propertyId = (int)$_POST['property_id'];
    
    // Verify the property belongs to the current user
    $stmt = $pdo->prepare("SELECT id FROM properties WHERE id = :id AND seller_id = :seller_id");
    $stmt->execute([':id' => $propertyId, ':seller_id' => $currentUser['id']]);
    
    if ($stmt->fetch()) {
        // Delete property images first
        $pdo->prepare("DELETE FROM property_images WHERE property_id = :id")->execute([':id' => $propertyId]);
        // Delete offers
        $pdo->prepare("DELETE FROM offers WHERE property_id = :id")->execute([':id' => $propertyId]);
        // Delete the property
        $pdo->prepare("DELETE FROM properties WHERE id = :id")->execute([':id' => $propertyId]);
        
        $success = 'Property deleted successfully!';
    }
}

// Fetch user's properties
$stmt = $pdo->prepare("
    SELECT p.*, 
           COUNT(DISTINCT o.id) as offer_count,
           COUNT(DISTINCT pi.id) as image_count
    FROM properties p 
    LEFT JOIN offers o ON p.id = o.property_id 
    LEFT JOIN property_images pi ON p.id = pi.property_id
    WHERE p.seller_id = :seller_id 
    GROUP BY p.id 
    ORDER BY p.created_at DESC
");
$stmt->execute([':seller_id' => $currentUser['id']]);
$properties = $stmt->fetchAll();

// Get some stats
$totalProperties = count($properties);
$totalOffers = array_sum(array_column($properties, 'offer_count'));
$totalViews = 0; // This would be implemented with a views tracking system
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>My Properties · NepaEstate</title>
    <meta name="description" content="Manage your property listings on NepaEstate"/>
    <link rel="stylesheet" href="assets/css/styles.css"/>
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, var(--accent), #e74c3c);
            color: white;
            padding: 32px;
            border-radius: 16px;
            margin-bottom: 32px;
            text-align: center;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-4px);
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: var(--accent);
            margin-bottom: 8px;
        }
        .stat-label {
            color: var(--muted);
            font-size: 14px;
        }
        .properties-grid {
            display: grid;
            gap: 20px;
        }
        .property-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .property-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .property-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid var(--line);
        }
        .property-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--ink);
            margin: 0;
        }
        .property-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-active {
            background: rgba(47, 176, 112, 0.1);
            color: #2fb070;
        }
        .property-content {
            padding: 20px;
        }
        .property-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        .detail-item {
            text-align: center;
        }
        .detail-label {
            font-size: 12px;
            color: var(--muted);
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .detail-value {
            font-weight: 600;
            color: var(--ink);
        }
        .property-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn-action {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-edit {
            background: var(--accent);
            color: white;
        }
        .btn-edit:hover {
            background: #b91c1c;
        }
        .btn-delete {
            background: #dc2626;
            color: white;
        }
        .btn-delete:hover {
            background: #b91c1c;
        }
        .btn-view {
            background: var(--brass);
            color: white;
        }
        .btn-view:hover {
            background: #d97706;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
        }
        .empty-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        .cta-section {
            background: var(--elev);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            margin-top: 32px;
        }
        .btn-primary-large {
            background: linear-gradient(135deg, var(--accent), #e74c3c);
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary-large:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(215, 38, 61, 0.3);
        }
        .offer-badge {
            background: var(--accent);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
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
                <a class="btn" href="favorites.php">❤️ My Favorites</a>
                <a class="btn" href="add-property.php">➕ Add Property</a>
                <a class="btn" href="profile.php">My Profile</a>
                <a class="btn btn-primary" href="logout.php">Logout</a>
            </div>
        </div>
    </header>

    <main class="container" style="margin-top: 20px;">
        <div class="dashboard-header">
            <h1>🏠 My Property Dashboard</h1>
            <p>Manage your listings and track buyer interest</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $totalProperties ?></div>
                <div class="stat-label">Total Properties</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $totalOffers ?></div>
                <div class="stat-label">Total Offers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $totalViews ?></div>
                <div class="stat-label">Total Views</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count(array_filter($properties, fn($p) => $p['offer_count'] > 0)) ?></div>
                <div class="stat-label">Properties with Offers</div>
            </div>
        </div>

        <?php if (empty($properties)): ?>
            <div class="empty-state">
                <div class="empty-icon">🏠</div>
                <h3>No Properties Listed Yet</h3>
                <p>Start your real estate journey by listing your first property!</p>
                <div class="cta-section">
                    <a href="add-property.php" class="btn-primary-large">
                        🚀 List Your First Property
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="properties-grid">
                <?php foreach ($properties as $property): ?>
                    <div class="property-card">
                        <div class="property-header">
                            <h3 class="property-title"><?= htmlspecialchars($property['title']) ?></h3>
                            <div class="property-status status-active">Active</div>
                        </div>
                        
                        <div class="property-content">
                            <div class="property-details">
                                <div class="detail-item">
                                    <div class="detail-label">Type</div>
                                    <div class="detail-value"><?= ucfirst($property['type']) ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Price</div>
                                    <div class="detail-value">Rs <?= number_format($property['price']) ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Location</div>
                                    <div class="detail-value"><?= htmlspecialchars($property['district']) ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Offers</div>
                                    <div class="detail-value">
                                        <?php if ($property['offer_count'] > 0): ?>
                                            <span class="offer-badge"><?= $property['offer_count'] ?></span>
                                        <?php else: ?>
                                            0
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Images</div>
                                    <div class="detail-value"><?= $property['image_count'] ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Listed</div>
                                    <div class="detail-value"><?= date('M j', strtotime($property['created_at'])) ?></div>
                                </div>
                            </div>
                            
                            <?php if ($property['type'] === 'house'): ?>
                                <div class="property-details" style="margin-top: 16px;">
                                    <div class="detail-item">
                                        <div class="detail-label">Bedrooms</div>
                                        <div class="detail-value"><?= $property['bedrooms'] ?? 'N/A' ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Bathrooms</div>
                                        <div class="detail-value"><?= $property['bathrooms'] ?? 'N/A' ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Area</div>
                                        <div class="detail-value">
                                            <?php if ($property['area_sqft']): ?>
                                                <?= number_format($property['area_sqft']) ?> sq ft
                                            <?php elseif ($property['area_ana']): ?>
                                                <?= $property['area_ana'] ?> ana
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="property-actions">
                                <a href="property.php?id=<?= $property['id'] ?>" class="btn-action btn-view">👁️ View</a>
                                <a href="edit-property.php?id=<?= $property['id'] ?>" class="btn-action btn-edit">✏️ Edit</a>
                                <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this property? This action cannot be undone.')">
                                    <input type="hidden" name="property_id" value="<?= $property['id'] ?>">
                                    <button type="submit" name="delete_property" class="btn-action btn-delete">🗑️ Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="cta-section">
                <h3>Want to list more properties?</h3>
                <p>Reach more potential buyers by adding more listings to your portfolio.</p>
                <a href="add-property.php" class="btn-primary-large">
                    ➕ Add Another Property
                </a>
            </div>
        <?php endif; ?>
    </main>

    <script>
        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to property cards
            const propertyCards = document.querySelectorAll('.property-card');
            propertyCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-4px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>
