<?php
// admin/manage_varieties.php
$pageTitle = 'Manage Varieties';
require_once '../config/db.php';
require_once '../includes/header.php';
checkRole('admin');

$msg = '';
$error = '';

// Handle Delete Variety
if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM variety WHERE variety_id = ?");
        $stmt->execute([$deleteId]);
        $msg = "Variety deleted successfully!";
    } catch (PDOException $e) {
        // FK constraint check code is 23000
        if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Constraint') !== false) {
            $error = "Cannot delete this variety. It is currently associated with active maize supplies, stock levels, or buyer orders.";
        } else {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle Add/Edit Variety
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_variety']) || isset($_POST['edit_variety']))) {
    $type = trim($_POST['type']);
    $price_per_kg = $_POST['price_per_kg'];
    $variety_id = $_POST['variety_id'] ?? null;

    if (empty($type) || !is_numeric($price_per_kg) || $price_per_kg <= 0) {
        $error = "Please provide a valid variety name and price per KG > 0.";
    } else {
        try {
            if ($variety_id) {
                // EDIT SQL
                $stmt = $pdo->prepare("UPDATE variety SET type = ?, price_per_kg = ? WHERE variety_id = ?");
                $stmt->execute([$type, $price_per_kg, $variety_id]);
                $msg = "Variety updated successfully!";
            } else {
                // ADD SQL
                $stmt = $pdo->prepare("INSERT INTO variety (type, price_per_kg) VALUES (?, ?)");
                $stmt->execute([$type, $price_per_kg]);
                $msg = "Variety added successfully!";
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Fetch for Edit
$editData = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM variety WHERE variety_id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $editData = $stmt->fetch();
}

// Search & Fetch Records
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM variety";
if ($search) {
    $query .= " WHERE type LIKE :search";
}
$query .= " ORDER BY variety_id DESC";

$stmt = $pdo->prepare($query);
if ($search) {
    $stmt->execute(['search' => "%$search%"]);
} else {
    $stmt->execute();
}
$varieties = $stmt->fetchAll();
?>

<div class="top-bar" style="margin-bottom: 2rem; background: white; padding: 1rem 2rem; border-radius: 15px;">
    <h2 style="font-size: 1.5rem; color: #2e7d32; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-seedling"></i> Variety Management
    </h2>
    <form method="GET" class="search-box" style="width: 400px; margin-left: auto;">
        <input type="text" name="search" placeholder="Search variety name..."
            value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" style="display:none;"></button>
    </form>
</div>

<?php if ($msg): ?>
    <div class="status status-approved" style="margin-bottom: 1.5rem;">
        <i class="fas fa-check-circle"></i>
        <?php echo $msg; ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="status status-pending"
        style="margin-bottom: 1.5rem; border-color: #feb2b2; background-color: #fff5f5; color: #c53030;">
        <i class="fas fa-exclamation-triangle"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<div style="display: flex; flex-direction: column; gap: 2rem;">
    <!-- Form Section -->
    <div class="table-container" style="max-width: 600px; margin: 0 auto; width: 100%;">
        <h3>
            <?php echo $editData ? 'Edit' : 'Add New'; ?> Maize Variety
        </h3>
        <form method="POST" style="margin-top: 1rem;">
            <?php if ($editData): ?>
                <input type="hidden" name="variety_id" value="<?php echo $editData['variety_id']; ?>">
            <?php endif; ?>
            <div class="form-group">
                <label style="color: #1a1a1a; font-weight: 700;">Variety Name / Type</label>
                <input type="text" name="type" class="btn"
                    style="width:100%; border:1px solid #ddd; background:white; color:#333;" required
                    value="<?php echo $editData ? htmlspecialchars($editData['type']) : ''; ?>"
                    placeholder="e.g. Hybrid, Normal, Sweet Corn">
            </div>
            <div class="form-group">
                <label style="color: #1a1a1a; font-weight: 700;">Base Price per KG (LKR)</label>
                <input type="number" step="0.01" name="price_per_kg" class="btn"
                    style="width:100%; border:1px solid #ddd; background:white; color:#333;" required
                    value="<?php echo $editData ? htmlspecialchars($editData['price_per_kg']) : '100.00'; ?>">
            </div>
            <button type="submit" name="<?php echo $editData ? 'edit_variety' : 'add_variety'; ?>" class="login-btn"
                style="margin-top:0;">
                <?php echo $editData ? 'Update Variety' : 'Add Variety'; ?>
            </button>
            <?php if ($editData): ?>
                <a href="manage_varieties.php" class="btn"
                    style="display:block; text-align:center; margin-top:10px; background:#eee; color:#333;">Cancel Edit</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table Section -->
    <div class="table-container">
        <h3>Varieties List</h3>
        <table style="margin-top: 1rem;">
            <thead>
                <tr>
                    <th>Variety ID</th>
                    <th>Variety Name</th>
                    <th>Price per KG</th>
                    <th>Date Added</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($varieties) > 0): ?>
                    <?php foreach ($varieties as $v): ?>
                        <tr>
                            <td>
                                <?php echo $v['variety_id']; ?>
                            </td>
                            <td><strong>
                                    <?php echo htmlspecialchars($v['type']); ?>
                                </strong></td>
                            <td>LKR
                                <?php echo number_format($v['price_per_kg'], 2); ?>
                            </td>
                            <td style="font-size: 0.85rem; color: #555;">
                                <?php echo date('Y-m-d', strtotime($v['created_at'])); ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <a href="?edit_id=<?php echo $v['variety_id']; ?>" class="btn"
                                        style="background: #e3f2fd; color: #0288d1; padding: 5px 8px;"><i
                                            class="fas fa-edit"></i> Edit</a>
                                    <a href="?delete_id=<?php echo $v['variety_id']; ?>"
                                        onclick="return confirm('Are you sure you want to delete this variety?')" class="btn"
                                        style="background: #fff5f5; color: #c53030; padding: 5px 8px;"><i
                                            class="fas fa-trash"></i> Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #666;">No varieties found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>