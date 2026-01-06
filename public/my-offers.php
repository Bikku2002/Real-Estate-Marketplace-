<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/user_auth.php';

// Check if user is logged in and is a buyer
$currentUser = get_logged_in_user();
if (!$currentUser || $currentUser['role'] !== 'buyer') {
    header('Location: login.php');
    exit;
}

$pdo = get_pdo();

// Get user's offers with property details
$stmt = $pdo->prepare("
    SELECT o.*, p.title, p.price, p.cover_image, p.type, p.district, p.municipality, p.is_negotiable,
           u.name AS seller_name, u.phone AS seller_phone, u.kyc_status
    FROM offers o 
    JOIN properties p ON o.property_id = p.id 
    JOIN users u ON p.seller_id = u.id
    WHERE o.buyer_id = :buyer_id 
    ORDER BY o.created_at DESC
");
$stmt->execute([':buyer_id' => $currentUser['id']]);
$offers = $stmt->fetchAll();

// Get some stats
$totalOffers = count($offers);
$pendingOffers = count(array_filter($offers, fn($o) => $o['status'] === 'pending'));
$acceptedOffers = count(array_filter($offers, fn($o) => $o['status'] === 'accepted'));
$rejectedOffers = count(array_filter($offers, fn($o) => $o['status'] === 'rejected'));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>My Offers · NepaEstate</title>
    <meta name="description" content="Track your property offers and negotiations on NepaEstate"/>
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
        .offers-grid {
            display: grid;
            gap: 20px;
        }
        .offer-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .offer-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .offer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid var(--line);
            background: var(--elev);
        }
        .offer-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--ink);
            margin: 0;
        }
        .offer-status {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }
        .status-accepted {
            background: rgba(47, 176, 112, 0.1);
            color: #2fb070;
        }
        .status-rejected {
            background: rgba(215, 38, 61, 0.1);
            color: #d7263d;
        }
        .status-countered {
            background: rgba(99, 102, 241, 0.1);
            color: #6366f1;
        }
        .offer-content {
            padding: 20px;
        }
        .property-info {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .property-image {
            width: 200px;
            height: 150px;
            border-radius: 8px;
            overflow: hidden;
        }
        .property-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .property-details h4 {
            margin: 0 0 12px 0;
            color: var(--ink);
        }
        .property-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }
        .meta-item {
            text-align: center;
            padding: 8px;
            background: var(--elev);
            border-radius: 6px;
        }
        .meta-label {
            font-size: 10px;
            color: var(--muted);
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .meta-value {
            font-weight: 600;
            color: var(--ink);
            font-size: 14px;
        }
        .offer-details {
            background: var(--elev);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }
        .offer-amount {
            font-size: 24px;
            font-weight: bold;
            color: var(--accent);
            margin-bottom: 8px;
        }
        .offer-message {
            color: var(--ink);
            margin-bottom: 12px;
            font-style: italic;
        }
        .offer-date {
            color: var(--muted);
            font-size: 14px;
        }
        .seller-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: var(--elev);
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .seller-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }
        .seller-details h4 {
            margin: 0 0 4px 0;
            color: var(--ink);
        }
        .seller-details p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
        }
        .kyc-badge {
            padding: 2px 8px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            margin-left: 8px;
        }
        .kyc-verified {
            background: rgba(47, 176, 112, 0.1);
            color: #2fb070;
        }
        .kyc-pending {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }
        .offer-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn-action {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-view {
            background: var(--brass);
            color: white;
        }
        .btn-view:hover {
            background: #d97706;
        }
        .btn-contact {
            background: var(--accent);
            color: white;
        }
        .btn-contact:hover {
            background: #b91c1c;
        }
        .btn-withdraw {
            background: #dc2626;
            color: white;
        }
        .btn-withdraw:hover {
            background: #b91c1c;
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
        .negotiable-badge {
            background: #f59e0b;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 8px;
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
                <a class="btn" href="buyer-dashboard.php">🔍 Browse Properties</a>
                <a class="btn" href="profile.php">My Profile</a>
                <a class="btn btn-primary" href="logout.php">Logout</a>
            </div>
        </div>
    </header>

    <main class="container" style="margin-top: 20px;">
        <div class="dashboard-header">
            <h1>💰 My Property Offers</h1>
            <p>Track your offers and negotiations with property sellers</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $totalOffers ?></div>
                <div class="stat-label">Total Offers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $pendingOffers ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $acceptedOffers ?></div>
                <div class="stat-label">Accepted</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $rejectedOffers ?></div>
                <div class="stat-label">Rejected</div>
            </div>
        </div>

        <?php if (empty($offers)): ?>
            <div class="empty-state">
                <div class="empty-icon">💰</div>
                <h3>No Offers Made Yet</h3>
                <p>Start browsing properties and make your first offer!</p>
                <div class="cta-section">
                    <a href="buyer-dashboard.php" class="btn-primary-large">
                        🔍 Browse Properties
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="offers-grid">
                <?php foreach ($offers as $offer): ?>
                    <div class="offer-card">
                        <div class="offer-header">
                            <h3 class="offer-title"><?= htmlspecialchars($offer['title']) ?></h3>
                            <div class="offer-status status-<?= $offer['status'] ?>">
                                <?= ucfirst($offer['status']) ?>
                            </div>
                        </div>
                        
                        <div class="offer-content">
                            <div class="property-info">
                                <div class="property-image">
                                    <img src="<?= $offer['cover_image'] ?: 'assets/img/placeholder.svg' ?>" 
                                         alt="<?= htmlspecialchars($offer['title']) ?>"
                                         onerror="this.src='assets/img/placeholder.svg'"/>
                                </div>
                                
                                <div class="property-details">
                                    <h4><?= htmlspecialchars($offer['title']) ?></h4>
                                    <div class="property-meta">
                                        <div class="meta-item">
                                            <div class="meta-label">Type</div>
                                            <div class="meta-value"><?= ucfirst($offer['type']) ?></div>
                                        </div>
                                        <div class="meta-item">
                                            <div class="meta-label">Location</div>
                                            <div class="meta-value">
                                                <?= htmlspecialchars($offer['district']) ?>
                                                <?php if ($offer['municipality']): ?>
                                                    , <?= htmlspecialchars($offer['municipality']) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="meta-item">
                                            <div class="meta-label">Listed Price</div>
                                            <div class="meta-value">
                                                Rs <?= number_format($offer['price']) ?>
                                                <?php if ($offer['is_negotiable']): ?>
                                                    <span class="negotiable-badge">💬</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="offer-details">
                                <div class="offer-amount">
                                    Your Offer: Rs <?= number_format($offer['offer_amount']) ?>
                                </div>
                                <?php if ($offer['message']): ?>
                                    <div class="offer-message">"<?= htmlspecialchars($offer['message']) ?>"</div>
                                <?php endif; ?>
                                <div class="offer-date">
                                    Offered on <?= date('F j, Y', strtotime($offer['created_at'])) ?>
                                </div>
                            </div>
                            
                            <div class="seller-info">
                                <div class="seller-avatar">
                                    <?= strtoupper(substr($offer['seller_name'], 0, 1)) ?>
                                </div>
                                <div class="seller-details">
                                    <h4><?= htmlspecialchars($offer['seller_name']) ?></h4>
                                    <p>
                                        <span class="kyc-badge kyc-<?= $offer['kyc_status'] ?>">
                                            <?= ucfirst($offer['kyc_status']) ?>
                                        </span>
                                        <?php if ($offer['seller_phone']): ?>
                                            · <?= htmlspecialchars($offer['seller_phone']) ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="offer-actions">
                                <a href="property.php?id=<?= $offer['property_id'] ?>" class="btn-action btn-view">
                                    👁️ View Property
                                </a>
                                <?php if ($offer['status'] === 'pending'): ?>
                                    <button class="btn-action btn-withdraw" onclick="withdrawOffer(<?= $offer['id'] ?>)">
                                        ❌ Withdraw Offer
                                    </button>
                                <?php endif; ?>
                                <?php if ($offer['status'] === 'accepted'): ?>
                                    <button class="btn-action btn-contact" onclick="contactSeller('<?= htmlspecialchars($offer['seller_name']) ?>', '<?= htmlspecialchars($offer['seller_phone'] ?? '') ?>')">
                                        📞 Contact Seller
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="cta-section">
                <h3>Want to make more offers?</h3>
                <p>Browse through our extensive collection of properties and find your perfect match.</p>
                <a href="buyer-dashboard.php" class="btn-primary-large">
                    🔍 Browse More Properties
                </a>
            </div>
        <?php endif; ?>
    </main>

    <script>
        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to offer cards
            const offerCards = document.querySelectorAll('.offer-card');
            offerCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-4px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });

        // Withdraw offer functionality
        function withdrawOffer(offerId) {
            if (confirm('Are you sure you want to withdraw this offer?')) {
                // This would integrate with an API to withdraw the offer
                alert('Offer withdrawal functionality coming soon!');
            }
        }

        // Contact seller functionality
        function contactSeller(sellerName, sellerPhone) {
            if (sellerPhone) {
                alert(`Contact ${sellerName} at ${sellerPhone}`);
            } else {
                alert(`Contact ${sellerName} through the platform messaging system.`);
            }
        }
    </script>
</body>
</html>
