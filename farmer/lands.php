<?php
// farmer/lands.php
$pageTitle = 'My Registered Lands';
require_once '../config/db.php';
require_once '../includes/header.php';
checkRole('farmer');

// 1. DYNAMIC IDENTITY LOOKUP (Link to logged-in user)
$username = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT farmer_id FROM farmer WHERE user_id = (SELECT id FROM users WHERE username = ?)");
$stmt->execute([$username]);
$farmer = $stmt->fetch();
$farmerId = $farmer ? $farmer['farmer_id'] : 0;

// 2. SEARCH LOGIC
$search = $_GET['search'] ?? '';
$whereClause = "WHERE farmer_id = ?";
$params = [$farmerId];

if (!empty($search)) {
    $whereClause .= " AND (land_name LIKE ? OR land_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare("SELECT * FROM land $whereClause");
$stmt->execute($params);
$lands = $stmt->fetchAll();
?>

<!-- Premium Search Bar -->
<div class="table-container" style="margin-bottom: 1.5rem; padding: 1.2rem;">
    <form method="GET" style="display: flex; gap: 15px; align-items: center;">
        <div style="flex: 1; position: relative;">
            <i class="fas fa-search"
                style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #718096;"></i>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                placeholder="Search land by name or ID..." class="btn"
                style="width: 100%; padding: 12px 12px 12px 45px; border: 1px solid #e2e8f0; background: white; color: #1a202c; text-align: left;">
        </div>
        <button type="submit" class="btn btn-primary" style="padding: 12px 30px;">
            <i class="fas fa-filter"></i> Search
        </button>
        <?php if (!empty($search)): ?>
            <a href="lands.php" class="btn" style="background: #edf2f7; color: #4a5568;">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="table-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h3 style="margin: 0;"><i class="fas fa-map-location-dot" style="color: #2e7d32; margin-right: 10px;"></i> Lands
            Details</h3>
        <span style="font-size: 0.85rem; color: #718096;"><?php echo count($lands); ?> Result(s) Found</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Land ID</th>
                <th>Land Name</th>
                <th>Size</th>
                <th>Unit</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($lands) > 0): ?>
                <?php foreach ($lands as $row): ?>
                    <tr>
                        <td>#<?php echo $row['land_id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($row['land_name']); ?></strong></td>
                        <td><?php echo number_format($row['land_size'], 1); ?></td>
                        <td><span class="status status-approved"
                                style="background: #e6f6e6; border: none;"><?php echo htmlspecialchars($row['unit']); ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 3rem; color: #a0aec0;">
                        <i class="fas fa-folder-open" style="font-size: 2rem; display: block; margin-bottom: 1rem;"></i>
                        No land records found
                        <?php echo !empty($search) ? "matching your search." : "registered to your account."; ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once '../includes/footer.php'; ?>