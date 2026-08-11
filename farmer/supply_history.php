<?php
// farmer/supply_history.php
$pageTitle = 'Supply History';
require_once '../config/db.php';
require_once '../includes/header.php';
checkRole('farmer');

// 1. DYNAMIC IDENTITY LOOKUP
$farmerId = $_SESSION['farmer_id'];


// 2. FETCH OPTIONS FOR DROPDOWNS
$varieties = $pdo->query("SELECT * FROM variety")->fetchAll();
$lands_list = $pdo->prepare("SELECT * FROM land WHERE farmer_id = ?");
$lands_list->execute([$farmerId]);
$lands = $lands_list->fetchAll();

// 3. FILTER LOGIC
$search_land_id = $_GET['land_id'] ?? '';
$search_variety_id = $_GET['variety_id'] ?? '';
$search_date = $_GET['date'] ?? '';
$min_qty = $_GET['min_qty'] ?? '';
$min_value = $_GET['min_value'] ?? '';

$whereClause = "WHERE m.farmer_id = ?";
$params = [$farmerId];

if (!empty($search_land_id)) {
    $whereClause .= " AND m.land_id = ?";
    $params[] = $search_land_id;
}

if (!empty($search_variety_id)) {
    $whereClause .= " AND m.variety_id = ?";
    $params[] = $search_variety_id;
}

if (!empty($search_date)) {
    $whereClause .= " AND DATE(m.supply_date) = ?";
    $params[] = $search_date;
}

if (!empty($min_qty)) {
    $whereClause .= " AND m.quantity >= ?";
    $params[] = $min_qty;
}

if (!empty($min_value)) {
    $whereClause .= " AND m.total_ammount >= ?";
    $params[] = $min_value;
}

$query = "SELECT m.*, l.land_name, v.type 
          FROM maize m 
          JOIN land l ON m.land_id = l.land_id 
          JOIN variety v ON m.variety_id = v.variety_id 
          $whereClause 
          ORDER BY m.maize_id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$supplies = $stmt->fetchAll();
?>

<!-- Advanced Filter Bar -->
<div class="table-container" style="margin-bottom: 2rem; padding: 2rem; border-left: 5px solid var(--primary);">
    <form method="GET"
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; align-items: end;">
        <!-- Date Filter -->
        <div>
            <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.85rem; color: #4a5568;">
                <i class="fas fa-calendar-alt"></i> Date
            </label>
            <input type="date" name="date" value="<?php echo htmlspecialchars($search_date); ?>" class="btn"
                style="width: 100%; border: 1px solid #cbd5e0; padding: 10px; background: white; text-align: left;">
        </div>

        

        <!-- Variety Filter -->
        <div>
            <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.85rem; color: #4a5568;">
                <i class="fas fa-seedling"></i> Variety
            </label>
            <select name="variety_id" class="btn"
                style="width: 100%; border: 1px solid #cbd5e0; padding: 10px; background: white; text-align: left; appearance: auto;">
                <option value="">All Varieties</option>
                <?php foreach ($varieties as $vt): ?>
                    <option value="<?php echo $vt['variety_id']; ?>" <?php echo $search_variety_id == $vt['variety_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($vt['type']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>


       

        <!-- Action Buttons -->
        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 20px; flex: 1;">
                <i class="fas fa-filter"></i> Filter
            </button>
            <?php if (!empty($search_land_id) || !empty($search_variety_id) || !empty($search_date) || !empty($min_qty) || !empty($min_value)): ?>
                <a href="supply_history.php" class="btn" style="background: #edf2f7; color: #2d3748; padding: 10px 15px;">
                    <i class="fas fa-undo"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="table-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h3 style="margin: 0;"><i class="fas fa-history" style="color: var(--primary); margin-right: 10px;"></i> Supply
            History Details</h3>
        <span
            style="background: #edf2f7; padding: 5px 15px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; color: var(--primary);">
            <?php echo count($supplies); ?> RECORDS
        </span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>land Name</th>
                <th>Variety</th>
                <th>Quantity (KG)</th>
                <th>Total Value (LKR)</th>
                
            </tr>
        </thead>
        <tbody>
            <?php if (count($supplies) > 0): ?>
                <?php foreach ($supplies as $row): ?>
                    <tr>
                        <td><i class="far fa-calendar-check" style="color: #a0aec0; margin-right: 5px;"></i>
                            <?php echo date('Y-m-d', strtotime($row['supply_date'])); ?></td>
                        <td><strong><?php echo htmlspecialchars($row['land_name']); ?></strong></td>
                        <td><span class="status status-approved"
                                style="background: #e3f2fd; color: #0288d1; border: none; padding: 4px 12px;"><?php echo htmlspecialchars($row['type']); ?></span>
                        </td>
                        <td><?php echo number_format($row['quantity']); ?> KG</td>
                        <td><strong style="color: #2e7d32;">LKR <?php echo number_format($row['total_ammount'], 2); ?></strong>
                        </td>
                        
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 5rem; color: #a0aec0;">
                        <div style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.3;"><i class="fas fa-search-minus"></i>
                        </div>
                        <h4 style="color: #4a5568;">No records found matching your filters</h4>
                        <p style="margin-top: 10px;">Try adjusting your criteria or clearing the filters.</p>
                        <a href="supply_history.php" class="btn btn-primary"
                            style="margin-top: 20px; display: inline-block;">View All Records</a>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>


<?php require_once '../includes/footer.php'; ?>