<?php
// admin/manage_stock.php
$pageTitle = 'Manage Inventory Stock';
require_once '../config/db.php';
require_once '../includes/header.php';
checkRole('admin');

// stock වගුව සහ variety වගුව පමණක් භාවිතා කරයි
$sql = "SELECT MIN(s.stock_id) as stock_id, v.type, SUM(s.quantity) as quantity, MAX(s.last_update) as last_update 
        FROM stock s 
        JOIN variety v ON s.variety_id = v.variety_id 
        GROUP BY v.type
        ORDER BY stock_id DESC";

$stmt = $pdo->query($sql);
$stocks = $stmt->fetchAll();
?>

<div class="table-container">
    <h3><i class="fas fa-boxes-stacked"></i> Current Stock Inventory</h3>
    <table>
        <thead>
            <tr>
                <th>Stock ID</th>
                <th>Variety Type</th>
                <th>Quantity (KG)</th>
                <th>Last Update</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1;
            foreach ($stocks as $s): ?>
                <tr>
                    <td>
                        <?php echo $i++; ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($s['type']); ?>
                    </td>
                    <td>
                        <?php echo number_format($s['quantity']); ?> KG
                    </td>
                    <td>
                        <?php echo date('Y-m-d H:i', strtotime($s['last_update'])); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>