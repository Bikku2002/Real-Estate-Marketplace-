<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/user_auth.php';
require_once __DIR__ . '/../config/nepal_area_units.php';
require_once __DIR__ . '/../config/content_based_filtering.php';
require_once __DIR__ . '/../config/property_availability_manager.php';

// Check if user is logged in and is a buyer
$currentUser = get_logged_in_user();
if (!$currentUser || $currentUser['role'] !== 'buyer') {
    header('Location: login.php');
    exit;
}

$pdo = get_pdo();

// Get search and filter parameters
$search = trim($_GET['search'] ?? '');
$district = $_GET['district'] ?? '';
$type = $_GET['type'] ?? '';
$min_price = !empty($_GET['min_price']) ? (int)$_GET['min_price'] : null;
$max_price = !empty($_GET['max_price']) ? (int)$_GET['max_price'] : null;
$area_min = !empty($_GET['area_min']) ? (float)$_GET['area_min'] : null;
$area_max = !empty($_GET['area_max']) ? (float)$_GET['area_max'] : null;
$area_unit = $_GET['area_unit'] ?? 'sqft';
$is_negotiable = $_GET['is_negotiable'] ?? '';

// Build the SQL query with filters
$where_conditions = ['p.status = "active"'];
$params = [];

if (!empty($search)) {
    $where_conditions[] = '(p.title LIKE :search OR p.description LIKE :search OR p.municipality LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

if (!empty($district)) {
    $where_conditions[] = 'p.district = :district';
    $params[':district'] = $district;
}

if (!empty($type)) {
    $where_conditions[] = 'p.type = :type';
    $params[':type'] = $type;
}

if ($min_price !== null) {
    $where_conditions[] = 'p.price >= :min_price';
    $params[':min_price'] = $min_price;
}

if ($max_price !== null) {
    $where_conditions[] = 'p.price <= :max_price';
    $params[':max_price'] = $max_price;
}

// Area-based filtering
if ($area_min !== null || $area_max !== null) {
    if ($area_unit === 'sqft') {
        if ($area_min !== null) {
            $where_conditions[] = 'p.area_sqft >= :area_min';
            $params[':area_min'] = $area_min;
        }
        if ($area_max !== null) {
            $where_conditions[] = 'p.area_sqft <= :area_max';
            $params[':area_max'] = $area_max;
        }
    } elseif ($area_unit === 'ana') {
        if ($area_min !== null) {
            $where_conditions[] = 'p.area_ana >= :area_min';
            $params[':area_min'] = $area_min;
        }
        if ($area_max !== null) {
            $where_conditions[] = 'p.area_ana <= :area_max';
            $params[':area_max'] = $area_max;
        }
    } else {
        // Convert other units to sqft for filtering
        if ($area_min !== null) {
            $min_sqft = NepalAreaUnits::convert($area_min, $area_unit, 'sqft');
            $where_conditions[] = 'p.area_sqft >= :area_min';
            $params[':area_min'] = $min_sqft;
        }
        if ($area_max !== null) {
            $max_sqft = NepalAreaUnits::convert($area_max, $area_unit, 'sqft');
            $where_conditions[] = 'p.area_sqft <= :area_max';
            $params[':area_max'] = $max_sqft;
        }
    }
}

if ($is_negotiable !== '') {
    $where_conditions[] = 'p.is_negotiable = :is_negotiable';
    $params[':is_negotiable'] = $is_negotiable === 'yes' ? 1 : 0;
}

$where_clause = implode(' AND ', $where_conditions);

// Get properties with filters
$sql = "SELECT p.*, u.name as seller_name, u.phone as seller_phone, u.email as seller_email,
               (SELECT COUNT(*) FROM favorites WHERE property_id = p.id AND user_id = :current_user_id) as is_favorite
        FROM properties p 
        LEFT JOIN users u ON p.seller_id = u.id 
        WHERE $where_clause 
        ORDER BY p.created_at DESC";

$params[':current_user_id'] = $currentUser['id'];
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$properties = $stmt->fetchAll();

// Get user's favorite property IDs
$favoriteStmt = $pdo->prepare("SELECT property_id FROM favorites WHERE user_id = :user_id");
$favoriteStmt->execute([':user_id' => $currentUser['id']]);
$favoritePropertyIds = array_column($favoriteStmt->fetchAll(), 'property_id');

// Get user's recent offers
$offersStmt = $pdo->prepare("
    SELECT p.title, p.price, p.type, p.district, o.offer_amount, o.status, o.created_at
    FROM offers o
    JOIN properties p ON o.property_id = p.id
    WHERE o.buyer_id = :buyer_id
    ORDER BY o.created_at DESC
    LIMIT 5
");
$offersStmt->execute([':buyer_id' => $currentUser['id']]);
$userOffers = $offersStmt->fetchAll();

// Initialize content-based filtering and property availability manager
$contentFiltering = new ContentBasedFiltering($pdo);
$availabilityManager = new PropertyAvailabilityManager($pdo);

// Get personalized property recommendations
$recommendations = $contentFiltering->getPersonalizedRecommendations($currentUser['id'], 6);

// Get trending properties
$trendingProperties = $contentFiltering->getTrendingProperties(6);

// Get properties based on user's search history
$searchBasedProperties = $contentFiltering->getPropertiesBasedOnSearchHistory($currentUser['id'], 4);

// Track current search for better future recommendations
if (!empty($search) || !empty($district) || !empty($type)) {
    $searchData = [
        'search' => $search,
        'district' => $district,
        'type' => $type,
        'min_price' => $min_price,
        'max_price' => $max_price,
        'area_min' => $area_min,
        'area_max' => $area_max,
        'area_unit' => $area_unit
    ];
    
    $contentFiltering->updateUserPreferences($currentUser['id'], [
        ['type' => 'property_search', 'search_data' => $searchData]
    ]);
}

// Get districts for filter
$districts = [
    'Achham', 'Arghakhanchi', 'Baglung', 'Baitadi', 'Bajhang', 'Bajura', 'Banke', 'Bara', 'Bardiya', 'Bhaktapur',
    'Bhojpur', 'Chitwan', 'Dadeldhura', 'Dailekh', 'Dang', 'Darchula', 'Dhading', 'Dhankuta', 'Dhanusa', 'Dolakha',
    'Dolpa', 'Doti', 'Gorkha', 'Gulmi', 'Humla', 'Ilam', 'Jajarkot', 'Jhapa', 'Jumla', 'Kailali',
    'Kalikot', 'Kanchanpur', 'Kapilvastu', 'Kaski', 'Kathmandu', 'Kavrepalanchok', 'Khotang', 'Lalitpur', 'Lamjung', 'Mahottari',
    'Makwanpur', 'Manang', 'Morang', 'Mugu', 'Mustang', 'Myagdi', 'Nawalparasi East', 'Nawalparasi West', 'Nuwakot', 'Okhaldhunga',
    'Palpa', 'Panchthar', 'Parbat', 'Parsa', 'Pyuthan', 'Ramechhap', 'Rasuwa', 'Rautahat', 'Rolpa', 'Rukum East',
    'Rukum West', 'Rupandehi', 'Salyan', 'Sankhuwasabha', 'Saptari', 'Sarlahi', 'Sindhuli', 'Sindhupalchok', 'Siraha', 'Solukhumbu',
    'Sunsari', 'Surkhet', 'Syangja', 'Tanahu', 'Taplejung', 'Terhathum', 'Udayapur'
];

// Get available area units
$areaUnits = NepalAreaUnits::getAvailableUnits();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Buyer Dashboard · RealEstate</title>
    <meta name="description" content="Find your dream property in Nepal. Browse listings, save favorites, and make offers."/>
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
        .search-filters {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 32px;
        }
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        .filter-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--ink);
        }
        .filter-input, .filter-select {
            padding: 12px;
            border: 2px solid var(--line);
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        .filter-input:focus, .filter-select:focus {
            outline: none;
            border-color: var(--accent);
        }
        
        /* District dropdown enhancements */
        #districtSelect {
            position: relative;
        }
        
        #districtSelect option {
            padding: 8px 12px;
            cursor: pointer;
        }
        
        #districtSelect option:hover {
            background-color: var(--accent);
            color: white;
        }
        
        /* Filter group enhancements */
        .filter-group small {
            font-size: 12px;
            opacity: 0.8;
        }
        .search-btn {
            background: var(--accent);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .search-btn:hover {
            background: #b91c1c;
            transform: translateY(-2px);
        }
        .properties-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        .property-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .property-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .property-image {
            position: relative;
            height: 200px;
            overflow: hidden;
        }
        .property-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .property-card:hover .property-image img {
            transform: scale(1.05);
        }
        .property-type {
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
        .negotiable-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: var(--brass);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
        }
        .property-content {
            padding: 20px;
        }
        .property-price {
            font-size: 24px;
            font-weight: bold;
            color: var(--accent);
            margin-bottom: 8px;
        }
        .property-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 8px;
            line-height: 1.4;
        }
        .property-location {
            color: var(--muted);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .property-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }
        .detail-item {
            text-align: center;
            padding: 8px;
            background: var(--elev);
            border-radius: 8px;
        }
        .detail-label {
            font-size: 10px;
            color: var(--muted);
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .detail-value {
            font-weight: 600;
            color: var(--ink);
            font-size: 14px;
        }
        .seller-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: var(--elev);
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .seller-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .seller-details h4 {
            margin: 0 0 4px 0;
            font-size: 14px;
            color: var(--ink);
        }
        .seller-details p {
            margin: 0;
            font-size: 12px;
            color: var(--muted);
        }
        .kyc-badge {
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .kyc-verified {
            background: rgba(47, 176, 112, 0.1);
            color: #2fb070;
        }
        .kyc-pending {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }
        .property-actions {
            display: flex;
            gap: 12px;
        }
        .btn-action {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-view {
            background: var(--brass);
            color: white;
        }
        .btn-view:hover {
            background: #d97706;
        }
        .btn-offer {
            background: var(--accent);
            color: white;
        }
        .btn-offer:hover {
            background: #b91c1c;
        }
        .btn-favorite {
            background: var(--elev);
            color: var(--ink);
            border: 2px solid var(--line);
        }
        .btn-favorite:hover {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }
        
        .btn-favorite.favorited {
            background: var(--success);
            color: white;
            border-color: var(--success);
        }
        
        .btn-favorite.favorited:hover {
            background: #dc2626;
            border-color: #dc2626;
        }
        .offers-section {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 32px;
        }
        .offers-grid {
            display: grid;
            gap: 16px;
        }
        .offer-card {
            background: var(--elev);
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .offer-info h4 {
            margin: 0 0 4px 0;
            color: var(--ink);
        }
        .offer-info p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
        }
        .offer-status {
            padding: 4px 12px;
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
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
        }
        .empty-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container nav">
            <a class="brand" href="index.php">
                <div class="brand-logo"></div>
                <div class="brand-name">RealEstate</div>
            </a>
            <div class="nav-actions">
                <a class="btn" href="area-search.php">🔍 Area Search</a>
                <a class="btn" href="favorites.php">My Favorites</a>
                <a class="btn" href="profile.php">My Profile</a>
                <a class="btn" href="my-offers.php">My Offers</a>
                <a class="btn btn-primary" href="logout.php">Logout</a>
            </div>
        </div>
    </header>

    <main class="container" style="margin-top: 20px;">
        <div class="dashboard-header">
            <h1>Find Your Dream Property</h1>
            <p>Browse through thousands of properties across Nepal. Save favorites and make offers on properties you love.</p>
        </div>

        <div class="search-filters">
            <h3 style="margin-top: 0; margin-bottom: 20px; color: var(--accent);">Search & Filters</h3>
            <form method="get" class="filters-grid">
                <div class="filter-group">
                    <label class="filter-label">Property Type</label>
                    <select class="filter-select" name="type">
                        <option value="">All Types</option>
                        <option value="land" <?= $type === 'land' ? 'selected' : '' ?>>Land</option>
                        <option value="house" <?= $type === 'house' ? 'selected' : '' ?>>House</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">District</label>
                    <select class="filter-select" name="district" id="districtSelect">
                        <option value="">All Districts (<?= count($districts) ?> available)</option>
                        <?php foreach ($districts as $dist): ?>
                            <option value="<?= $dist ?>" <?= $district === $dist ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dist) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: var(--muted); margin-top: 4px; display: block;">
                        Type to search through <?= count($districts) ?> districts of Nepal
                    </small>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Keywords</label>
                    <input class="filter-input" type="text" name="search" 
                           placeholder="garden, parking, ring road..." value="<?= htmlspecialchars($search) ?>"/>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Min Price (Rs)</label>
                    <input class="filter-input" type="number" name="min_price" 
                           placeholder="e.g., 5000000" value="<?= htmlspecialchars($min_price ?: '') ?>"/>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Max Price (Rs)</label>
                    <input class="filter-input" type="number" name="max_price" 
                           placeholder="e.g., 15000000" value="<?= htmlspecialchars($max_price ?: '') ?>"/>
                </div>
                
                <!-- Area-based filtering -->
                <div class="filter-group">
                    <label class="filter-label">Area Range</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px;">
                        <input class="filter-input" type="number" name="area_min" 
                               placeholder="Min" step="0.01" min="0.01"
                               value="<?= htmlspecialchars($area_min ?: '') ?>"/>
                        <input class="filter-input" type="number" name="area_max" 
                               placeholder="Max" step="0.01" min="0.01"
                               value="<?= htmlspecialchars($area_max ?: '') ?>"/>
                        <select class="filter-select" name="area_unit">
                            <?php foreach ($areaUnits as $unitKey => $unitInfo): ?>
                                <option value="<?= $unitKey ?>" <?= $area_unit === $unitKey ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($unitInfo['short']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Negotiable</label>
                    <select class="filter-select" name="is_negotiable">
                        <option value="">All</option>
                        <option value="yes" <?= $is_negotiable === 'yes' ? 'selected' : '' ?>>Yes</option>
                        <option value="no" <?= $is_negotiable === 'no' ? 'selected' : '' ?>>No</option>
                    </select>
                </div>
                
                <div class="filter-group" style="display: flex; align-items: end;">
                    <button type="submit" class="search-btn">Search</button>
                </div>
            </form>
        </div>

        <!-- Content-Based Recommendations Section -->
        <?php if (!empty($recommendations)): ?>
            <div class="recommendations-section" style="margin-bottom: 32px;">
                <h3 style="margin-top: 0; margin-bottom: 20px; color: var(--accent);">🎯 Personalized Recommendations</h3>
                <div class="recommendations-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <?php foreach ($recommendations as $property): ?>
                        <div class="property-card recommendation-card" style="position: relative; border: 2px solid var(--accent);">
                            <div class="recommendation-badge" style="position: absolute; top: 12px; left: 12px; background: var(--accent); color: white; padding: 4px 8px; border-radius: 12px; font-size: 10px; font-weight: 600; z-index: 1;">
                                ⭐ Recommended for you
                            </div>
                            <div class="property-image" style="height: 200px; background: linear-gradient(135deg, var(--elev), var(--line)); border-radius: 12px 12px 0 0; display: flex; align-items: center; justify-content: center; color: var(--muted);">
                                🏠 Property Image
                            </div>
                            <div class="property-content">
                                <h4 class="property-title"><?= htmlspecialchars($property['title']) ?></h4>
                                <p class="property-location">📍 <?= htmlspecialchars($property['district']) ?></p>
                                <div class="property-details">
                                    <span class="property-type">🏠 <?= ucfirst($property['type']) ?></span>
                                    <span class="property-price">💰 Rs <?= number_format($property['price']) ?></span>
                                    <span class="property-area">📏 <?= number_format($property['area_sqft']) ?> sq ft</span>
                                </div>
                                <div class="recommendation-score" style="margin-top: 12px; padding: 8px; background: var(--elev); border-radius: 8px; text-align: center;">
                                    <small style="color: var(--muted);">Match Score: <?= number_format($property['recommendation_score'] * 100, 1) ?>%</small>
                                </div>
                                <div style="margin-top: 16px;">
                                    <a href="property-details.php?id=<?= $property['id'] ?>" class="btn btn-primary" style="width: 100%;">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Trending Properties Section -->
        <?php if (!empty($trendingProperties)): ?>
            <div class="trending-section" style="margin-bottom: 32px;">
                <h3 style="margin-top: 0; margin-bottom: 20px; color: var(--accent);">🔥 Trending Properties</h3>
                <div class="trending-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <?php foreach ($trendingProperties as $property): ?>
                        <div class="property-card trending-card" style="position: relative; border: 2px solid #ff6b35;">
                            <div class="trending-badge" style="position: absolute; top: 12px; left: 12px; background: #ff6b35; color: white; padding: 4px 8px; border-radius: 12px; font-size: 10px; font-weight: 600; z-index: 1;">
                                🔥 Trending
                            </div>
                            <div class="property-image" style="height: 200px; background: linear-gradient(135deg, var(--elev), var(--line)); border-radius: 12px 12px 0 0; display: flex; align-items: center; justify-content: center; color: var(--muted);">
                                🏠 Property Image
                            </div>
                            <div class="property-content">
                                <h4 class="property-title"><?= htmlspecialchars($property['title']) ?></h4>
                                <p class="property-location">📍 <?= htmlspecialchars($property['district']) ?></p>
                                <div class="property-details">
                                    <span class="property-type">🏠 <?= ucfirst($property['type']) ?></span>
                                    <span class="property-price">💰 Rs <?= number_format($property['price']) ?></span>
                                    <span class="property-area">📏 <?= number_format($property['area_sqft']) ?> sq ft</span>
                                </div>
                                <div class="trending-stats" style="margin-top: 12px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; text-align: center;">
                                    <div style="padding: 8px; background: var(--elev); border-radius: 8px;">
                                        <small style="color: var(--muted);">👁️ <?= $property['view_count'] ?? 0 ?></small>
                                    </div>
                                    <div style="padding: 8px; background: var(--elev); border-radius: 8px;">
                                        <small style="padding: 8px; background: var(--elev); border-radius: 8px;">
                                        <small style="color: var(--muted);">❤️ <?= $property['favorite_count'] ?? 0 ?></small>
                                    </div>
                                    <div style="padding: 8px; background: var(--elev); border-radius: 8px;">
                                        <small style="color: var(--muted);">💰 <?= $property['offer_count'] ?? 0 ?></small>
                                    </div>
                                </div>
                                <div style="margin-top: 16px;">
                                    <a href="property-details.php?id=<?= $property['id'] ?>" class="btn btn-primary" style="width: 100%;">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Search-Based Properties Section -->
        <?php if (!empty($searchBasedProperties)): ?>
            <div class="search-based-section" style="margin-bottom: 32px;">
                <h3 style="margin-top: 0; margin-bottom: 20px; color: var(--accent);">🔍 Based on Your Searches</h3>
                <div class="search-based-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <?php foreach ($searchBasedProperties as $property): ?>
                        <div class="property-card search-based-card" style="position: relative; border: 2px solid #6c5ce7;">
                            <div class="search-based-badge" style="position: absolute; top: 12px; left: 12px; background: #6c5ce7; color: white; padding: 4px 8px; border-radius: 12px; font-size: 10px; font-weight: 600; z-index: 1;">
                                🔍 Search Match
                            </div>
                            <div class="property-image" style="height: 200px; background: linear-gradient(135deg, var(--elev), var(--line)); border-radius: 12px 12px 0 0; display: flex; align-items: center; justify-content: center; color: var(--muted);">
                                🏠 Property Image
                            </div>
                            <div class="property-content">
                                <h4 class="property-title"><?= htmlspecialchars($property['title']) ?></h4>
                                <p class="property-location">📍 <?= htmlspecialchars($property['district']) ?></p>
                                <div class="property-details">
                                    <span class="property-type">🏠 <?= ucfirst($property['type']) ?></span>
                                    <span class="property-price">💰 Rs <?= number_format($property['price']) ?></span>
                                    <span class="property-area">📏 <?= number_format($property['area_sqft']) ?> sq ft</span>
                                </div>
                                <div style="margin-top: 16px;">
                                    <a href="property-details.php?id=<?= $property['id'] ?>" class="btn btn-primary" style="width: 100%;">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($properties)): ?>
            <div class="empty-state">
                <div class="empty-icon">🔍</div>
                <h3>No Properties Found</h3>
                <p>Try adjusting your search criteria or browse all properties.</p>
                <a href="buyer-dashboard.php" class="btn btn-primary">View All Properties</a>
            </div>
        <?php else: ?>
            <h2 style="margin-bottom: 20px; color: var(--ink);">
                📋 Found <?= count($properties) ?> Properties
            </h2>
            
            <div class="properties-grid">
                <?php foreach ($properties as $property): ?>
                    <div class="property-card" data-property-id="<?= $property['id'] ?>" onclick="window.location.href='property.php?id=<?= $property['id'] ?>'">
                        <div class="property-image">
                            <img src="<?= $property['cover_image'] ?: 'assets/img/placeholder.svg' ?>" 
                                 alt="<?= htmlspecialchars($property['title']) ?>"
                                 onerror="this.src='assets/img/placeholder.svg'"/>
                            <div class="property-type"><?= ucfirst($property['type']) ?></div>
                            <?php if ($property['is_negotiable']): ?>
                                <div class="negotiable-badge">💬 Negotiable</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="property-info">
                            <h3 class="property-title"><?= htmlspecialchars($property['title']) ?></h3>
                            <p class="property-location">📍 <?= htmlspecialchars($property['district']) ?><?= !empty($property['municipality']) ? ', ' . htmlspecialchars($property['municipality']) : '' ?></p>
                            
                            <div class="property-details">
                                <span class="property-type">🏠 <?= ucfirst($property['type']) ?></span>
                                <span class="property-price">💰 Rs <?= number_format($property['price']) ?></span>
                                
                                <!-- Enhanced area display with multiple units -->
                                <?php if ($property['area_unit'] && $property['area_unit'] !== 'sqft'): ?>
                                    <?php 
                                    $primaryArea = $property['area_unit'] === 'ana' ? $property['area_ana'] : $property['area_sqft'];
                                    $areaConversions = NepalAreaUnits::getAreaInMultipleUnits($primaryArea, $property['area_unit']);
                                    ?>
                                    <span class="property-area" title="<?= htmlspecialchars(implode(' | ', array_map(function($unit, $value) { return $value . ' ' . $unit; }, array_keys($areaConversions), $areaConversions))) ?>">
                                        📏 <?= NepalAreaUnits::formatArea($primaryArea, $property['area_unit']) ?>
                                        <small style="color: var(--muted); display: block; font-size: 10px;">
                                            ≈ <?= number_format($property['area_sqft']) ?> sq ft
                                        </small>
                                    </span>
                                <?php else: ?>
                                    <span class="property-area" title="Click to see other units">
                                        📏 <?= number_format($property['area_sqft']) ?> sq ft
                                        <?php if ($property['area_ana']): ?>
                                            <small style="color: var(--muted); display: block; font-size: 10px;">
                                                ≈ <?= number_format($property['area_ana'], 2) ?> Ana
                                            </small>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($property['type'] === 'house'): ?>
                                    <span class="property-rooms">🛏️ <?= $property['bedrooms'] ?> Beds, 🚿 <?= $property['bathrooms'] ?> Baths</span>
                                <?php endif; ?>
                                
                                <?php if ($property['is_negotiable']): ?>
                                    <span class="negotiable-badge">💬 Negotiable</span>
                                <?php endif; ?>
                            </div>
                            
                            <p class="property-description"><?= htmlspecialchars(substr($property['description'] ?? '', 0, 150)) ?><?= strlen($property['description'] ?? '') > 150 ? '...' : '' ?></p>
                            
                            <div class="seller-info">
                                <small>👤 <?= htmlspecialchars($property['seller_name']) ?></small>
                                <?php if ($property['seller_phone']): ?>
                                    <small>📞 <?= htmlspecialchars($property['seller_phone']) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($userOffers)): ?>
            <div class="offers-section">
                <h3 style="margin-top: 0; margin-bottom: 20px; color: var(--accent);">💰 My Recent Offers</h3>
                <div class="offers-grid">
                    <?php foreach ($userOffers as $offer): ?>
                        <div class="offer-card">
                            <div class="offer-info">
                                <h4><?= htmlspecialchars($offer['title']) ?></h4>
                                <p>
                                    Your offer: Rs <?= number_format($offer['offer_amount']) ?> 
                                    (Listed: Rs <?= number_format($offer['price']) ?>)
                                    · <?= ucfirst($offer['type']) ?> in <?= htmlspecialchars($offer['district']) ?>
                                </p>
                            </div>
                            <div class="offer-status status-<?= $offer['status'] ?>">
                                <?= ucfirst($offer['status']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="my-offers.php" class="btn btn-primary">View All My Offers</a>
                </div>
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

            // Track property views for better recommendations
            propertyCards.forEach(card => {
                card.addEventListener('click', function() {
                    const propertyId = this.getAttribute('data-property-id');
                    if (propertyId) {
                        // Track property view
                        trackPropertyView(propertyId);
                    }
                });
            });
        });

        // Function to track property views
        function trackPropertyView(propertyId) {
            const formData = new FormData();
            formData.append('action', 'track_view');
            formData.append('property_id', propertyId);
            
            fetch('track_property_view.php', {
                method: 'POST',
                body: formData
            }).catch(error => {
                console.log('Property view tracking failed:', error);
            });
        }

        // Toggle favorite functionality
        function toggleFavorite(propertyId) {
            const button = event.target;
            const isFavorite = button.classList.contains('favorited');
            
            // Create form data
            const formData = new FormData();
            formData.append('property_id', propertyId);
            formData.append(isFavorite ? 'remove_favorite' : 'add_favorite', '1');
            
            // Send AJAX request
            fetch('favorites.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Toggle button state
                    if (isFavorite) {
                        button.classList.remove('favorited');
                        button.innerHTML = 'Add to Favorites';
                        button.style.background = 'var(--accent)';
                        button.style.color = 'white';
                    } else {
                        button.classList.add('favorited');
                        button.innerHTML = 'Remove from Favorites';
                        button.style.background = 'var(--success)';
                        button.style.color = 'white';
                    }
                } else {
                    alert('Error updating favorite: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating favorite. Please try again.');
            });
        }

        // Auto-submit form when filters change
        document.querySelectorAll('.filter-select, .filter-input').forEach(input => {
            input.addEventListener('change', function() {
                if (this.name === 'q') return; // Don't auto-submit on text input
                this.closest('form').submit();
            });
        });

        // Make district dropdown searchable
        const districtSelect = document.getElementById('districtSelect');
        if (districtSelect) {
            districtSelect.addEventListener('keyup', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                const options = Array.from(this.options);
                
                options.forEach(option => {
                    if (option.value === '') return; // Skip "All Districts" option
                    
                    const districtName = option.text.toLowerCase();
                    if (districtName.includes(searchTerm)) {
                        option.style.display = '';
                    } else {
                        option.style.display = 'none';
                    }
                });
            });
            
            // Reset display when dropdown loses focus
            districtSelect.addEventListener('blur', function() {
                setTimeout(() => {
                    Array.from(this.options).forEach(option => {
                        option.style.display = '';
                    });
                }, 200);
            });
        }
    </script>
</body>
</html>
