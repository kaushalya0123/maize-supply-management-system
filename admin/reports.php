<?php
// admin/reports.php
$pageTitle = 'System Reports';
require_once '../config/db.php';
require_once '../includes/header.php';
checkRole('admin');
?>

<div class="dashboard-cards">
    <div class="card">
        <h3>Total Revenue (Orders)</h3>
        <div class="value">
            <?php
            echo "LKR " . number_format($pdo->query("SELECT SUM(total_amount) FROM orders WHERE status = 'approved'")->fetchColumn() ?? 0, 2);
            ?>
        </div>
    </div>
    <div class="card" style="border-left-color: #fbc02d;">
        <h3>Total Farmer Payments</h3>
        <div class="value">
            <?php
            echo "LKR " . number_format($pdo->query("SELECT SUM(total_paid) FROM payment")->fetchColumn() ?? 0, 2);
            ?>
        </div>
    </div>

    <div class="card" style="border-left-color: #ef5350;">
        <h3>Total Bonuses Paid</h3>
        <div class="value">
            <?php
            echo "LKR " . number_format($pdo->query("SELECT SUM(bonus_amount) FROM payment")->fetchColumn() ?? 0, 2);
            ?>
        </div>
    </div>
</div>

<a href="payment_report.php" class="btn btn-primary">
    <i class="fas fa-money-bill-wave"></i>
    View Payment Report
</a>


<div class="table-container">
    <h3>Stock report by Variety</h3>
    <table>
        <thead>
            <tr>
                <th>Variety</th>
                <th>Quantity in Stock</th>
                <th>Total Value (Est.)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stmt = $pdo->query("SELECT v.type, s.quantity, (s.quantity * v.price_per_kg) as est_value 
                                FROM stock s JOIN variety v ON s.variety_id = v.variety_id");
            while ($row = $stmt->fetch()):
                ?>
                <tr>
                    <td>
                        <?php echo htmlspecialchars($row['type']); ?>
                    </td>
                    <td>
                        <?php echo number_format($row['quantity']); ?> KG
                    </td>
                    <td>LKR
                        <?php echo number_format($row['est_value'], 2); ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>