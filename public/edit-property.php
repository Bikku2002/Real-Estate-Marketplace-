<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/user_auth.php';
require_once __DIR__ . '/../config/nepal_area_units.php';

// Check if user is logged in and is a seller
$currentUser = get_logged_in_user();
if (!$currentUser || $currentUser['role'] !== 'seller') {
    header('Location: login.php');
    exit;
}

$pdo = get_pdo();

// Get property ID from URL
$property_id = (int)($_GET['id'] ?? 0);
if (!$property_id) {
    header('Location: my-properties.php');
    exit;
}

// Get property details
$stmt = $pdo->prepare("SELECT * FROM properties WHERE id = :id AND seller_id = :seller_id");
$stmt->execute([':id' => $property_id, ':seller_id' => $currentUser['id']]);
$property = $stmt->fetch();

if (!$property) {
    header('Location: my-properties.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $type = $_POST['type'] ?? '';
    $district = $_POST['district'] ?? '';
    $municipality = trim($_POST['municipality'] ?? '');
    $ward = (int)($_POST['ward'] ?? 0);
    $price = (int)($_POST['price'] ?? 0);
    
    // New area measurement system
    $area_value = !empty($_POST['area_value']) ? (float)$_POST['area_value'] : null;
    $area_unit = $_POST['area_unit'] ?? 'sqft';
    
    // Convert to both sqft and ana for database storage
    $area_sqft = null;
    $area_ana = null;
    
    if ($area_value && $area_unit) {
        if ($area_unit === 'sqft') {
            $area_sqft = $area_value;
            $area_ana = NepalAreaUnits::convert($area_value, 'sqft', 'ana');
        } elseif ($area_unit === 'ana') {
            $area_ana = $area_value;
            $area_sqft = NepalAreaUnits::convert($area_value, 'ana', 'sqft');
        } else {
            // Convert from any unit to both sqft and ana
            $area_sqft = NepalAreaUnits::convert($area_value, $area_unit, 'sqft');
            $area_ana = NepalAreaUnits::convert($area_value, $area_unit, 'ana');
        }
    }
    
    $bedrooms = !empty($_POST['bedrooms']) ? (int)$_POST['bedrooms'] : null;
    $bathrooms = !empty($_POST['bathrooms']) ? (int)$_POST['bathrooms'] : null;
    $description = trim($_POST['description'] ?? '');
    $is_negotiable = isset($_POST['is_negotiable']) ? 1 : 0;
    
    // File upload handling
    $cover_image = $property['cover_image'];
    $property_video = $property['property_video'];
    $three_d_preview = $property['three_d_preview'];
    
    // Handle cover image upload
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/properties/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $filename = uniqid() . '.' . $file_extension;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $filepath)) {
                $cover_image = $filepath;
            }
        }
    }
    
    // Handle property video upload
    if (isset($_FILES['property_video']) && $_FILES['property_video']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/videos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['property_video']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $filename = uniqid() . '.' . $file_extension;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['property_video']['tmp_name'], $filepath)) {
                $property_video = $filepath;
            }
        }
    }
    
    // Handle 3D preview file upload
    if (isset($_FILES['three_d_preview']) && $_FILES['three_d_preview']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/3d_previews/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['three_d_preview']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['obj', 'fbx', 'dae', '3ds', 'max', 'blend', 'zip', 'rar'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $filename = uniqid() . '.' . $file_extension;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['three_d_preview']['tmp_name'], $filepath)) {
                $three_d_preview = $filepath;
            }
        }
    }
    
    // Validate required fields
    $errors = [];
    if (empty($title)) $errors[] = 'Property title is required';
    if (empty($type)) $errors[] = 'Property type is required';
    if (empty($district)) $errors[] = 'District is required';
    if (empty($price) || $price <= 0) $errors[] = 'Valid price is required';
    if (empty($area_value) || $area_value <= 0) $errors[] = 'Valid area is required';
    if (empty($area_unit)) $errors[] = 'Area unit is required';
    if (empty($description)) $errors[] = 'Description is required';
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE properties SET title = :title, type = :type, district = :district, municipality = :municipality, ward = :ward, price = :price, area_sqft = :area_sqft, area_ana = :area_ana, area_unit = :area_unit, bedrooms = :bedrooms, bathrooms = :bathrooms, description = :description, cover_image = :cover_image, property_video = :property_video, three_d_preview = :three_d_preview, is_negotiable = :is_negotiable WHERE id = :id AND seller_id = :seller_id");
            
            $stmt->execute([
                ':title' => $title,
                ':type' => $type,
                ':district' => $district,
                ':municipality' => $municipality,
                ':ward' => $ward,
                ':price' => $price,
                ':area_sqft' => $area_sqft,
                ':area_ana' => $area_ana,
                ':area_unit' => $area_unit,
                ':bedrooms' => $bedrooms,
                ':bathrooms' => $bathrooms,
                ':description' => $description,
                ':cover_image' => $cover_image,
                ':property_video' => $property_video,
                ':three_d_preview' => $three_d_preview,
                ':is_negotiable' => $is_negotiable,
                ':id' => $property_id,
                ':seller_id' => $currentUser['id']
            ]);
            
            $success_message = 'Property updated successfully!';
            
            // Refresh property data
            $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = :id AND seller_id = :seller_id");
            $stmt->execute([':id' => $property_id, ':seller_id' => $currentUser['id']]);
            $property = $stmt->fetch();
            
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get districts for form
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

// Determine current area value and unit for form
$current_area_value = null;
$current_area_unit = $property['area_unit'] ?? 'sqft';

if ($current_area_unit === 'sqft' && $property['area_sqft']) {
    $current_area_value = $property['area_sqft'];
} elseif ($current_area_unit === 'ana' && $property['area_ana']) {
    $current_area_value = $property['area_ana'];
} else {
    // Default to sqft if no specific unit is set
    $current_area_value = $property['area_sqft'] ?? $property['area_ana'];
    $current_area_unit = 'sqft';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Edit Property · NepaEstate</title>
    <meta name="description" content="Edit your property listing on NepaEstate."/>
    <link rel="stylesheet" href="assets/css/styles.css"/>
    <style>
        .property-form {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-section {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .form-section h3 {
            margin-top: 0;
            color: var(--accent);
            border-bottom: 2px solid var(--accent);
            padding-bottom: 8px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--ink);
        }
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--line);
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--accent);
        }
        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
        .file-upload {
            border: 2px dashed var(--accent);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .file-upload:hover {
            border-color: var(--brass);
            background: rgba(215, 38, 61, 0.05);
        }
        .file-upload input[type="file"] {
            display: none;
        }
        .btn-submit {
            background: linear-gradient(135deg, var(--accent), #e74c3c);
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(215, 38, 61, 0.3);
        }
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: rgba(47, 176, 112, 0.1);
            border: 1px solid rgba(47, 176, 112, 0.3);
            color: #2fb070;
        }
        .alert-error {
            background: rgba(215, 38, 61, 0.1);
            border: 1px solid rgba(215, 38, 61, 0.3);
            color: #d7263d;
        }
        .image-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 12px;
            margin-top: 12px;
        }
        .image-preview img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid var(--line);
        }
        .existing-images {
            margin: 16px 0;
            padding: 16px;
            background: var(--elev);
            border-radius: 8px;
        }
        .existing-images h4 {
            margin-top: 0;
            color: var(--accent);
        }
        .existing-image {
            display: inline-block;
            margin: 8px;
            position: relative;
        }
        .existing-image img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid var(--line);
        }
        .remove-image {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            cursor: pointer;
            font-size: 12px;
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
                <a class="btn" href="my-properties.php">My Properties</a>
                <a class="btn" href="profile.php">My Profile</a>
                <a class="btn btn-primary" href="logout.php">Logout</a>
            </div>
        </div>
    </header>

    <main class="container" style="margin-top: 20px;">
        <div class="property-form">
            <div class="form-section">
                <h1>✏️ Edit Property</h1>
                <p>Update your property listing details below.</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    ✅ <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    ❌ <?= implode('<br>', $errors) ?>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="form-section">
                <h3>📋 Basic Information</h3>
                
                <div class="form-group">
                    <label class="form-label">🏷️ Property Title *</label>
                    <input class="form-input" type="text" name="title" required 
                           placeholder="e.g., Beautiful 4 BHK House in Kathmandu" 
                           value="<?= htmlspecialchars($property['title']) ?>"/>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">🏠 Property Type *</label>
                        <select class="form-select" name="type" required>
                            <option value="">Select Type</option>
                            <option value="land" <?= $property['type'] === 'land' ? 'selected' : '' ?>>Land</option>
                            <option value="house" <?= $property['type'] === 'house' ? 'selected' : '' ?>>House</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">💰 Price (Rs) *</label>
                        <input class="form-input" type="number" name="price" required 
                               placeholder="e.g., 15000000" min="1"
                               value="<?= htmlspecialchars($property['price']) ?>"/>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">🗺️ District *</label>
                        <select class="form-select" name="district" required>
                            <option value="">Select District</option>
                            <?php foreach ($districts as $dist): ?>
                                <option value="<?= $dist ?>" <?= $property['district'] === $dist ? 'selected' : '' ?>>
                                    <?= $dist ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">🏘️ Municipality</label>
                        <input class="form-input" type="text" name="municipality" 
                               placeholder="e.g., Kathmandu Metropolitan City"
                               value="<?= htmlspecialchars($property['municipality'] ?? '') ?>"/>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">🏠 Ward Number</label>
                        <input class="form-input" type="text" name="ward" 
                               placeholder="e.g., 5" maxlength="2"
                               value="<?= htmlspecialchars($property['ward'] ?? '') ?>"/>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">📏 Area</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                            <input class="form-input" type="number" name="area_value" 
                                   placeholder="Value" min="0.01" step="0.01"
                                   value="<?= htmlspecialchars($current_area_value) ?>"/>
                            <select class="form-select" name="area_unit" required>
                                <option value="">Select Unit</option>
                                <?php foreach ($areaUnits as $unitKey => $unitInfo): ?>
                                    <option value="<?= $unitKey ?>" <?= $current_area_unit === $unitKey ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($unitInfo['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="house-details" style="display: <?= $property['type'] === 'house' ? 'block' : 'none' ?>;">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">🛏️ Bedrooms</label>
                            <select class="form-select" name="bedrooms">
                                <option value="">Select</option>
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?= $i ?>" <?= $property['bedrooms'] == $i ? 'selected' : '' ?>>
                                        <?= $i ?> Bedroom<?= $i > 1 ? 's' : '' ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">🚿 Bathrooms</label>
                            <select class="form-select" name="bathrooms">
                                <option value="">Select</option>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?= $i ?>" <?= $property['bathrooms'] == $i ? 'selected' : '' ?>>
                                        <?= $i ?> Bathroom<?= $i > 1 ? 's' : '' ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">📝 Description</label>
                    <textarea class="form-textarea" name="description" 
                              placeholder="Describe your property in detail. Include features, amenities, nearby facilities, etc."><?= htmlspecialchars($property['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" name="is_negotiable" id="is_negotiable" 
                               <?= $property['is_negotiable'] ? 'checked' : '' ?>/>
                        <label for="is_negotiable">💬 Price is negotiable</label>
                    </div>
                </div>

                <h3>📸 Property Images</h3>
                
                <!-- Existing Images -->
                <?php if (!empty($existingImages)): ?>
                <div class="existing-images">
                    <h4>🖼️ Current Images</h4>
                    <?php foreach ($existingImages as $img): ?>
                        <div class="existing-image">
                            <img src="<?= htmlspecialchars($img['image_url']) ?>" alt="Property image" onerror="this.onerror=null;this.src='assets/img/placeholder.svg'"/>
                            <button type="button" class="remove-image" onclick="removeImage(<?= $img['id'] ?>)">×</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label">🖼️ Update Cover Image</label>
                    <div class="file-upload" onclick="document.getElementById('cover_image').click()">
                        <div style="font-size: 24px; margin-bottom: 8px;">📷</div>
                        <div>Click to upload new cover image</div>
                        <div style="font-size: 14px; color: var(--muted); margin-top: 4px;">
                            JPG, PNG, or GIF (max 5MB)
                        </div>
                    </div>
                    <input type="file" name="cover_image" id="cover_image" accept="image/*" 
                           onchange="previewImage(this, 'cover-preview')"/>
                    <div id="cover-preview" class="image-preview"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">🖼️ Add More Images</label>
                    <div class="file-upload" onclick="document.getElementById('additional_images').click()">
                        <div style="font-size: 24px; margin-bottom: 8px;">📁</div>
                        <div>Click to upload additional images</div>
                        <div style="font-size: 14px; color: var(--muted); margin-top: 4px;">
                            Upload multiple images (max 5MB each)
                        </div>
                    </div>
                    <input type="file" name="additional_images[]" id="additional_images" accept="image/*" multiple
                           onchange="previewMultipleImages(this, 'additional-preview')"/>
                    <div id="additional-preview" class="image-preview"></div>
                </div>

                <h3>🎥 Property Video</h3>
                
                <div class="form-group">
                    <label class="form-label">🎬 Update Property Video</label>
                    <?php if ($property['property_video']): ?>
                        <div style="margin-bottom: 16px;">
                            <strong>Current Video:</strong> <a href="<?= htmlspecialchars($property['property_video']) ?>" target="_blank">View Current Video</a>
                        </div>
                    <?php endif; ?>
                    <div class="file-upload" onclick="document.getElementById('property_video').click()">
                        <div style="font-size: 24px; margin-bottom: 8px;">🎥</div>
                        <div>Click to upload new property video</div>
                        <div style="font-size: 14px; color: var(--muted); margin-top: 4px;">
                            MP4, AVI, MOV, WMV, or FLV (max 100MB)
                        </div>
                    </div>
                    <input type="file" name="property_video" id="property_video" accept="video/*"
                           onchange="previewVideo(this, 'video-preview')"/>
                    <div id="video-preview" class="image-preview"></div>
                </div>

                <h3>🔮 3D Preview</h3>
                
                <div class="form-group">
                    <label class="form-label">🎯 Update 3D Model File</label>
                    <?php if ($property['three_d_preview']): ?>
                        <div style="margin-bottom: 16px;">
                            <strong>Current 3D File:</strong> <a href="<?= htmlspecialchars($property['three_d_preview']) ?>" target="_blank">Download Current 3D File</a>
                        </div>
                    <?php endif; ?>
                    <div class="file-upload" onclick="document.getElementById('three_d_preview').click()">
                        <div style="font-size: 24px; margin-bottom: 8px;">🔮</div>
                        <div>Click to upload new 3D preview file</div>
                        <div style="font-size: 14px; color: var(--muted); margin-top: 4px;">
                            GLB, GLTF, OBJ, FBX, or DAE (max 50MB)
                        </div>
                    </div>
                    <input type="file" name="three_d_preview" id="three_d_preview" accept=".glb,.gltf,.obj,.fbx,.dae"
                           onchange="preview3DFile(this, '3d-preview')"/>
                    <div id="3d-preview" class="image-preview"></div>
                </div>

                <div class="form-group" style="text-align: center; margin-top: 32px;">
                    <button type="submit" class="btn-submit">
                        💾 Update Property
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Show/hide house-specific fields based on property type
        document.querySelector('select[name="type"]').addEventListener('change', function() {
            const houseDetails = document.getElementById('house-details');
            if (this.value === 'house') {
                houseDetails.style.display = 'block';
                // Make bedrooms and bathrooms required for houses
                document.querySelector('select[name="bedrooms"]').required = true;
                document.querySelector('select[name="bathrooms"]').required = true;
            } else {
                houseDetails.style.display = 'none';
                // Remove required for non-houses
                document.querySelector('select[name="bedrooms"]').required = false;
                document.querySelector('select[name="bathrooms"]').required = false;
            }
        });

        // Image preview functions
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    preview.appendChild(img);
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function previewMultipleImages(input, previewId) {
            const preview = document.getElementById(previewId);
            preview.innerHTML = '';
            
            if (input.files) {
                Array.from(input.files).forEach(file => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        preview.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                });
            }
        }

        // Video preview function
        function previewVideo(input, previewId) {
            const preview = document.getElementById(previewId);
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const video = document.createElement('video');
                video.controls = true;
                video.muted = true;
                video.src = URL.createObjectURL(file);
                preview.appendChild(video);
            }
        }

        // 3D file preview function
        function preview3DFile(input, previewId) {
            const preview = document.getElementById(previewId);
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const fileInfo = document.createElement('div');
                fileInfo.style.padding = '16px';
                fileInfo.style.background = 'var(--elev)';
                fileInfo.style.borderRadius = '8px';
                fileInfo.style.textAlign = 'center';
                fileInfo.innerHTML = `
                    <div style="font-size: 24px; margin-bottom: 8px;">🔮</div>
                    <div style="font-weight: 600; color: var(--accent);">${file.name}</div>
                    <div style="color: var(--muted); font-size: 14px;">${(file.size / (1024 * 1024)).toFixed(2)} MB</div>
                `;
                preview.appendChild(fileInfo);
            }
        }

        // Remove image function
        function removeImage(imageId) {
            if (confirm('Are you sure you want to remove this image?')) {
                // You can implement AJAX call here to remove the image
                // For now, just hide it
                const imageElement = event.target.parentElement;
                imageElement.style.display = 'none';
            }
        }
    </script>
</body>
</html>
