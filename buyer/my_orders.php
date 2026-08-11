<?php
// buyer/my_orders.php
$pageTitle = 'My Orders';
require_once '../config/db.php';
require_once '../includes/header.php';
checkRole('buyer');
$buyerId = $_SESSION['buyer_id'];

?>
<div class="table-container">
    <h3>Order History</h3>
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Variety</th>
                <th>Quantity</th>
                <th>Total Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stmt = $pdo->prepare("SELECT o.*, v.type FROM orders o JOIN variety v ON o.variety_id = v.variety_id WHERE o.buyer_id = ? ORDER BY o.order_date DESC");
            $stmt->execute([$buyerId]);
            while ($row = $stmt->fetch()): ?>
                <tr>
                    <td>
                        <?php echo $row['order_id']; ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($row['type']); ?>
                    </td>
                    <td>
                        <?php echo $row['required_quantity']; ?> KG
                    </td>
                    <td>LKR
                        <?php echo number_format($row['total_amount'], 2); ?>
                    </td>
                    <td><span class="status status-<?php echo $row['status']; ?>">
                            <?php echo ucfirst($row['status']); ?>
                        </span></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php require_once '../includes/footer.php'; ?>