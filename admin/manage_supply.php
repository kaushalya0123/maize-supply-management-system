<?php
// admin/manage_supply.php
$pageTitle = 'Manage Maize Supply';
require_once '../config/db.php';
require_once '../includes/header.php';
checkRole('admin');

$msg = '';
$error = '';

// Handle Delete Supply
if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];
    try {
        $pdo->beginTransaction();

        // 1. Get supply details for stock adjustment
        $stmt = $pdo->prepare("SELECT variety_id, quantity FROM maize WHERE maize_id = ?");
        $stmt->execute([$deleteId]);
        $supply = $stmt->fetch();

        if ($supply) {
            // 2. Decrement Stock
            $stmt = $pdo->prepare("UPDATE stock SET quantity = quantity - ? WHERE variety_id = ? AND company_id = 1");
            $stmt->execute([$supply['quantity'], $supply['variety_id']]);

            // 3. Delete related payments
            $stmt = $pdo->prepare("DELETE FROM payment WHERE maize_id = ?");
            $stmt->execute([$deleteId]);

            // 4. Delete Supply record
            $stmt = $pdo->prepare("DELETE FROM maize WHERE maize_id = ?");
            $stmt->execute([$deleteId]);
        }

        $pdo->commit();
        $msg = "Supply record deleted and stock adjusted successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error deleting supply: " . $e->getMessage();
    }
}

// Handle Add/Edit Supply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_supply']) || isset($_POST['edit_supply']))) {
    $farmer_id = $_POST['farmer_id'];
    $land_name = trim($_POST['land_name']);
    $land_size = $_POST['land_size'];
    $variety_id = $_POST['variety_id'];
    $quantity = $_POST['quantity'];
    $price_per_kg = $_POST['price_per_kg'];
    $maize_id = $_POST['maize_id'] ?? null;

    try {
        $stmt = $pdo->prepare("
SELECT land_id
FROM land
WHERE farmer_id = ?
AND TRIM(LOWER(land_name)) = TRIM(LOWER(?))
LIMIT 1
");

$stmt->execute([
    $farmer_id,
    $land_name
]);

$land = $stmt->fetch(PDO::FETCH_ASSOC);

if ($land) {

    $land_id = $land['land_id'];

} else {
$stmt=$pdo->prepare("
INSERT INTO land
(land_name,land_size,unit,farmer_id)
VALUES
(?,?,?,?)
");

$stmt->execute([
    $land_name,
    $land_size,
    'Acres',
    $farmer_id
]);
   

    $land_id = $pdo->lastInsertId();
}


        $pdo->beginTransaction();

        if ($maize_id) {
            // EDIT LOGIC
            // 1. Revert previous stock
            $stmt = $pdo->prepare("SELECT variety_id, quantity FROM maize WHERE maize_id = ?");
            $stmt->execute([$maize_id]);
            $old = $stmt->fetch();

            $stmt = $pdo->prepare("UPDATE stock SET quantity = quantity - ? WHERE variety_id = ? AND company_id = 1");
            $stmt->execute([$old['quantity'], $old['variety_id']]);

            // 2. Update Supply Record
            $stmt = $pdo->prepare("UPDATE maize SET farmer_id = ?, land_id = ?, variety_id = ?, quantity = ?, price_per_kg = ? WHERE maize_id = ?");
            $stmt->execute([$farmer_id, $land_id, $variety_id, $quantity, $price_per_kg, $maize_id]);

            $msg = "Supply record updated successfully!";
        } else {
            // ADD LOGIC
            $stmt = $pdo->prepare("INSERT INTO maize (farmer_id, land_id, variety_id, quantity, price_per_kg) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$farmer_id, $land_id, $variety_id, $quantity, $price_per_kg]);
            $maize_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("
      UPDATE land
      SET maize_id = ?
      WHERE land_id = ?
  ");

            $stmt->execute([
            $maize_id,
            $land_id
]);

            $msg = "Supply recorded successfully!";
        }

        // 3. Update Stock (Add new quantity)
        $company_id = 1;
        $stmt = $pdo->prepare("SELECT * FROM stock WHERE company_id = ? AND variety_id = ?");
        $stmt->execute([$company_id, $variety_id]);
        $stockItem = $stmt->fetch();

        if ($stockItem) {
            $stmt = $pdo->prepare("UPDATE stock SET quantity = quantity + ? WHERE stock_id = ?");
            $stmt->execute([$quantity, $stockItem['stock_id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO stock (company_id, variety_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$company_id, $variety_id, $quantity]);
        }

        // 4. Update Payment (Simplified)
        $basic_amount = $quantity * $price_per_kg;
        $stmt = $pdo->prepare("SELECT land_size FROM land WHERE land_id = ?");
        $stmt->execute([$land_id]);
        $land_size = $stmt->fetchColumn();
        $bonus_amount = ($land_size > 2 && $quantity > 1000) ? 500.00 : 0;
        $total_paid = $basic_amount + $bonus_amount;

        $stmt = $pdo->prepare("DELETE FROM payment WHERE maize_id = ?");
        $stmt->execute([$maize_id]);
        $stmt = $pdo->prepare("INSERT INTO payment (maize_id, farmer_id, basic_amount, bonus_amount, total_paid) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$maize_id, $farmer_id, $basic_amount, $bonus_amount, $total_paid]);

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch for Edit
$editData = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM maize WHERE maize_id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $editData = $stmt->fetch();
}

// Search & Fetch Records
$search = $_GET['search'] ?? '';
$query = "SELECT m.*, f.name as farmer_name, l.land_name, v.type as variety_type 
          FROM maize m 
          JOIN farmer f ON m.farmer_id = f.farmer_id 
          JOIN land l ON m.land_id = l.land_id 
          JOIN variety v ON m.variety_id = v.variety_id";

if ($search) {
    $query .= " WHERE f.name LIKE :search OR l.land_name LIKE :search OR v.type LIKE :search";
}
$query .= " ORDER BY m.supply_date DESC";

$stmt = $pdo->prepare($query);
if ($search) {
    $stmt->execute(['search' => "%$search%"]);
} else {
    $stmt->execute();
}
$supplies = $stmt->fetchAll();
?>

<div class="top-bar" style="margin-bottom: 2rem; background: white; padding: 1rem 2rem; border-radius: 15px;">
    <h2 style="font-size: 1.5rem; color: #2e7d32; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-truck-ramp-box"></i> Supply Management
    </h2>
    <form method="GET" class="search-box" style="width: 400px; margin-left: auto;">
        <input type="text" name="search" placeholder="Search farmer, land, variety..."
            value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" style="display:none;"></button>
    </form>
</div>

<?php if ($msg): ?>
    <div class="status status-approved" style="margin-bottom: 1.5rem;">
        <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="status status-pending" style="margin-bottom: 1.5rem;">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<div style="display: flex; flex-direction: column; gap: 2rem;">
    <!-- Form Section -->
    <div class="table-container" style="max-width: 800px; margin: 0 auto;">
        <h3><?php echo $editData ? 'Edit' : 'Record New'; ?> Supply</h3>
        <form method="POST">
            <?php if ($editData): ?>
                <input type="hidden" name="maize_id" value="<?php echo $editData['maize_id']; ?>">
            <?php endif; ?>
            <div class="form-group">
                <label style="color: #1a1a1a; font-weight: 700;">Farmer</label>
                <select name="farmer_id" class="btn"
                    style="width:100%; border:1px solid #ddd; background:white; color:#333;" required>
                    <?php
                    $farmers = $pdo->query("SELECT * FROM farmer")->fetchAll();
                    foreach ($farmers as $f) {
                        $selected = ($editData && $editData['farmer_id'] == $f['farmer_id']) ? 'selected' : '';
                        echo "<option value='{$f['farmer_id']}' $selected>{$f['name']}</option>";
                    }
                    ?>
                </select>
            <div class="form-group">

<label style="color:#1a1a1a;font-weight:700;">
Land Name
</label>

<input
type="text"
name="land_name"
class="btn"
style="width:100%;border:1px solid #ddd;background:#fff;color:#333;"
placeholder="Enter Land Name"
required>

</div>

<div class="form-group">

<label style="color:#1a1a1a;font-weight:700;">
Land Size (Acres)
</label>

<input
type="number"
step="0.01"
name="land_size"
class="btn"
style="width:100%;border:1px solid #ddd;background:#fff;color:#333;"
required>

</div>
            
            <div class="form-group">
    <label style="color: #1a1a1a; font-weight: 700;">Variety</label>
    <select name="variety_id" id="variety_id" class="btn"
        style="width:100%; border:1px solid #ddd; background:white; color:#333;" required 
        onchange="updatePrice()">
        <option value="">Select Variety</option>
        <?php
        $varieties = $pdo->query("SELECT * FROM variety")->fetchAll();
        foreach ($varieties as $v) {
            $selected = ($editData && $editData['variety_id'] == $v['variety_id']) ? 'selected' : '';
            echo "<option value='{$v['variety_id']}' data-price='{$v['price_per_kg']}' $selected>{$v['type']}</option>";
        }
        ?>
    </select>
</div>
<div class="form-group">
                <label style="color: #1a1a1a; font-weight: 700;">Quantity (KG)</label>
                <input type="number" name="quantity" class="btn" style="width:100%; border:1px solid #ddd; background:white; color:#333;" required value="<?php echo $editData['quantity'] ?? ''; ?>">
            </div>

            <div class="form-group">
                <label style="color: #1a1a1a; font-weight: 700;">Price per KG</label>
                <input type="number" step="0.01" name="price_per_kg" id="price_per_kg" class="btn"
                    style="width:100%; border:1px solid #ddd; background:white; color:#333;" required
                    value="<?php echo $editData['price_per_kg'] ??  '0.00'; ?>">
            </div>
            <button type="submit" name="<?php echo $editData ? 'edit_supply' : 'add_supply'; ?>" class="login-btn"
                style="margin-top:0;">
                <?php echo $editData ? 'Update Record' : 'Record Supply'; ?>
            </button>
            <?php if ($editData): ?>
                <a href="manage_supply.php" class="btn"
                    style="display:block; text-align:center; margin-top:10px; background:#eee; color:#333;">Cancel Edit</a>
            <?php endif; ?>
        </form>
    </div>
<script>
function updatePrice() {
    var select = document.getElementById('variety_id');
    var priceInput = document.getElementById('price_per_kg');
    var selectedOption = select.options[select.selectedIndex];
    
    // තෝරාගත් option එකේ ඇති data-price අගය ගෙන input එකට දමයි
    var price = selectedOption.getAttribute('data-price');
    if (price) {
        priceInput.value = price;
    }
}
</script>

    <!-- Table Section -->
    <div class="table-container">
        <h3>Supply Records</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Farmer</th>
                    <th>Land</th>
                    <th>Variety</th>
                    <th>Qty (KG)</th>
                    <th>price_per_kg</th>
                    <th>total(LKR)</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($supplies as $s): ?>
                    <tr>
                        <td style="font-size: 0.8rem;"><?php echo date('Y-m-d', strtotime($s['supply_date'])); ?></td>
                        <td><strong><?php echo htmlspecialchars($s['farmer_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($s['land_name']); ?></td>
                        <td><span class="status status-approved"
                                style="background: #e8f5e9;padding:5px 10px;border-radius:5px;display:inline-block;"><?php echo htmlspecialchars($s['variety_type']); ?></span></td>
                        <td><?php echo number_format($s['quantity']); ?></td>
                        <td>LKR <?php echo number_format($s['price_per_kg'],2);?></td>
                        <td>LKR <?php echo number_format($s['quantity']*$s['price_per_kg'],2);?></td>

                        <td>
                            <div style="display: flex; gap: 5px;">
                                <a href="?edit_id=<?php echo $s['maize_id']; ?>" class="btn"
                                    style="background: #e3f2fd; color: #0288d1; padding: 5px 8px;"><i
                                        class="fas fa-edit"></i></a>
                                <a href="?delete_id=<?php echo $s['maize_id']; ?>"
                                    onclick="return confirm('Delete this record and adjust stock?')" class="btn"
                                    style="background: #fff5f5; color: #c53030; padding: 5px 8px;"><i
                                        class="fas fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>