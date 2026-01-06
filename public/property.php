<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/user_auth.php';

$pdo = get_pdo();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($id<=0){ header('Location: /Final6/public/index.php'); exit; }

// Get current user if logged in
$currentUser = get_logged_in_user();

$stmt = $pdo->prepare("SELECT p.*, u.name AS seller_name, u.phone AS seller_phone, u.kyc_status FROM properties p JOIN users u ON u.id=p.seller_id WHERE p.id=:id");
$stmt->execute([':id'=>$id]);
$prop = $stmt->fetch();
if(!$prop){ header('Location: /Final6/public/index.php'); exit; }

// Get additional images
$imgs = $pdo->prepare("SELECT image_url FROM property_images WHERE property_id=:id ORDER BY sort_order ASC, id ASC");
$imgs->execute([':id'=>$id]);
$images = $imgs->fetchAll();

// Check if current user is the property owner
$isOwner = $currentUser && $currentUser['id'] == $prop['seller_id'];

// Handle favorites
if ($currentUser && !$isOwner) {
    if (isset($_POST['add_favorite'])) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO favorites (user_id, property_id) VALUES (:user_id, :property_id)");
        $stmt->execute([':user_id' => $currentUser['id'], ':property_id' => $prop['id']]);
    } elseif (isset($_POST['remove_favorite'])) {
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = :user_id AND property_id = :property_id");
        $stmt->execute([':user_id' => $currentUser['id'], ':property_id' => $prop['id']]);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title><?=$prop['title']?> · NepaEstate</title>
  <link rel="stylesheet" href="assets/css/styles.css"/>
  <style>
    .property-gallery {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
      margin-top: 16px;
    }
    .gallery-item {
      border-radius: 8px;
      overflow: hidden;
      border: 2px solid var(--line);
      transition: transform 0.3s ease;
    }
    .gallery-item:hover {
      transform: scale(1.05);
    }
    .gallery-item img {
      width: 100%;
      height: 200px;
      object-fit: cover;
    }
    .media-section {
      margin-top: 24px;
    }
    .media-section h3 {
      color: var(--accent);
      border-bottom: 2px solid var(--accent);
      padding-bottom: 8px;
      margin-bottom: 16px;
    }
    .video-container {
      position: relative;
      width: 100%;
      max-width: 600px;
      margin: 0 auto;
    }
    .video-container video {
      width: 100%;
      border-radius: 8px;
      border: 2px solid var(--line);
    }
    .three-d-container {
      background: var(--elev);
      border: 2px solid var(--accent);
      border-radius: 12px;
      padding: 24px;
      text-align: center;
      margin: 16px 0;
    }
    .three-d-icon {
      font-size: 48px;
      margin-bottom: 16px;
    }
    .three-d-title {
      font-size: 18px;
      font-weight: 600;
      color: var(--accent);
      margin-bottom: 8px;
    }
    .three-d-desc {
      color: var(--muted);
      margin-bottom: 16px;
    }
    .download-btn {
      background: var(--accent);
      color: white;
      padding: 12px 24px;
      border-radius: 8px;
      text-decoration: none;
      display: inline-block;
      transition: all 0.3s ease;
    }
    .download-btn:hover {
      background: var(--brass);
      transform: translateY(-2px);
    }
    .owner-notice {
      background: rgba(215, 38, 61, 0.1);
      border: 1px solid rgba(215, 38, 61, 0.3);
      border-radius: 8px;
      padding: 16px;
      margin: 16px 0;
      text-align: center;
      color: var(--accent);
    }
    .property-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
      gap: 16px;
      margin: 16px 0;
    }
    .stat-card {
      background: var(--elev);
      border: 1px solid var(--line);
      border-radius: 8px;
      padding: 16px;
      text-align: center;
    }
    .stat-label {
      font-size: 12px;
      color: var(--muted);
      text-transform: uppercase;
      margin-bottom: 4px;
    }
    .stat-value {
      font-size: 18px;
      font-weight: 600;
      color: var(--ink);
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
          <a class="btn" href="index.php#search">Back to search</a>
          <?php if($currentUser): ?>
            <a class="btn" href="favorites.php">❤️ My Favorites</a>
            <a class="btn" href="profile.php">My Profile</a>
            <a class="btn btn-primary" href="logout.php">Logout</a>
          <?php else: ?>
            <a class="btn" href="login.php">Login</a>
            <a class="btn btn-primary" href="register.php">Register</a>
          <?php endif; ?>
        </div>
    </div>
  </header>

  <main class="container" style="margin-top:16px">


    <div class="grid">
      <div class="col-8">
        <!-- Main Property Card -->
        <div class="card">
          <!-- Cover Image -->
          <div class="media" style="height:320px">
            <?php if($prop['cover_image']): ?>
              <img src="<?=htmlspecialchars($prop['cover_image'])?>" alt="<?=htmlspecialchars($prop['title'])?>" style="width:100%;height:100%;object-fit:cover" onerror="this.onerror=null;this.src='assets/img/placeholder.svg'"/>
            <?php else: ?>
              <img src="assets/img/placeholder.svg" alt="No image" style="width:100%;height:100%;object-fit:cover"/>
            <?php endif; ?>
          </div>
          
          <div class="body">
            <div class="price" style="font-size:20px">
              Rs <?=number_format((int)$prop['price'])?>
              <?php if($prop['is_negotiable']): ?>
                <span style="background: #f59e0b; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px; margin-left: 8px;">💬 Negotiable</span>
              <?php endif; ?>
            </div>
            <h2 style="margin:6px 0 8px"><?=htmlspecialchars($prop['title'])?></h2>
            <div class="meta">Type: <?=$prop['type']?> · <?=$prop['district']?><?=$prop['municipality']?', '.$prop['municipality']:''?></div>
            <p style="margin-top:12px;white-space:pre-wrap"><?=nl2br(htmlspecialchars((string)$prop['description']))?></p>
            
            <!-- Property Statistics -->
            <div class="property-stats">
              <?php if($prop['type'] === 'house'): ?>
                <div class="stat-card">
                  <div class="stat-label">Bedrooms</div>
                  <div class="stat-value"><?= $prop['bedrooms'] ?? 'N/A' ?></div>
                </div>
                <div class="stat-card">
                  <div class="stat-label">Bathrooms</div>
                  <div class="stat-value"><?= $prop['bathrooms'] ?? 'N/A' ?></div>
                </div>
              <?php endif; ?>
              <?php if(!empty($prop['area_sqft'])): ?>
                <div class="stat-card">
                  <div class="stat-label">Area (Sq Ft)</div>
                  <div class="stat-value"><?= number_format((float)$prop['area_sqft']) ?></div>
                </div>
              <?php endif; ?>
              <?php if(!empty($prop['area_ana'])): ?>
                <div class="stat-card">
                  <div class="stat-label">Area (Ana)</div>
                  <div class="stat-value"><?= number_format((float)$prop['area_ana'], 2) ?></div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Additional Images Gallery -->
        <?php if(!empty($images)): ?>
        <div class="card" style="margin-top:16px">
          <div class="body">
            <h3>📸 Property Gallery</h3>
            <div class="property-gallery">
              <?php foreach($images as $img): ?>
                <div class="gallery-item">
                  <img src="<?=htmlspecialchars($img['image_url'])?>" alt="Property image" onerror="this.onerror=null;this.src='assets/img/placeholder.svg'"/>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?php else: ?>
        <div class="card" style="margin-top:16px">
          <div class="body">
            <h3>📸 Property Gallery</h3>
            <p style="color: var(--muted); text-align: center; padding: 20px;">No additional images available for this property.</p>
          </div>
        </div>
        <?php endif; ?>

        <!-- Property Video -->
        <?php if(!empty($prop['property_video'])): ?>
        <div class="card" style="margin-top:16px">
          <div class="body">
            <h3>🎥 Property Video</h3>
            <div class="video-container">
              <video controls>
                <source src="<?=htmlspecialchars($prop['property_video'])?>" type="video/mp4">
                Your browser does not support the video tag.
              </video>
            </div>
          </div>
        </div>
        <?php else: ?>
        <div class="card" style="margin-top:16px">
          <div class="body">
            <h3>🎥 Property Video</h3>
            <p style="color: var(--muted); text-align: center; padding: 20px;">No video available for this property.</p>
          </div>
        </div>
        <?php endif; ?>

        <!-- 3D Preview -->
        <?php if(!empty($prop['three_d_preview'])): ?>
        <div class="card" style="margin-top:16px">
          <div class="body">
            <h3>🔮 3D Preview</h3>
            <div class="three-d-container">
              <div class="three-d-icon">🔮</div>
              <div class="three-d-title">3D Model Available</div>
              <div class="three-d-desc">Download the 3D model file to view in your preferred 3D viewer</div>
              <a href="<?=htmlspecialchars($prop['three_d_preview'])?>" class="download-btn" download>
                📥 Download 3D Model
              </a>
            </div>
          </div>
        </div>
        <?php else: ?>
        <div class="card" style="margin-top:16px">
          <div class="body">
            <h3>🔮 3D Preview</h3>
            <p style="color: var(--muted); text-align: center; padding: 20px;">No 3D model available for this property.</p>
          </div>
        </div>
        <?php endif; ?>

        <!-- Recent Offers -->
        <div class="card" style="margin-top:16px;padding:16px">
          <h3>💰 Recent Offers</h3>
          <div id="offers"></div>
        </div>
      </div>

      <div class="col-4">
        <!-- Contact Seller -->
        <div class="card" style="padding:16px">
          <h3>👤 Contact Seller</h3>
          <div class="meta">
            <strong><?=$prop['seller_name']?></strong>
            <?php if($prop['seller_phone']): ?>
              <br>📞 <?=$prop['seller_phone']?>
            <?php endif; ?>
            <br>🛡️ KYC: <span style="color: <?=$prop['kyc_status'] === 'verified' ? '#10b981' : '#f59e0b'?>"><?=ucfirst($prop['kyc_status'])?></span>
          </div>
        </div>

        <!-- Add to Favorites -->
        <?php if($currentUser && !$isOwner): ?>
        <div class="card" style="padding:16px;margin-top:16px">
          <h3>❤️ Add to Favorites</h3>
          <?php
          // Check if property is already in favorites
          $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = :user_id AND property_id = :property_id");
          $stmt->execute([':user_id' => $currentUser['id'], ':property_id' => $prop['id']]);
          $isFavorite = $stmt->fetch();
          ?>
          
          <?php if($isFavorite): ?>
            <div style="text-align: center; padding: 16px;">
              <div style="font-size: 24px; margin-bottom: 8px;">❤️</div>
              <div style="color: var(--accent); font-weight: 600; margin-bottom: 8px;">Already in Favorites</div>
              <form method="post" style="display: inline;">
                <input type="hidden" name="remove_favorite" value="1">
                <input type="hidden" name="property_id" value="<?=$prop['id']?>">
                <button type="submit" class="btn" style="background: #ef4444; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer;">
                  Remove from Favorites
                </button>
              </form>
            </div>
          <?php else: ?>
            <div style="text-align: center; padding: 16px;">
              <div style="font-size: 24px; margin-bottom: 8px;">🤍</div>
              <div style="color: var(--muted); margin-bottom: 16px;">Save this property to your favorites for easy access later.</div>
              <form method="post" style="display: inline;">
                <input type="hidden" name="add_favorite" value="1">
                <input type="hidden" name="property_id" value="<?=$prop['id']?>">
                <button type="submit" class="btn" style="background: var(--accent); color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer;">
                  ❤️ Add to Favorites
                </button>
              </form>
            </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Make an Offer Section -->
        <?php if($currentUser && !$isOwner): ?>
          <div class="card" style="padding:16px;margin-top:16px">
            <h3>💼 Make an Offer</h3>
            <form id="offer-form">
              <input type="hidden" name="property_id" value="<?=$prop['id']?>"/>
              <div class="field">
                <label class="label">Offer amount (Rs)</label>
                <input class="input" type="number" name="offer_amount" required placeholder="e.g. 12000000" min="1"/>
              </div>
              <div class="field">
                <label class="label">Message (optional)</label>
                <textarea class="input" name="message" placeholder="Any notes for seller" rows="3"></textarea>
              </div>
              <button class="btn btn-primary" type="submit" style="width:100%">Submit Offer</button>
              <div id="offer-msg" class="meta" style="margin-top:8px"></div>
            </form>
          </div>
        <?php elseif($isOwner): ?>
          <div class="owner-notice">
            <div style="font-size: 24px; margin-bottom: 8px;">🏠</div>
            <strong>This is your property!</strong><br>
            You cannot make offers on your own listing.
          </div>
          <div class="card" style="padding:16px;margin-top:16px">
            <h3>📊 Property Management</h3>
            <a href="my-properties.php" class="btn" style="width:100%;margin-bottom:8px">View My Properties</a>
            <a href="edit-property.php?id=<?=$prop['id']?>" class="btn btn-primary" style="width:100%">Edit This Property</a>
          </div>
        <?php else: ?>
          <div class="card" style="padding:16px;margin-top:16px">
            <h3>💼 Make an Offer</h3>
            <p style="color: var(--muted); margin-bottom: 16px;">Please login to make an offer on this property.</p>
            <a href="login.php" class="btn btn-primary" style="width:100%">Login to Offer</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container">© <?=date('Y')?> NepaEstate</div>
  </footer>

  <script src="assets/js/app.js"></script>
  <script>
  (function(){
    const offersEl = document.getElementById('offers');
    
    async function loadOffers(){
      try{
        const data = await httpGet('api/offers.php?property_id=<?=$prop['id']?>');
        if (data.offers && data.offers.length > 0) {
          offersEl.innerHTML = data.offers.map(o => `
            <div class="row" style="justify-content:space-between;border-bottom:1px solid var(--line);padding:12px 0">
              <div>
                <strong>Rs ${Number(o.offer_amount).toLocaleString('ne-NP')}</strong>
                <div class="meta">${o.buyer_name || 'Buyer'}</div>
              </div>
              <div class="meta">
                <span class="chip" style="background: ${o.status === 'accepted' ? '#10b981' : o.status === 'rejected' ? '#ef4444' : '#f59e0b'}">${o.status}</span>
                <div>${new Date(o.created_at).toLocaleDateString()}</div>
              </div>
            </div>
          `).join('');
        } else {
          offersEl.innerHTML = '<div class="meta">No offers yet</div>';
        }
      } catch(e) { 
        offersEl.innerHTML = '<div class="meta">Failed to load offers</div>'; 
      }
    }
    
    loadOffers();

    // Handle offer form submission
    const form = document.getElementById('offer-form');
    if (form) {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(form);
        const payload = Object.fromEntries(fd.entries());
        payload.offer_amount = Number(payload.offer_amount || 0);
        
        const msg = document.getElementById('offer-msg');
        const submitBtn = form.querySelector('button[type="submit"]');
        
        try {
          submitBtn.disabled = true;
          submitBtn.textContent = 'Submitting...';
          
          const res = await httpPost('api/offers.php', payload);
          if (res.ok) {
            msg.textContent = '✅ Offer submitted successfully!';
            msg.style.color = '#10b981';
            form.reset();
            loadOffers();
          } else {
            msg.textContent = '❌ ' + (res.error || 'Error submitting offer');
            msg.style.color = '#ef4444';
          }
        } catch (err) {
          msg.textContent = '❌ Network error. Please try again.';
          msg.style.color = '#ef4444';
        } finally {
          submitBtn.disabled = false;
          submitBtn.textContent = 'Submit Offer';
        }
      });
    }
  })();
  </script>
</body>
</html>


