<?php
// admin/manage_orders.php
$pageTitle = 'Manage Orders';
require_once '../config/db.php';
require_once '../includes/header.php';
checkRole('admin');

$msg = '';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $order_id = $_GET['id'];
    $action = $_GET['action'];

    try {
        if ($action === 'approve') {

            $pdo->beginTransaction();

            // 1. Get Order
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                throw new Exception("Order not found.");
            }

            if ($order['status'] == 'approved') {
                throw new Exception("Order already approved.");
            }

            // 2. Check Stock
            $stmt = $pdo->prepare("
        SELECT stock_id, quantity
        FROM stock
        WHERE variety_id = ? AND company_id = ?
    ");
            $stmt->execute([$order['variety_id'], $order['company_id']]);
            $stock = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$stock || $stock['quantity'] < $order['required_quantity']) {
                throw new Exception("Insufficient stock.");
            }

            // ==========================================
            // 3. FIFO Supply Allocation
            // ==========================================

            $remainingQty = $order['required_quantity'];

            $stmt = $pdo->prepare("
SELECT maize_id, quantity
FROM maize
WHERE variety_id = ?
ORDER BY supply_date ASC
");

            $stmt->execute([$order['variety_id']]);

            $maizeRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($maizeRecords as $m) {

                if ($remainingQty <= 0) {
                    break;
                }

                // කලින් assign කරපු quantity
                $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(quantity_used),0)
        FROM order_supply
        WHERE maize_id=?
    ");

                $stmt->execute([$m['maize_id']]);

                $used = $stmt->fetchColumn();

                $available = $m['quantity'] - $used;

                if ($available <= 0) {
                    continue;
                }

                $allocate = min($available, $remainingQty);

                $stmt = $pdo->prepare("
        INSERT INTO order_supply
        (order_id, maize_id, quantity_used)
        VALUES (?,?,?)
    ");

                $stmt->execute([
                    $order_id,
                    $m['maize_id'],
                    $allocate
                ]);

                $remainingQty -= $allocate;
            }


            // ==========================================
            // 4. Update Stock
            // ==========================================

            $stmt = $pdo->prepare("
        UPDATE stock
        SET quantity = quantity - ?
        WHERE stock_id = ?
    ");

            $stmt->execute([
                $order['required_quantity'],
                $stock['stock_id']
            ]);

            // ==========================================
            // 5. Approve Order
            // ==========================================

            $stmt = $pdo->prepare("
        UPDATE orders
        SET status='approved',
            stock_id=?
        WHERE order_id=?
    ");

            $stmt->execute([
                $stock['stock_id'],
                $order_id
            ]);

            // ==========================================
            // 6. Generate Payments and Calculate Bonuses
            // ==========================================

            // Fetch allocations for this order
            $stmtAllocs = $pdo->prepare("
        SELECT os.maize_id, os.quantity_used, m.farmer_id, m.price_per_kg, l.land_size
        FROM order_supply os
        JOIN maize m ON os.maize_id = m.maize_id
        JOIN land l ON m.land_id = l.land_id
        WHERE os.order_id = ?
    ");
            $stmtAllocs->execute([$order_id]);
            $allocations = $stmtAllocs->fetchAll(PDO::FETCH_ASSOC);

            // Group allocations by farmer_id
            $farmerGroups = [];
            foreach ($allocations as $alloc) {
                $fid = $alloc['farmer_id'];
                if (!isset($farmerGroups[$fid])) {
                    $farmerGroups[$fid] = [
                        'total_quantity_used' => 0.0,
                        'allocs' => [],
                        'first_maize_id' => $alloc['maize_id']
                    ];
                }
                $farmerGroups[$fid]['total_quantity_used'] += (float) $alloc['quantity_used'];
                $farmerGroups[$fid]['allocs'][] = $alloc;
            }

            $is_large_order = ((float) $order['required_quantity'] >= 30000);

            // Calculate details and insert/update payments
            foreach ($farmerGroups as $fid => $group) {
                $basic_amount = 0.0;
                $bonus_amount = 0.0;

                foreach ($group['allocs'] as $alloc) {
                    $alloc_basic = (float) $alloc['quantity_used'] * (float) $alloc['price_per_kg'];
                    $basic_amount += $alloc_basic;

                    // Check if land size >= 4, order is Large, and farmer total allocated quantity >= 10,000
                    if ($is_large_order && $group['total_quantity_used'] >= 10000 && (float) $alloc['land_size'] >= 4) {
                        $bonus_amount += $alloc_basic * 0.02;
                    }
                }
                $total_paid = $basic_amount + $bonus_amount;
                $first_maize_id = $group['first_maize_id'];

                // Prevent duplicate payments using farmer_id + order_id
                $stmtCheck = $pdo->prepare("SELECT payment_id FROM payment WHERE farmer_id = ? AND order_id = ?");
                $stmtCheck->execute([$fid, $order_id]);
                $existing_payment = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                if ($existing_payment) {
                    // Update existing payment record to prevent duplicate
                    $stmtUpdateP = $pdo->prepare("
                UPDATE payment
                SET maize_id = ?,
                    basic_amount = ?,
                    bonus_amount = ?,
                    total_paid = ?,
                    payment_date = NOW()
                WHERE payment_id = ?
            ");
                    $stmtUpdateP->execute([
                        $first_maize_id,
                        $basic_amount,
                        $bonus_amount,
                        $total_paid,
                        $existing_payment['payment_id']
                    ]);
                } else {
                    // Insert new payment record
                    $stmtInsertP = $pdo->prepare("
                INSERT INTO payment (maize_id, farmer_id, order_id, basic_amount, bonus_amount, total_paid, payment_date)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
                    $stmtInsertP->execute([
                        $first_maize_id,
                        $fid,
                        $order_id,
                        $basic_amount,
                        $bonus_amount,
                        $total_paid
                    ]);
                }
            }

            $pdo->commit();

            $msg = "Order approved successfully.";

        } elseif ($action === 'reject') {

            $stmt = $pdo->prepare("
                UPDATE orders
                SET status = 'rejected'
                WHERE order_id = ?
            ");
            $stmt->execute([$order_id]);

            $msg = "Order rejected.";
        }

    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $msg = "Error: " . $e->getMessage();

    } catch (PDOException $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $msg = "Database Error: " . $e->getMessage();
    } catch (PDOException $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $msg = "Database Error: " . $e->getMessage();
    }

}

?>

<div class="table-container">
    <h3>All Orders</h3>
    <?php if ($msg): ?>
        <div class="status status-approved">
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Buyer</th>
                <th>Variety</th>
                <th>Qty</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stmt = $pdo->query("SELECT o.*, b.buyer_name, v.type FROM orders o JOIN buyer b ON o.buyer_id = b.buyer_id JOIN variety v ON o.variety_id = v.variety_id ORDER BY o.order_date DESC");
            while ($row = $stmt->fetch()):
                ?>
                <tr>
                    <td>
                        <?php echo $row['order_id']; ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($row['buyer_name']); ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($row['type']); ?>
                    </td>
                    <td>
                        <?php echo $row['required_quantity']; ?> KG
                    </td>
                    <td><span class="status status-<?php echo $row['status']; ?>">
                            <?php echo ucfirst($row['status']); ?>
                        </span></td>
                    <td>
                        <?php if ($row['status'] === 'pending'): ?>
                            <a href="?action=approve&id=<?php echo $row['order_id']; ?>" class="btn btn-primary">Approve</a>
                            <a href="?action=reject&id=<?php echo $row['order_id']; ?>" class="btn"
                                style="background:#ef5350; color:white;">Reject</a>
                            <a href="order_details.php?id=<?php echo $row['order_id']; ?>" class="btn"
                                style="background:#2196F3;color:white;">Details</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>