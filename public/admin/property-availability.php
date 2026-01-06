<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/user_auth.php';
require_once __DIR__ . '/../config/property_availability_manager.php';

// Check if user is logged in and is admin
$currentUser = get_logged_in_user();
if (!$currentUser || $currentUser['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$pdo = get_pdo();
$availabilityManager = new PropertyAvailabilityManager($pdo);

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['property_id'])) {
        $propertyId = (int)$_POST['property_id'];
        $action = $_POST['action'];
        $reason = $_POST['reason'] ?? null;
        
        $newStatus = '';
        switch ($action) {
            case 'mark_under_offer':
                $newStatus = 'under_offer';
                break;
            case 'mark_sold':
                $newStatus = 'sold';
                break;
            case 'mark_available':
                $newStatus = 'available';
                break;
            case 'withdraw':
                $newStatus = 'withdrawn';
                break;
            case 'relist':
                $newStatus = 'available';
                break;
        }
        
        if ($newStatus) {
            $success = $availabilityManager->updatePropertyStatus($propertyId, $newStatus, $currentUser['id'], $reason);
            if ($success) {
                $message = "Property status updated to {$newStatus} successfully.";
                $messageType = 'success';
            } else {
                $message = "Failed to update property status.";
                $messageType = 'error';
            }
        }
    }
}

// Get filters
$statusFilter = $_GET['status'] ?? '';
$districtFilter = $_GET['district'] ?? '';
$typeFilter = $_GET['type'] ?? '';

$filters = [];
if ($statusFilter) $filters['status'] = $statusFilter;
if ($districtFilter) $filters['district'] = $districtFilter;
if ($typeFilter) $filters['type'] = $typeFilter;

// Get property availability data
$properties = $availabilityManager->getAdminPropertyAvailabilityList($filters);
$statistics = $availabilityManager->getAvailabilityStatistics();

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
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Property Availability Management · RealEstate Admin</title>
    <meta name="description" content="Manage property availability status and track property performance."/>
    <link rel="stylesheet" href="../assets/css/styles.css"/>
    <style>
        .admin-header {
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
        .filters-section {
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
        .filter-select {
            padding: 12px;
            border: 2px solid var(--line);
            border-radius: 8px;
            font-size: 16px;
        }
        .properties-table {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
        }
        .table-header {
            background: var(--elev);
            padding: 16px 24px;
            border-bottom: 1px solid var(--line);
        }
        .table-content {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 16px 24px;
            text-align: left;
            border-bottom: 1px solid var(--line);
        }
        th {
            background: var(--elev);
            font-weight: 600;
            color: var(--ink);
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-available { background: rgba(47, 176, 112, 0.1); color: #2fb070; }
        .status-under_offer { background: rgba(255, 107, 53, 0.1); color: #ff6b35; }
        .status-sold { background: rgba(215, 38, 61, 0.1); color: #d7263d; }
        .status-withdrawn { background: rgba(108, 117, 125, 0.1); color: #6c757d; }
        .status-expired { background: rgba(52, 58, 64, 0.1); color: #343a40; }
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 6px;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: var(--card);
            margin: 15% auto;
            padding: 24px;
            border-radius: 12px;
            width: 80%;
            max-width: 500px;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .close {
            color: var(--muted);
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: var(--ink);
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
        .form-input, .form-textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--line);
            border-radius: 8px;
            font-size: 16px;
        }
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container nav">
            <a class="brand" href="../index.php">
                <div class="brand-logo"></div>
                <div class="brand-name">RealEstate Admin</div>
            </a>
            <div class="nav-actions">
                <a class="btn" href="dashboard.php">Dashboard</a>
                <a class="btn" href="properties.php">Properties</a>
                <a class="btn" href="users.php">Users</a>
                <a class="btn btn-primary" href="../logout.php">Logout</a>
            </div>
        </div>
    </header>

    <main class="container" style="margin-top: 20px;">
        <div class="admin-header">
            <h1>Property Availability Management</h1>
            <p>Monitor and manage property availability status across the platform</p>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert alert-<?= $messageType ?>" style="margin-bottom: 20px; padding: 16px; border-radius: 8px; background: <?= $messageType === 'success' ? 'rgba(47, 176, 112, 0.1)' : 'rgba(215, 38, 61, 0.1)' ?>; color: <?= $messageType === 'success' ? '#2fb070' : '#d7263d' ?>; border: 1px solid <?= $messageType === 'success' ? '#2fb070' : '#d7263d' ?>;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Overview -->
        <div class="stats-grid">
            <?php foreach ($statistics['status_statistics'] as $stat): ?>
                <div class="stat-card">
                    <div class="stat-number"><?= $stat['count'] ?></div>
                    <div class="stat-label"><?= ucfirst(str_replace('_', ' ', $stat['availability_status'])) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <h3 style="margin-top: 0; margin-bottom: 20px; color: var(--accent);">Filters</h3>
            <form method="get" class="filters-grid">
                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select class="filter-select" name="status">
                        <option value="">All Statuses</option>
                        <option value="available" <?= $statusFilter === 'available' ? 'selected' : '' ?>>Available</option>
                        <option value="under_offer" <?= $statusFilter === 'under_offer' ? 'selected' : '' ?>>Under Offer</option>
                        <option value="sold" <?= $statusFilter === 'sold' ? 'selected' : '' ?>>Sold</option>
                        <option value="withdrawn" <?= $statusFilter === 'withdrawn' ? 'selected' : '' ?>>Withdrawn</option>
                        <option value="expired" <?= $statusFilter === 'expired' ? 'selected' : '' ?>>Expired</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">District</label>
                    <select class="filter-select" name="district">
                        <option value="">All Districts</option>
                        <?php foreach ($districts as $dist): ?>
                            <option value="<?= $dist ?>" <?= $districtFilter === $dist ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dist) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Type</label>
                    <select class="filter-select" name="type">
                        <option value="">All Types</option>
                        <option value="land" <?= $typeFilter === 'land' ? 'selected' : '' ?>>Land</option>
                        <option value="house" <?= $typeFilter === 'house' ? 'selected' : '' ?>>House</option>
                    </select>
                </div>
                
                <div class="filter-group" style="display: flex; align-items: end;">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>

        <!-- Properties Table -->
        <div class="properties-table">
            <div class="table-header">
                <h3 style="margin: 0;">Properties (<?= count($properties) ?> found)</h3>
            </div>
            <div class="table-content">
                <table>
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Seller</th>
                            <th>Status</th>
                            <th>Price</th>
                            <th>Views</th>
                            <th>Favorites</th>
                            <th>Offers</th>
                            <th>Days Listed</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($properties as $property): ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($property['title']) ?></strong><br>
                                        <small style="color: var(--muted);">
                                            <?= ucfirst($property['type']) ?> in <?= htmlspecialchars($property['district']) ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <?= htmlspecialchars($property['seller_name']) ?><br>
                                        <small style="color: var(--muted);">
                                            <?= htmlspecialchars($property['seller_email']) ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $property['availability_status'] ?>">
                                        <?= ucfirst(str_replace('_', ' ', $property['availability_status'])) ?>
                                    </span>
                                </td>
                                <td>Rs <?= number_format($property['price']) ?></td>
                                <td><?= $property['view_count'] ?? 0 ?></td>
                                <td><?= $property['total_favorites'] ?? 0 ?></td>
                                <td><?= $property['active_offers'] ?? 0 ?></td>
                                <td><?= $property['days_listed'] ?? 0 ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <?php foreach ($property['available_actions'] as $action): ?>
                                            <button class="btn btn-small <?= $action['class'] ?>" 
                                                    onclick="showStatusModal('<?= $action['action'] ?>', <?= $property['id'] ?>, '<?= htmlspecialchars($property['title']) ?>')">
                                                <?= $action['label'] ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Status Update Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Update Property Status</h3>
                <span class="close" onclick="closeStatusModal()">&times;</span>
            </div>
            <form id="statusForm" method="post">
                <input type="hidden" id="modalAction" name="action">
                <input type="hidden" id="modalPropertyId" name="property_id">
                
                <div class="form-group">
                    <label class="form-label">Property</label>
                    <div id="modalPropertyTitle" style="padding: 12px; background: var(--elev); border-radius: 8px; color: var(--muted);"></div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Reason (Optional)</label>
                    <textarea class="form-textarea" name="reason" placeholder="Enter reason for status change..."></textarea>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="btn" onclick="closeStatusModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showStatusModal(action, propertyId, propertyTitle) {
            document.getElementById('modalAction').value = action;
            document.getElementById('modalPropertyId').value = propertyId;
            document.getElementById('modalPropertyTitle').textContent = propertyTitle;
            
            let actionLabel = '';
            switch (action) {
                case 'mark_under_offer': actionLabel = 'Mark Under Offer'; break;
                case 'mark_sold': actionLabel = 'Mark Sold'; break;
                case 'mark_available': actionLabel = 'Mark Available'; break;
                case 'withdraw': actionLabel = 'Withdraw Property'; break;
                case 'relist': actionLabel = 'Relist Property'; break;
            }
            
            document.getElementById('modalTitle').textContent = actionLabel + ' - ' + propertyTitle;
            document.getElementById('statusModal').style.display = 'block';
        }
        
        function closeStatusModal() {
            document.getElementById('statusModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('statusModal');
            if (event.target === modal) {
                closeStatusModal();
            }
        }
    </script>
</body>
</html>
