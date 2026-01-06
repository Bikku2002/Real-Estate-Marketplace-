<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/user_auth.php';
require_once __DIR__ . '/../config/languages.php';
$pdo = get_pdo();

// Check if user is logged in
$currentUser = get_logged_in_user();

// Fetch latest listings for initial render
$stmt = $pdo->query("SELECT id,title,type,district,municipality,price,cover_image FROM properties ORDER BY created_at DESC LIMIT 8");
$latest = $stmt->fetchAll();

// Fetch some stats for hero section
$totalProperties = $pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('buyer','seller')")->fetchColumn();
$totalOffers = $pdo->query("SELECT COUNT(*) FROM offers")->fetchColumn();
?>
<!doctype html>
<html lang="<?= get_current_language() ?>">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>REAL-ESTATE MARKETPLACE · Nepal's Premier Real Estate Platform</title>
  <meta name="description" content="Buy and sell land and houses across Nepal. Transparent pricing, local expertise, and secure transactions."/>
  <link rel="stylesheet" href="assets/css/styles.css"/>
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><circle cx='50' cy='50' r='40' fill='%23d7263d'/></svg>"/>
</head>
<body>
  <header class="site-header">
    <div class="container nav">
      <a class="brand" href="index.php">
        <img src="assets/Main.png" alt="REAL-ESTATE MARKETPLACE" style="width:28px;height:28px;border-radius:6px;">
        <div class="brand-name">REAL-ESTATE MARKETPLACE</div>
      </a>
      <div class="nav-actions">
        <a class="btn" href="contact.php"><?= __('contact') ?></a>
        <a class="btn" href="valuation.php">🏠 <?= __('estimated_value') ?></a>
        <?php include __DIR__ . '/components/language-switcher.php'; ?>
        <?php if($currentUser): ?>
          <a class="btn" href="profile.php">
            <?php if($currentUser['profile_image']): ?>
              <img src="<?=$currentUser['profile_image']?>" alt="Profile" style="width:20px;height:20px;border-radius:50%;margin-right:6px">
            <?php endif; ?>
            <?=htmlspecialchars($currentUser['name'])?>
          </a>
          <a class="btn" href="favorites.php">❤️ My Favorites</a>
          <?php if($currentUser['role'] === 'seller'): ?>
            <a class="btn" href="add-property.php">➕ Add Property</a>
            <a class="btn" href="my-properties.php">🏠 My Properties</a>
          <?php elseif($currentUser['role'] === 'buyer'): ?>
            <a class="btn" href="buyer-dashboard.php">🔍 Browse Properties</a>
            <a class="btn" href="area-search.php">📏 Area Search</a>
            <a class="btn" href="my-offers.php">💰 My Offers</a>
          <?php endif; ?>
          <a class="btn btn-primary" href="logout.php"><?= __('logout') ?></a>
        <?php else: ?>
          <a class="btn" href="login.php"><?= __('login') ?></a>
          <a class="btn btn-primary" href="register.php"><?= __('register') ?></a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main>
    <!-- Hero Section -->
    <section class="hero">
      <div class="container">
        <div class="hero-content">
          <h1>Nepal's most trusted real estate marketplace</h1>
          <p class="hero-subtitle">Find your perfect land or house across all 77 districts. From the bustling streets of Kathmandu to the serene hills of Pokhara.</p>
          
          <div class="hero-stats">
            <div class="stat-item">
              <div class="stat-number"><?= number_format($totalProperties) ?>+</div>
              <div class="stat-label">Properties</div>
            </div>
            <div class="stat-item">
              <div class="stat-number"><?= number_format($totalUsers) ?>+</div>
              <div class="stat-label">Happy Users</div>
            </div>
            <div class="stat-item">
              <div class="stat-number"><?= number_format($totalOffers) ?>+</div>
              <div class="stat-label">Successful Deals</div>
            </div>
          </div>

          <form id="search" class="search-card">
            <div class="field">
              <label class="label">Property Type</label>
              <select class="select" name="type">
                <option value="">All Types</option>
                <option value="land">Land</option>
                <option value="house">House</option>
              </select>
            </div>
            <div class="field">
              <label class="label">District</label>
              <select class="select" name="district">
                <option value="">All Districts</option>
                <option value="Achham">Achham</option>
                <option value="Arghakhanchi">Arghakhanchi</option>
                <option value="Baglung">Baglung</option>
                <option value="Baitadi">Baitadi</option>
                <option value="Bajhang">Bajhang</option>
                <option value="Bajura">Bajura</option>
                <option value="Banke">Banke</option>
                <option value="Bara">Bara</option>
                <option value="Bardiya">Bardiya</option>
                <option value="Bhaktapur">Bhaktapur</option>
                <option value="Bhojpur">Bhojpur</option>
                <option value="Chitwan">Chitwan</option>
                <option value="Dadeldhura">Dadeldhura</option>
                <option value="Dailekh">Dailekh</option>
                <option value="Dang">Dang</option>
                <option value="Darchula">Darchula</option>
                <option value="Dhading">Dhading</option>
                <option value="Dhankuta">Dhankuta</option>
                <option value="Dhanusa">Dhanusa</option>
                <option value="Dolakha">Dolakha</option>
                <option value="Dolpa">Dolpa</option>
                <option value="Doti">Doti</option>
                <option value="Gorkha">Gorkha</option>
                <option value="Gulmi">Gulmi</option>
                <option value="Humla">Humla</option>
                <option value="Ilam">Ilam</option>
                <option value="Jajarkot">Jajarkot</option>
                <option value="Jhapa">Jhapa</option>
                <option value="Jumla">Jumla</option>
                <option value="Kailali">Kailali</option>
                <option value="Kalikot">Kalikot</option>
                <option value="Kanchanpur">Kanchanpur</option>
                <option value="Kapilvastu">Kapilvastu</option>
                <option value="Kaski">Kaski</option>
                <option value="Kathmandu">Kathmandu</option>
                <option value="Kavrepalanchok">Kavrepalanchok</option>
                <option value="Khotang">Khotang</option>
                <option value="Lalitpur">Lalitpur</option>
                <option value="Lamjung">Lamjung</option>
                <option value="Mahottari">Mahottari</option>
                <option value="Makwanpur">Makwanpur</option>
                <option value="Manang">Manang</option>
                <option value="Morang">Morang</option>
                <option value="Mugu">Mugu</option>
                <option value="Mustang">Mustang</option>
                <option value="Myagdi">Myagdi</option>
                <option value="Nawalparasi">Nawalparasi</option>
                <option value="Nuwakot">Nuwakot</option>
                <option value="Okhaldhunga">Okhaldhunga</option>
                <option value="Palpa">Palpa</option>
                <option value="Panchthar">Panchthar</option>
                <option value="Parbat">Parbat</option>
                <option value="Parsa">Parsa</option>
                <option value="Pyuthan">Pyuthan</option>
                <option value="Ramechhap">Ramechhap</option>
                <option value="Rasuwa">Rasuwa</option>
                <option value="Rautahat">Rautahat</option>
                <option value="Rolpa">Rolpa</option>
                <option value="Rukum">Rukum</option>
                <option value="Rupandehi">Rupandehi</option>
                <option value="Salyan">Salyan</option>
                <option value="Sankhuwasabha">Sankhuwasabha</option>
                <option value="Saptari">Saptari</option>
                <option value="Sarlahi">Sarlahi</option>
                <option value="Sindhuli">Sindhuli</option>
                <option value="Sindhupalchok">Sindhupalchok</option>
                <option value="Siraha">Siraha</option>
                <option value="Solukhumbu">Solukhumbu</option>
                <option value="Sunsari">Sunsari</option>
                <option value="Surkhet">Surkhet</option>
                <option value="Syangja">Syangja</option>
                <option value="Tanahu">Tanahu</option>
                <option value="Taplejung">Taplejung</option>
                <option value="Terhathum">Terhathum</option>
                <option value="Udayapur">Udayapur</option>
              </select>
            </div>
            <div class="field">
              <label class="label">Max Budget (Rs)</label>
              <input class="input" name="max_price" type="number" placeholder="e.g. 15,000,000"/>
            </div>
            <div class="field">
              <label class="label">Features</label>
              <input class="input" name="q" placeholder="garden, parking, ring road..."/>
            </div>
            <div class="field search-btn-field">
              <button class="btn btn-primary search-btn" type="submit">
                <span>🔍</span>
                Search Properties
              </button>
            </div>
          </form>
        </div>
        
        <div class="mountains">
          <div class="layer l1"></div>
          <div class="layer l2"></div>
          <div class="layer l3"></div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
      <div class="container">
        <h2 class="section-title">Why choose RealEstate?</h2>
        <div class="features-grid">
          <div class="feature-card">
            <div class="feature-icon">🏔️</div>
            <h3>Local Expertise</h3>
            <p>Deep knowledge of Nepal's unique geography, from Terai plains to mountain districts.</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">💰</div>
            <h3>Transparent Pricing</h3>
            <p>Real market values with built-in budget calculator and negotiation tools.</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">🤝</div>
            <h3>Secure Transactions</h3>
            <p>Verified listings and secure communication between buyers and sellers.</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">📱</div>
            <h3>Real-time Updates</h3>
            <p>Live notifications for new listings, price changes, and offers in your area.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Latest Properties Section -->
    <section class="listings-section">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">Featured Properties</h2>
          <p class="section-subtitle">Handpicked properties from across Nepal</p>
        </div>
        <div id="listing-grid" class="properties-grid">
          <?php foreach($latest as $row): ?>
            <a class="property-card tilt" href="property.php?id=<?=$row['id']?>">
              <div class="property-image">
                <img src="<?=$row['cover_image']?>" alt="<?=$row['title']?>" onerror="this.onerror=null;this.src='assets/img/placeholder.svg'"/>
                <div class="property-type"><?=$row['type']?></div>
                <div class="property-badge">Featured</div>
              </div>
              <div class="property-content">
                <div class="property-price">Rs <?=number_format((int)$row['price'])?></div>
                <h3 class="property-title"><?=$row['title']?></h3>
                <div class="property-location">📍 <?=$row['district']?><?= $row['municipality']? ', '.$row['municipality'] : '' ?></div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- Budget Estimator Section -->
    <section class="estimator-section">
      <div class="container">
        <div class="grid">
          <div class="col-8">
            <div class="estimator-card" id="estimator">
              <h2 class="section-title">Budget Calculator</h2>
              <p class="section-subtitle">Plan your investment with our smart calculator</p>
              
              <div class="estimator-form">
                <div class="row">
                  <div class="field">
                    <label class="label">Target Property Price (Rs)</label>
                    <input class="input" type="number" name="budget" value="10000000" placeholder="15,000,000"/>
                  </div>
                  <div class="field">
                    <label class="label">Down Payment (Rs)</label>
                    <input class="input" type="number" name="down" value="2000000" placeholder="3,000,000"/>
                  </div>
                </div>
                <div class="row">
                  <div class="field">
                    <label class="label">Interest Rate (%)</label>
                    <input class="input" type="number" step="0.1" name="rate" value="10" placeholder="9.5"/>
                  </div>
                  <div class="field">
                    <label class="label">Loan Term (Years)</label>
                    <input class="input" type="number" name="years" value="20" placeholder="15"/>
                  </div>
                </div>
                
                <div class="estimator-results">
                  <div id="estimator-out" class="result-item"></div>
                  <div id="estimator-rec" class="result-item"></div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-4">
            <div class="info-card">
              <div class="info-icon">💡</div>
              <h3>Smart Investment Tips</h3>
              <ul class="tips-list">
                <li>Consider location growth potential</li>
                <li>Factor in maintenance costs</li>
                <li>Research neighborhood amenities</li>
                <li>Check legal documentation</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section">
      <div class="container">
        <h2 class="section-title">What our users say</h2>
        <div class="testimonials-grid">
          <div class="testimonial-card">
            <div class="testimonial-content">
              <p>"Found my dream house in Lalitpur through RealEstate. The process was smooth and transparent."</p>
            </div>
            <div class="testimonial-author">
              <div class="author-avatar">👨</div>
              <div class="author-info">
                <div class="author-name">Rajesh Shrestha</div>
                <div class="author-location">Lalitpur</div>
              </div>
            </div>
          </div>
          <div class="testimonial-card">
            <div class="testimonial-content">
              <p>"Sold my land in Chitwan quickly. The platform made connecting with serious buyers effortless."</p>
            </div>
            <div class="testimonial-author">
              <div class="author-avatar">👩</div>
              <div class="author-info">
                <div class="author-name">Sunita Gurung</div>
                <div class="author-location">Chitwan</div>
              </div>
            </div>
          </div>
          <div class="testimonial-card">
            <div class="testimonial-content">
              <p>"The budget calculator helped me understand exactly what I could afford. Very helpful!"</p>
            </div>
            <div class="testimonial-author">
              <div class="author-avatar">👨</div>
              <div class="author-info">
                <div class="author-name">Amit Tamang</div>
                <div class="author-location">Pokhara</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- Call to Action Section -->
    <section class="cta-section">
      <div class="container">
        <div class="cta-content">
          <h2>Ready to find your perfect property?</h2>
          <p>Join thousands of Nepalis who trust RealEstate for their real estate needs</p>
          <div class="cta-buttons">
            <a href="#search" class="btn btn-primary cta-btn">Start Your Search</a>
            <a href="contact.php" class="btn cta-btn-secondary">Get Expert Help</a>
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container">
      <div class="footer-content">
        <div class="footer-section">
          <div class="footer-brand">
            <div class="brand">
              <img src="assets/Main.png" alt="REAL-ESTATE MARKETPLACE" style="width:28px;height:28px;border-radius:6px;">
              <div class="brand-name">REAL-ESTATE MARKETPLACE</div>
            </div>
            <p>Nepal's most trusted real estate marketplace connecting buyers and sellers across all 77 districts.</p>
          </div>
        </div>
        <div class="footer-section">
          <h4>For Buyers</h4>
          <ul class="footer-links">
            <li><a href="#search">Search Properties</a></li>
            <li><a href="#estimator">Budget Calculator</a></li>
            <li><a href="buyer-dashboard.php">Browse Properties</a></li>
          </ul>
        </div>
        <div class="footer-section">
          <h4>For Sellers</h4>
          <ul class="footer-links">
            <li><a href="add-property.php">List Property</a></li>
            <li><a href="valuation.php">Pricing Guide</a></li>
            <li><a href="my-properties.php">My Properties</a></li>
          </ul>
        </div>
        <div class="footer-section">
          <h4>Support</h4>
          <ul class="footer-links">
            <li><a href="contact.php">Contact Us</a></li>
            <li><a href="contact.php">Help Center</a></li>
            <li><a href="contact.php">Legal</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <div class="footer-copyright">
          © <?=date('Y')?> REAL-ESTATE MARKETPLACE. Made with ❤️ for Nepal.
        </div>
        <div class="footer-social">
          🏔️ Connecting Nepal, one property at a time
        </div>
      </div>
    </div>
  </footer>

  <script src="assets/js/app.js"></script>
</body>
</html>


