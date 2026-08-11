<?php
// admin/manage_lands.php
$pageTitle = 'Manage Lands';
require_once '../config/db.php';
require_once '../includes/header.php';
checkRole('admin');

// 1. SEARCH LOGIC (Global search for Admin)
$search = $_GET['search'] ?? '';
$whereClause = "";
$params = [];

if (!empty($search)) {
    $whereClause = "WHERE (l.land_name LIKE ? OR l.land_id LIKE ? OR f.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query = "SELECT l.*, f.name as farmer_name 
          FROM land l 
          JOIN farmer f ON l.farmer_id = f.farmer_id 
          $whereClause 
          ORDER BY l.land_id DESC";

$stmt = $pdo->prepare($query);
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
                placeholder="Search land by name, ID, or farmer..." class="btn"
                style="width: 100%; padding: 12px 12px 12px 45px; border: 1px solid #e2e8f0; background: white; color: #1a202c; text-align: left;">
        </div>
        <button type="submit" class="btn btn-primary" style="padding: 12px 30px;">
            <i class="fas fa-filter"></i> Search
        </button>
        <?php if (!empty($search)): ?>
            <a href="manage_lands.php" class="btn" style="background: #edf2f7; color: #4a5568;">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="table-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h3 style="margin: 0;"><i class="fas fa-map-location-dot" style="color: #2e7d32; margin-right: 10px;"></i>
            Global Land Directory</h3>
        <span style="font-size: 0.85rem; color: #718096;">
            <?php echo count($lands); ?> Land(s) Found
        </span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Land ID</th>
                <th>Land Name</th>
                <th>Farmer OWNER</th>
                <th>Size</th>
                <th>Unit</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($lands) > 0): ?>
                <?php foreach ($lands as $row): ?>
                    <tr>
                        <td>
                            <?php echo $row['land_id']; ?>
                        </td>
                        <td><strong>
                                <?php echo htmlspecialchars($row['land_name']); ?>
                            </strong></td>
                        <td><i class="fas fa-user" style="color: #4a5568; margin-right: 5px;"></i>
                            <?php echo htmlspecialchars($row['farmer_name']); ?>
                        </td>
                        <td>
                            <?php echo number_format($row['land_size'], 1); ?>
                        </td>
                        <td><span class="status status-approved" style="background: #e3f2fd; color: #0288d1; border: none;">
                                <?php echo htmlspecialchars($row['unit']); ?>
                            </span></td>
                        <td>
                            <a href="edit_land.php?id=<?php echo $row['land_id']; ?>" class="btn"
                                style="background: #f0f2f5; padding: 5px 12px; font-size: 0.8rem;"><i class="fas fa-edit"></i>
                                Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 3rem; color: #a0aec0;">
                        <i class="fas fa-folder-open" style="font-size: 2rem; display: block; margin-bottom: 1rem;"></i>
                        No land records matching your query.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>