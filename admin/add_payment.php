<?php
$pageTitle = 'Add Payment';
require_once '../config/db.php';
require_once '../includes/header.php';
checkRole('admin');

$msg = "";

// Farmers
$farmers = $pdo->query("SELECT farmer_id, name FROM farmer")->fetchAll(PDO::FETCH_ASSOC);

// Orders
$orders = $pdo->query("
SELECT order_id, required_quantity
FROM orders
WHERE status='approved'
")->fetchAll(PDO::FETCH_ASSOC);

// Maize Supplies
$maize = $pdo->query("
SELECT maize_id, farmer_id, quantity, price_per_kg
FROM maize
")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
if (isset($_POST['save'])) {

    $farmer_id = $_POST['farmer_id'];
    $maize_id = $_POST['maize_id'];
    $order_id = $_POST['order_id'];

    // Check if this maize supply actually belongs to this farmer and is allocated to this order
    $stmtCheckAlloc = $pdo->prepare("
        SELECT os.quantity_used 
        FROM order_supply os 
        JOIN maize m ON os.maize_id = m.maize_id 
        WHERE os.maize_id = ? AND os.order_id = ? AND m.farmer_id = ?
    ");
    $stmtCheckAlloc->execute([$maize_id, $order_id, $farmer_id]);
    $allocCheck = $stmtCheckAlloc->fetch(PDO::FETCH_ASSOC);

    if (!$allocCheck) {
        echo "<div class='alert alert-danger'>Error: Selected Maize Supply is not allocated to this Buyer Order for this Farmer.</div>";
    } else {
        // Fetch all allocations for this farmer and order to calculate the aggregated payment
        $stmtAllocs = $pdo->prepare("
            SELECT os.maize_id, os.quantity_used, m.price_per_kg, l.land_size
            FROM order_supply os
            JOIN maize m ON os.maize_id = m.maize_id
            JOIN land l ON m.land_id = l.land_id
            WHERE m.farmer_id = ? AND os.order_id = ?
        ");
        $stmtAllocs->execute([$farmer_id, $order_id]);
        $allocations = $stmtAllocs->fetchAll(PDO::FETCH_ASSOC);

        // Get order details
        $stmtOrder = $pdo->prepare("SELECT required_quantity FROM orders WHERE order_id = ?");
        $stmtOrder->execute([$order_id]);
        $order = $stmtOrder->fetch(PDO::FETCH_ASSOC);

        if (!empty($allocations) && $order) {
            $order_qty = (float) $order['required_quantity'];
            $is_large_order = ($order_qty >= 30000);

            $total_quantity_used = 0.0;
            foreach ($allocations as $alloc) {
                $total_quantity_used += (float) $alloc['quantity_used'];
            }

            $basic = 0.0;
            $bonus = 0.0;
            $first_maize_id = $allocations[0]['maize_id'];

            foreach ($allocations as $alloc) {
                $alloc_basic = (float) $alloc['quantity_used'] * (float) $alloc['price_per_kg'];
                $basic += $alloc_basic;

                $land_size = (float) $alloc['land_size'];
                if ($is_large_order && $total_quantity_used >= 10000 && $land_size >= 4) {
                    $bonus += $alloc_basic * 0.02;
                }
            }
            $total = $basic + $bonus;

            // Check if payment already exists
            $check = $pdo->prepare("SELECT payment_id FROM payment WHERE farmer_id = ? AND order_id = ?");
            $check->execute([$farmer_id, $order_id]);
            $existing = $check->fetch(PDO::FETCH_ASSOC);

            if (!$existing) {
                $stmtInsert = $pdo->prepare("
                    INSERT INTO payment (maize_id, farmer_id, order_id, basic_amount, bonus_amount, total_paid, payment_date)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmtInsert->execute([
                    $first_maize_id,
                    $farmer_id,
                    $order_id,
                    $basic,
                    $bonus,
                    $total
                ]);
                echo "<div class='alert alert-success'>Payment Added Successfully.</div>";
            } else {
                // Update existing record to prevent duplicate
                $stmtUpdate = $pdo->prepare("
                    UPDATE payment 
                    SET maize_id = ?, 
                        basic_amount = ?, 
                        bonus_amount = ?, 
                        total_paid = ?, 
                        payment_date = NOW()
                    WHERE payment_id = ?
                ");
                $stmtUpdate->execute([
                    $first_maize_id,
                    $basic,
                    $bonus,
                    $total,
                    $existing['payment_id']
                ]);
                echo "<div class='alert alert-success'>Payment Updated Successfully.</div>";
            }
        }
    }
}


?>


<div class="table-container">

    <h2>Add Payment</h2>

    <form method="post">

        <label>Farmer</label>

        <select name="farmer_id" required>

            <option value="">Select Farmer</option>

            <?php foreach ($farmers as $f): ?>

                <option value="<?= $f['farmer_id']; ?>">

                    <?= htmlspecialchars($f['name']); ?>

                </option>

            <?php endforeach; ?>

        </select>

        <br><br>

        <label>Maize Supply</label>

        <select name="maize_id" required>

            <option value="">Select Supply</option>

            <?php foreach ($maize as $m): ?>

                <option value="<?= $m['maize_id']; ?>">

                    Maize <?= $m['maize_id']; ?> -
                    <?= $m['quantity']; ?> KG

                </option>

            <?php endforeach; ?>

        </select>

        <br><br>

        <label>Approved Order</label>

        <select name="order_id" required>

            <option value="">Select Order</option>

            <?php foreach ($orders as $o): ?>

                <option value="<?= $o['order_id']; ?>">

                    Order #<?= $o['order_id']; ?>
                    (<?= $o['required_quantity']; ?> KG)

                </option>

            <?php endforeach; ?>

        </select>

        <br><br>

        <button type="submit" name="save" class="btn btn-success">

            Save Payment

        </button>

    </form>

</div>