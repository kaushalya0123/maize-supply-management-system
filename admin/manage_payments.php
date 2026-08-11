<?php
$pageTitle = 'Manage Payments';
require_once '../config/db.php';
require_once '../includes/header.php';
checkRole('admin');

// Payment List
$paymentStmt = $pdo->query("
SELECT
    p.*,
    f.name,
    m.quantity,
    l.land_size,
    o.required_quantity

FROM payment p

LEFT JOIN farmer f 
    ON p.farmer_id=f.farmer_id

LEFT JOIN maize m 
    ON p.maize_id=m.maize_id

LEFT JOIN land l 
    ON m.land_id=l.land_id

LEFT JOIN orders o 
    ON p.order_id=o.order_id

ORDER BY p.payment_date DESC
");
// Farmer Dropdown
$farmers = $pdo->query("
SELECT farmer_id,name
FROM farmer
ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

// Approved Orders
$orders = $pdo->query("
SELECT order_id,required_quantity
FROM orders
WHERE status='approved'
ORDER BY order_id DESC
")->fetchAll(PDO::FETCH_ASSOC);


// Generate Payment
if (isset($_POST['generate'])) {

    $farmer_id = $_POST['farmer_id'];
    $order_id = $_POST['order_id'];

    // Get allocations for this farmer and order
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

        // Group / calculate basic and bonus
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
        $check = $pdo->prepare("
        SELECT payment_id
        FROM payment
        WHERE farmer_id=? AND order_id=?
        ");

        $check->execute([$farmer_id, $order_id]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {

            $insert = $pdo->prepare("
            INSERT INTO payment
            (maize_id,farmer_id,order_id,basic_amount,bonus_amount,total_paid,payment_date)
            VALUES(?,?,?,?,?,?,CURDATE())
            ");

            $insert->execute([
                $first_maize_id,
                $farmer_id,
                $order_id,
                $basic,
                $bonus,
                $total
            ]);

            echo "<div class='status status-approved'>Payment Generated Successfully.</div>";

        } else {
            // Update the existing record instead of creating duplicate
            $update = $pdo->prepare("
            UPDATE payment
            SET maize_id=?,
                basic_amount=?,
                bonus_amount=?,
                total_paid=?
            WHERE payment_id=?
            ");
            $update->execute([
                $first_maize_id,
                $basic,
                $bonus,
                $total,
                $existing['payment_id']
            ]);

            echo "<div class='status status-approved'>Payment Updated Successfully.</div>";

        }

    } else {
        echo "<div class='status status-pending'>No allocations found in order_supply for this farmer and order. Payment could not be generated.</div>";
    }

    // Reload payment list
    $paymentStmt = $pdo->query("
    SELECT
        p.*,
        f.name,
        m.quantity,
        l.land_size,
        o.required_quantity
    FROM payment p
    LEFT JOIN farmer f ON p.farmer_id=f.farmer_id
    LEFT JOIN maize m ON p.maize_id=m.maize_id
    LEFT JOIN land l ON m.land_id=l.land_id
    LEFT JOIN orders o ON p.order_id=o.order_id
    ORDER BY p.payment_date DESC
    ");
}
?>
<div class="table-container">

    <h2>
        <i class="fas fa-money-bill-wave"></i>
        Payment Management
    </h2>

    <form method="post" style="display:flex;gap:15px;margin-bottom:20px;align-items:end;flex-wrap:wrap;">

        <div>
            <label><strong>Farmer</strong></label><br>
            <select name="farmer_id" required class="form-control">

                <option value="">Select Farmer</option>

                <?php foreach ($farmers as $f): ?>

                    <option value="<?= $f['farmer_id']; ?>">

                        <?= htmlspecialchars($f['name']); ?>

                    </option>

                <?php endforeach; ?>

            </select>
        </div>

        <div>

            <label><strong>Approved Order</strong></label><br>

            <select name="order_id" required class="form-control">

                <option value="">Select Order</option>

                <?php foreach ($orders as $o): ?>

                    <option value="<?= $o['order_id']; ?>">

                        Order #<?= $o['order_id']; ?>
                        (<?= $o['required_quantity']; ?> KG)

                    </option>

                <?php endforeach; ?>

            </select>

        </div>

        <div>

            <button type="submit" name="generate" class="btn btn-success">

                <i class="fas fa-money-check-dollar"></i>

                Generate Payment

            </button>

        </div>

    </form>


    <table>

        <thead>

            <tr>

                <th>Payment ID</th>

                <th>Farmer</th>

                <th>Order ID</th>

                <th>Basic Amount</th>

                <th>Bonus</th>

                <th>Total Paid</th>

                <th>Date</th>

                <th>Action</th>

            </tr>

        </thead>

        <tbody>

            <?php while ($row = $paymentStmt->fetch(PDO::FETCH_ASSOC)): ?>

                <tr>

                    <td><?= $row['payment_id']; ?></td>

                    <td><?= htmlspecialchars($row['name']); ?></td>

                    <td><?= $row['order_id']; ?></td>

                    <td>Rs. <?= number_format($row['basic_amount'], 2); ?></td>

                    <td>

                        <?php
                        echo "Rs. " . number_format($row['bonus_amount'], 2);
                        ?>

                    </td>

                    <td>

                        <strong>

                            Rs. <?= number_format($row['total_paid'], 2); ?>

                        </strong>

                    </td>

                    <td><?= $row['payment_date']; ?></td>
                    <td>

                        <a href="edit_payment.php?id=<?= $row['payment_id']; ?>" class="btn btn-warning"
                            style="margin-right:5px;">
                            <i class="fas fa-edit"></i> Edit
                        </a>

                        <a href="delete_payment.php?id=<?= $row['payment_id']; ?>" class="btn btn-danger"
                            style="margin-right:5px;"
                            onclick="return confirm('Are you sure you want to delete this payment?');">
                            <i class="fas fa-trash"></i> Delete
                        </a>

                        <a href="payment_receipt.php?id=<?= $row['payment_id']; ?>" class="btn btn-primary">
                            <i class="fas fa-print"></i> Print
                        </a>

                    </td>
                </tr>

            <?php endwhile; ?>

        </tbody>

    </table>

</div>

<?php require_once '../includes/footer.php'; ?>