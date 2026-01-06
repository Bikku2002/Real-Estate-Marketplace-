<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/user_auth.php';
require_once __DIR__ . '/../config/languages.php';
require_once __DIR__ . '/../config/pricing_algorithms.php';

$pdo = get_pdo();
$currentUser = get_logged_in_user();

// Helper function to safely format numbers
function safeNumberFormat($value, $decimals = 0) {
    if ($value === null || $value === '') {
        return '0';
    }
    $numericValue = is_numeric($value) ? (float)$value : 0;
    return number_format($numericValue, $decimals);
}

// Initialize valuation system
$valuation = new PropertyValuation();
$marketAnalysis = new MarketAnalysis();

$propertyId = $_GET['id'] ?? null;
$property = null;
$valuationResults = null;
$marketStats = null;
$priceTrends = null;
$comparableSales = null;

// Check if form was submitted
$formSubmitted = isset($_GET['type']) && isset($_GET['size']) && isset($_GET['district']);

if ($propertyId) {
    // Get property data
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch();
    
    if ($property) {
        // Run valuation algorithms
        $valuationResults = $valuation->estimatePropertyValue($property);
        
        // Get market analysis
        $marketStats = $marketAnalysis->getMarketStats($property['district'], $property['type']);
        $priceTrends = $marketAnalysis->getPriceTrends($property['district'], $property['type']);
        $comparableSales = $marketAnalysis->getComparableSales($property);
    }
} elseif ($formSubmitted) {
    // Process form submission for new property valuation
    $formData = [
        'type' => $_GET['type'] ?? 'land',
        'size' => (float)($_GET['size'] ?? 0),
        'district' => $_GET['district'] ?? '',
        'features' => $_GET['features'] ?? '',
        'latitude' => 0, // Default values for new properties
        'longitude' => 0
    ];
    
    // Convert features string to array
    if (!empty($formData['features'])) {
        $formData['features'] = array_map('trim', explode(',', $formData['features']));
    } else {
        $formData['features'] = [];
    }
    
    // Run valuation algorithms on form data
    $valuationResults = $valuation->estimatePropertyValue($formData);
    
    // Get market analysis for the specified district and type
    $marketStats = $marketAnalysis->getMarketStats($formData['district'], $formData['type']);
    $priceTrends = $marketAnalysis->getPriceTrends($formData['district'], $formData['type']);
    
    // Create a mock property object for comparable sales
    $mockProperty = [
        'id' => 0,
        'type' => $formData['type'],
        'district' => $formData['district']
    ];
    $comparableSales = $marketAnalysis->getComparableSales($mockProperty);
}
?>
<!doctype html>
<html lang="<?= get_current_language() ?>">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title><?= __('estimated_value') ?> · REAL-ESTATE MARKETPLACE</title>
  <link rel="stylesheet" href="assets/css/styles.css"/>
  <link rel="stylesheet" href="assets/css/register.css"/>
  
  <style>
    /* CSS Variables for consistent theming */
    :root {
      --ink: #e8f1ff;
      --muted: #9fb3d8;
      --accent: #d7263d;
      --accent-2: #00856f;
      --gold: #d4a20a;
      --bg: #0c0f14;
      --card: #1a2130;
      --elev: #242b3d;
      --line: rgba(255, 255, 255, 0.1);
    }
    
    .valuation-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
      color: var(--ink);
    }
    
    .valuation-header {
      text-align: center;
      margin-bottom: 40px;
    }
    
    .valuation-header h1 {
      color: var(--ink);
      margin-bottom: 16px;
    }
    
    .valuation-header p {
      color: var(--muted);
      font-size: 18px;
    }
    
    .valuation-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 30px;
      margin-bottom: 40px;
    }
    
    .valuation-card {
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }
    
    .valuation-card h3 {
      margin: 0 0 16px 0;
      color: var(--ink);
      font-size: 18px;
      font-weight: 600;
    }
    
    .property-info p {
      color: var(--ink);
      margin-bottom: 8px;
    }
    
    .property-info strong {
      color: var(--accent);
    }
    
    .algorithm-results {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }
    
    .algorithm-item {
      background: var(--elev);
      border: 1px solid var(--line);
      border-radius: 12px;
      padding: 16px;
      text-align: center;
    }
    
    .algorithm-name {
      font-weight: 600;
      color: var(--accent);
      margin-bottom: 8px;
    }
    
    .estimated-price {
      font-size: 24px;
      font-weight: 700;
      color: var(--gold);
      margin-bottom: 4px;
    }
    
    .confidence-score {
      font-size: 14px;
      color: var(--muted);
    }
    
    .confidence-bar {
      width: 100%;
      height: 6px;
      background: var(--elev);
      border-radius: 3px;
      margin-top: 8px;
      overflow: hidden;
    }
    
    .confidence-fill {
      height: 100%;
      background: linear-gradient(90deg, var(--accent-2), var(--accent));
      border-radius: 3px;
      transition: width 0.3s ease;
    }
    
    .ensemble-result {
      background: linear-gradient(135deg, var(--accent), var(--accent-2));
      color: white;
      padding: 24px;
      border-radius: 16px;
      text-align: center;
      margin-bottom: 20px;
    }
    
    .ensemble-price {
      font-size: 36px;
      font-weight: 700;
      margin-bottom: 8px;
    }
    
    .ensemble-confidence {
      font-size: 18px;
      opacity: 0.9;
    }
    
    .market-analysis {
      grid-column: 1 / -1;
    }
    
    .market-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }
    
    .stat-item {
      text-align: center;
      padding: 16px;
      background: var(--elev);
      border: 1px solid var(--line);
      border-radius: 12px;
    }
    
    .stat-value {
      font-size: 20px;
      font-weight: 700;
      color: var(--accent);
      margin-bottom: 4px;
    }
    
    .stat-label {
      font-size: 12px;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .price-trends {
      margin-bottom: 24px;
    }
    
    .trend-chart {
      background: var(--elev);
      border: 1px solid var(--line);
      border-radius: 12px;
      padding: 20px;
      height: 200px;
      display: flex;
      align-items: end;
      gap: 8px;
    }
    
    .trend-bar {
      background: var(--accent);
      border-radius: 4px 4px 0 0;
      min-width: 20px;
      transition: height 0.3s ease;
    }
    
    .comparable-sales {
      max-height: 300px;
      overflow-y: auto;
    }
    
    .sale-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 0;
      border-bottom: 1px solid var(--line);
    }
    
    .sale-item:last-child {
      border-bottom: none;
    }
    
    .sale-info h4 {
      margin: 0 0 4px 0;
      font-size: 14px;
      color: var(--ink);
    }
    
    .sale-details {
      font-size: 12px;
      color: var(--muted);
    }
    
    .sale-price {
      font-weight: 600;
      color: var(--accent);
    }
    
    .property-form {
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }
    
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 24px;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: var(--ink);
    }
    
    .form-input {
      width: 100%;
      padding: 12px 16px;
      border: 2px solid var(--line);
      border-radius: 8px;
      font-size: 16px;
      background: var(--elev);
      color: var(--ink);
      transition: border-color 0.3s ease;
    }
    
    .form-input:focus {
      outline: none;
      border-color: var(--accent);
    }
    
    .form-input::placeholder {
      color: var(--muted);
    }
    
    .btn-valuate {
      background: linear-gradient(135deg, var(--accent), var(--accent-2));
      color: white;
      border: none;
      padding: 16px 32px;
      border-radius: 12px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      width: 100%;
    }
    
    .btn-valuate:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(215, 38, 61, 0.3);
    }
    
    @media (max-width: 768px) {
      .valuation-grid {
        grid-template-columns: 1fr;
      }
      
      .algorithm-results {
        grid-template-columns: 1fr;
      }
      
      .form-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body class="register-body">
  <div class="register-container">
    <!-- Background Animation -->
    <div class="bg-animation">
      <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
      </div>
    </div>
    
    <!-- Main Content -->
    <main class="register-main">
      <div class="valuation-container">
        <!-- Header -->
        <div class="valuation-header">
          <h1>🏠 <?= __('estimated_value') ?></h1>
          <p>Advanced AI-powered property valuation using multiple algorithms</p>
        </div>
        
        <?php if ($property || $formSubmitted): ?>
          <!-- Calculate New Valuation Button -->
          <div style="text-align: center; margin-bottom: 20px;">
            <a href="valuation.php" class="btn-valuate" style="display: inline-block; width: auto; text-decoration: none;">
              🔄 Calculate New Valuation
            </a>
          </div>
          
          <?php if ($property): ?>
            <!-- Property Information -->
            <div class="valuation-card">
              <h3>📋 <?= __('property') ?> <?= __('description') ?></h3>
              <div class="property-info">
                <p><strong><?= __('property') ?>:</strong> <?= htmlspecialchars($property['title']) ?></p>
                <p><strong><?= __('location') ?>:</strong> <?= htmlspecialchars($property['district']) ?>, <?= htmlspecialchars($property['municipality']) ?></p>
                <p><strong><?= __('size') ?>:</strong> <?= number_format($property['size']) ?> sq ft</p>
                <p><strong><?= __('type') ?>:</strong> <?= ucfirst($property['type']) ?></p>
              </div>
            </div>
          <?php else: ?>
            <!-- Form Data Summary -->
            <div class="valuation-card">
              <h3>📋 <?= __('property') ?> <?= __('description') ?></h3>
              <div class="property-info">
                <p><strong><?= __('property') ?> <?= __('type') ?>:</strong> <?= ucfirst($_GET['type']) ?></p>
                <p><strong><?= __('location') ?>:</strong> <?= htmlspecialchars($_GET['district']) ?></p>
                <p><strong><?= __('size') ?>:</strong> <?= safeNumberFormat($_GET['size']) ?> sq ft</p>
                <?php if (!empty($_GET['features'])): ?>
                  <p><strong><?= __('features') ?>:</strong> <?= htmlspecialchars($_GET['features']) ?></p>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
          
          <!-- Valuation Results -->
          <div class="valuation-grid">
            <!-- Algorithm Results -->
            <div class="valuation-card">
              <h3>🧠 <?= __('price_confidence') ?> - Algorithm Analysis</h3>
              <div class="algorithm-results">
                <?php foreach (['gwr', 'knn', 'cosine'] as $method): ?>
                  <?php if (isset($valuationResults[$method])): ?>
                    <div class="algorithm-item">
                      <div class="algorithm-name"><?= strtoupper($method) ?></div>
                      <div class="estimated-price">₨<?= safeNumberFormat($valuationResults[$method]['estimated_price']) ?></div>
                      <div class="confidence-score"><?= ($valuationResults[$method]['confidence'] * 100) ?>% <?= __('price_confidence') ?></div>
                      <div class="confidence-bar">
                        <div class="confidence-fill" style="width: <?= ($valuationResults[$method]['confidence'] * 100) ?>%"></div>
                      </div>
                    </div>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            </div>
            
            <!-- Ensemble Result -->
            <div class="valuation-card">
              <div class="ensemble-result">
                <div class="ensemble-price">₨<?= safeNumberFormat($valuationResults['ensemble']['estimated_price']) ?></div>
                <div class="ensemble-confidence"><?= ($valuationResults['ensemble']['confidence'] * 100) ?>% <?= __('price_confidence') ?></div>
              </div>
              
              <h3>📊 <?= __('market_analysis') ?></h3>
              <?php if ($marketStats): ?>
                <div class="market-stats">
                  <div class="stat-item">
                    <div class="stat-value"><?= safeNumberFormat($marketStats['total_properties']) ?></div>
                    <div class="stat-label"><?= __('property') ?>s</div>
                  </div>
                  <div class="stat-item">
                    <div class="stat-value">₨<?= safeNumberFormat($marketStats['avg_price']) ?></div>
                    <div class="stat-label">Avg <?= __('price') ?></div>
                  </div>
                  <div class="stat-item">
                    <div class="stat-value">₨<?= safeNumberFormat($marketStats['min_price']) ?></div>
                    <div class="stat-label">Min <?= __('price') ?></div>
                  </div>
                  <div class="stat-item">
                    <div class="stat-value">₨<?= safeNumberFormat($marketStats['max_price']) ?></div>
                    <div class="stat-label">Max <?= __('price') ?></div>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>
          
          <!-- Market Analysis -->
          <div class="valuation-card market-analysis">
            <h3>📈 <?= __('price_trends') ?></h3>
            <?php if ($priceTrends): ?>
              <div class="price-trends">
                <div class="trend-chart">
                  <?php 
                  $maxPrice = max(array_column($priceTrends, 'avg_price'));
                  foreach ($priceTrends as $trend): 
                    $height = ($trend['avg_price'] / $maxPrice) * 100;
                  ?>
                    <div class="trend-bar" style="height: <?= $height ?>%" title="Month: <?= $trend['month'] ?>, Avg: ₨<?= safeNumberFormat($trend['avg_price']) ?>"></div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
            
            <h3>🏘️ <?= __('comparable_sales') ?></h3>
            <?php if ($comparableSales): ?>
              <div class="comparable-sales">
                <?php foreach ($comparableSales as $sale): ?>
                  <div class="sale-item">
                    <div class="sale-info">
                      <h4><?= htmlspecialchars($sale['title']) ?></h4>
                      <div class="sale-details">
                        <?= htmlspecialchars($sale['district']) ?> • <?= safeNumberFormat($sale['size']) ?> sq ft • <?= date('M Y', strtotime($sale['created_at'])) ?>
                      </div>
                    </div>
                    <div class="sale-price">₨<?= safeNumberFormat($sale['price']) ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p>No comparable sales found in this area.</p>
            <?php endif; ?>
          </div>
          
        <?php else: ?>
          <!-- Property Input Form -->
          <div class="property-form">
            <h3>🔍 <?= __('estimated_value') ?> Calculator</h3>
            <form method="get" action="">
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label"><?= __('property') ?> <?= __('type') ?></label>
                  <select class="form-input" name="type" required>
                    <option value="">Select Property Type</option>
                    <option value="land"><?= __('land') ?></option>
                    <option value="house"><?= __('house') ?></option>
                  </select>
                </div>
                
                <div class="form-group">
                  <label class="form-label"><?= __('size') ?> (sq ft)</label>
                  <input class="form-input" type="number" name="size" placeholder="1000" required>
                </div>
                
                <div class="form-group">
                  <label class="form-label"><?= __('location') ?> (District)</label>
                  <input class="form-input" type="text" name="district" placeholder="Kathmandu" required>
                </div>
                
                <div class="form-group">
                  <label class="form-label"><?= __('features') ?></label>
                  <input class="form-input" type="text" name="features" placeholder="road access, electricity, water">
                </div>
              </div>
              
              <button type="submit" class="btn-valuate">🔍 <?= __('estimated_value') ?> Calculate</button>
            </form>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
  
  <script>
    // Animate confidence bars
    document.addEventListener('DOMContentLoaded', function() {
      const confidenceBars = document.querySelectorAll('.confidence-fill');
      
      confidenceBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        
        setTimeout(() => {
          bar.style.width = width;
        }, 500);
      });
    });
    
    // Animate trend bars
    const trendBars = document.querySelectorAll('.trend-bar');
    trendBars.forEach((bar, index) => {
      bar.style.opacity = '0';
      bar.style.transform = 'scaleY(0)';
      
      setTimeout(() => {
        bar.style.transition = 'all 0.5s ease';
        bar.style.opacity = '1';
        bar.style.transform = 'scaleY(1)';
      }, index * 100);
    });
  </script>
</body>
</html>
