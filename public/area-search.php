<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/user_auth.php';
require_once __DIR__ . '/../config/nepal_area_units.php';

// Check if user is logged in
$currentUser = get_logged_in_user();
if (!$currentUser) {
    header('Location: login.php');
    exit;
}

$pdo = get_pdo();

// Get search parameters
$area_min = !empty($_GET['area_min']) ? (float)$_GET['area_min'] : null;
$area_max = !empty($_GET['area_max']) ? (float)$_GET['area_max'] : null;
$area_unit = $_GET['area_unit'] ?? 'sqft';
$target_unit = $_GET['target_unit'] ?? 'sqft';
$district = $_GET['district'] ?? '';
$type = $_GET['type'] ?? '';
$min_price = !empty($_GET['min_price']) ? (int)$_GET['min_price'] : null;
$max_price = !empty($_GET['max_price']) ? (int)$_GET['max_price'] : null;

// Build the SQL query with area filters
$where_conditions = ['p.status = "active"'];
$params = [];

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

$where_clause = implode(' AND ', $where_conditions);

// Get properties with area filters
$sql = "SELECT p.*, u.name as seller_name, u.phone as seller_phone
        FROM properties p 
        LEFT JOIN users u ON p.seller_id = u.id 
        WHERE $where_clause 
        ORDER BY p.area_sqft ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$properties = $stmt->fetchAll();

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

// Calculate area statistics
$totalArea = 0;
$avgArea = 0;
$minArea = PHP_FLOAT_MAX;
$maxArea = 0;

foreach ($properties as $property) {
    $area = $property['area_sqft'] ?? 0;
    $totalArea += $area;
    $minArea = min($minArea, $area);
    $maxArea = max($maxArea, $area);
}

if (count($properties) > 0) {
    $avgArea = $totalArea / count($properties);
}

// Helper function to safely format numbers
function safeNumberFormat($value, $decimals = 0) {
    if ($value === null || $value === '' || !is_numeric($value)) {
        return '0';
    }
    return number_format((float)$value, $decimals);
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Area-Based Property Search · NepaEstate</title>
    <meta name="description" content="Search properties by area size using Nepal-specific measurement units."/>
    <link rel="stylesheet" href="assets/css/styles.css"/>
    <style>
        .search-header {
            background: linear-gradient(135deg, var(--accent), #059669);
            color: white;
            padding: 32px;
            border-radius: 16px;
            margin-bottom: 32px;
            text-align: center;
        }
        .area-filters {
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
        .area-converter {
            background: var(--elev);
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }
        .converter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            align-items: end;
        }
        .converter-result {
            background: var(--accent);
            color: white;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
        }
        .area-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
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
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 24px;
        }
        .property-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
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
        }
        .property-content {
            padding: 20px;
        }
        .property-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 8px;
        }
        .property-location {
            color: var(--muted);
            margin-bottom: 16px;
        }
        .area-display {
            background: var(--elev);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
            text-align: center;
        }
        .primary-area {
            font-size: 20px;
            font-weight: bold;
            color: var(--accent);
            margin-bottom: 8px;
        }
        .converted-areas {
            font-size: 12px;
            color: var(--muted);
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
                <?php if ($currentUser['role'] === 'buyer'): ?>
                    <a class="btn" href="buyer-dashboard.php">Dashboard</a>
                    <a class="btn" href="favorites.php">My Favorites</a>
                <?php else: ?>
                    <a class="btn" href="my-properties.php">My Properties</a>
                <?php endif; ?>
                <a class="btn" href="profile.php">Profile</a>
                <a class="btn btn-primary" href="logout.php">Logout</a>
            </div>
        </div>
    </header>

    <main class="container" style="margin-top: 20px;">
        <div class="search-header">
            <h1>🔍 Area-Based Property Search</h1>
            <p>Find properties by size using traditional Nepal area units or international measurements</p>
        </div>

        <div class="area-filters">
            <h3 style="margin-top: 0; margin-bottom: 20px; color: var(--accent);">Area Search Filters</h3>
            
            <!-- Area Converter Tool -->
            <div class="area-converter">
                <h4 style="margin-top: 0; margin-bottom: 16px;">📏 Area Unit Converter</h4>
                <div class="converter-grid">
                    <div class="filter-group">
                        <label class="filter-label">Convert From</label>
                        <input class="filter-input" type="number" id="convertValue" placeholder="Enter value" step="0.01" min="0.01"/>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">From Unit</label>
                        <select class="filter-select" id="convertFromUnit">
                            <?php foreach ($areaUnits as $unitKey => $unitInfo): ?>
                                <option value="<?= $unitKey ?>"><?= htmlspecialchars($unitInfo['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">To Unit</label>
                        <select class="filter-select" id="convertToUnit">
                            <?php foreach ($areaUnits as $unitKey => $unitInfo): ?>
                                <option value="<?= $unitKey ?>"><?= htmlspecialchars($unitInfo['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="converter-result" id="converterResult">
                        Enter values to convert
                    </div>
                </div>
            </div>

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
                    <select class="filter-select" name="district">
                        <option value="">All Districts</option>
                        <?php foreach ($districts as $dist): ?>
                            <option value="<?= $dist ?>" <?= $district === $dist ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dist) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
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
                    <label class="filter-label">Min Price (Rs)</label>
                    <input class="filter-input" type="number" name="min_price" 
                           placeholder="e.g., 5000000" value="<?= htmlspecialchars($min_price ?: '') ?>"/>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Max Price (Rs)</label>
                    <input class="filter-input" type="number" name="max_price" 
                           placeholder="e.g., 15000000" value="<?= htmlspecialchars($max_price ?: '') ?>"/>
                </div>
                
                <div class="filter-group" style="display: flex; align-items: end;">
                    <button type="submit" class="search-btn">Search Properties</button>
                </div>
            </form>
        </div>

        <?php if (!empty($properties)): ?>
            <!-- Area Statistics -->
            <div class="area-stats">
                <div class="stat-card">
                    <div class="stat-value"><?= count($properties) ?></div>
                    <div class="stat-label">Properties Found</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= safeNumberFormat($minArea, 0) ?></div>
                    <div class="stat-label">Smallest Area (sq ft)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= safeNumberFormat($avgArea, 0) ?></div>
                    <div class="stat-label">Average Area (sq ft)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= safeNumberFormat($maxArea, 0) ?></div>
                    <div class="stat-label">Largest Area (sq ft)</div>
                </div>
            </div>

            <h2 style="margin-bottom: 20px; color: var(--ink);">
                📋 Properties by Area (<?= count($properties) ?> found)
            </h2>
            
            <div class="properties-grid">
                <?php foreach ($properties as $property): ?>
                    <div class="property-card" onclick="window.location.href='property.php?id=<?= $property['id'] ?>'">
                        <div class="property-image">
                            <img src="<?= $property['cover_image'] ?: 'assets/img/placeholder.svg' ?>" 
                                 alt="<?= htmlspecialchars($property['title']) ?>"
                                 onerror="this.src='assets/img/placeholder.svg'"/>
                        </div>
                        
                        <div class="property-content">
                            <h3 class="property-title"><?= htmlspecialchars($property['title']) ?></h3>
                            <p class="property-location">📍 <?= htmlspecialchars($property['district']) ?><?= !empty($property['municipality']) ? ', ' . htmlspecialchars($property['municipality']) : '' ?></p>
                            
                            <div class="area-display">
                                <?php if ($property['area_unit'] && $property['area_unit'] !== 'sqft'): ?>
                                    <?php 
                                    $primaryArea = $property['area_unit'] === 'ana' ? ($property['area_ana'] ?? 0) : ($property['area_sqft'] ?? 0);
                                    ?>
                                    <div class="primary-area">
                                        <?= NepalAreaUnits::formatArea($primaryArea, $property['area_unit']) ?>
                                    </div>
                                    <div class="converted-areas">
                                        <?= safeNumberFormat($property['area_sqft']) ?> sq ft | 
                                        <?= safeNumberFormat($property['area_ana'], 2) ?> Ana
                                    </div>
                                <?php else: ?>
                                    <div class="primary-area">
                                        <?= safeNumberFormat($property['area_sqft']) ?> sq ft
                                    </div>
                                    <div class="converted-areas">
                                        <?= safeNumberFormat($property['area_ana'], 2) ?> Ana | 
                                        <?= safeNumberFormat(($property['area_sqft'] ?? 0) * 0.092903, 2) ?> sq m
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: var(--accent); font-weight: 600; font-size: 18px;">
                                    Rs <?= number_format($property['price']) ?>
                                </span>
                                <span style="background: var(--elev); padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                    <?= ucfirst($property['type']) ?>
                                </span>
                            </div>
                            
                            <div style="margin-top: 12px; font-size: 12px; color: var(--muted);">
                                👤 <?= htmlspecialchars($property['seller_name']) ?>
                                <?php if ($property['seller_phone']): ?>
                                    · 📞 <?= htmlspecialchars($property['seller_phone']) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 60px 20px; color: var(--muted);">
                <div style="font-size: 48px; margin-bottom: 16px;">🔍</div>
                <h3>No Properties Found</h3>
                <p>Try adjusting your area search criteria or browse all properties.</p>
                <a href="buyer-dashboard.php" class="btn btn-primary">View All Properties</a>
            </div>
        <?php endif; ?>
    </main>

    <script>
        // Area converter functionality with built-in conversion logic
        document.addEventListener('DOMContentLoaded', function() {
            const convertValue = document.getElementById('convertValue');
            const convertFromUnit = document.getElementById('convertFromUnit');
            const convertToUnit = document.getElementById('convertToUnit');
            const converterResult = document.getElementById('converterResult');
            
            // Area conversion constants (same as PHP class)
            const AREA_CONVERSIONS = {
                'sqft': 1,
                'sqm': 10.764,
                'ana': 0.002921,
                'paisa': 0.000731,
                'dhur': 0.000183,
                'ropani': 0.000183,
                'kattha': 0.000584,
                'bigha': 0.000037
            };
            
            // Conversion function
            function convertArea(value, fromUnit, toUnit) {
                if (!value || !fromUnit || !toUnit) return null;
                
                // Convert to square feet first
                let sqftValue;
                if (fromUnit === 'sqft') {
                    sqftValue = value;
                } else if (AREA_CONVERSIONS[fromUnit]) {
                    sqftValue = value / AREA_CONVERSIONS[fromUnit];
                } else {
                    return null;
                }
                
                // Convert from square feet to target unit
                if (toUnit === 'sqft') {
                    return sqftValue;
                } else if (AREA_CONVERSIONS[toUnit]) {
                    return sqftValue * AREA_CONVERSIONS[toUnit];
                } else {
                    return null;
                }
            }
            
            function updateConversion() {
                const value = parseFloat(convertValue.value);
                const fromUnit = convertFromUnit.value;
                const toUnit = convertToUnit.value;
                
                if (value && fromUnit && toUnit && value > 0) {
                    try {
                        const convertedValue = convertArea(value, fromUnit, toUnit);
                        if (convertedValue !== null) {
                            converterResult.innerHTML = `${value} ${fromUnit} = ${convertedValue.toFixed(4)} ${toUnit}`;
                        } else {
                            converterResult.innerHTML = 'Invalid conversion';
                        }
                    } catch (error) {
                        converterResult.innerHTML = 'Error in conversion';
                        console.error('Conversion error:', error);
                    }
                } else {
                    converterResult.innerHTML = 'Enter values to convert';
                }
            }
            
            convertValue.addEventListener('input', updateConversion);
            convertFromUnit.addEventListener('change', updateConversion);
            convertToUnit.addEventListener('change', updateConversion);
        });
    </script>
</body>
</html>
