<?php
// buyer/stock.php
require_once '../config/db.php';
require_once '../includes/header.php';
checkRole('buyer');

// Fetch available stock (මෙය dashboard එකේ තිබූ එම query එකමයි)
$stmt = $pdo->query("SELECT s.*, v.type, v.price_per_kg 
                     FROM stock s 
                     JOIN variety v ON s.variety_id = v.variety_id 
                     WHERE s.quantity > 0");
$stocks = $stmt->fetchAll();
?>

<div class="table-container">
    <h3 style="margin-bottom: 1.5rem;"><i class="fas fa-warehouse" style="color: #2e7d32; margin-right: 10px;"></i>
        All Available Stock</h3>
    <table>
        <thead>
            <tr>
                <th>Maize Variety</th>
                <th>Available Quantity (KG)</th>
                <th>Price per KG</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($stocks as $s): ?>
                <tr>
                    <td><?php echo htmlspecialchars($s['type']); ?></td>
                    <td><?php echo number_format($s['quantity']); ?> KG</td>
                    <td>LKR <?php echo number_format($s['price_per_kg'], 2); ?></td>
                    <td><a href="place_order.php?variety_id=<?php echo $s['variety_id']; ?>" class="btn btn-primary">Order Now</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($stocks)): ?>
                <tr>
                    <td colspan="4" style="text-align:center;">No stock currently available.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>