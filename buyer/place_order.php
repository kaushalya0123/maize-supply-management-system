<?php
// buyer/place_order.php
$pageTitle = 'Place Order';
require_once '../config/db.php';
require_once '../includes/header.php';
checkRole('buyer');

$variety_id = $_GET['variety_id'] ?? 0;
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $qty = $_POST['quantity'];
    $variety_id = $_POST['variety_id'];
    $buyer_id = $_SESSION['buyer_id'];

    // Get price
    $stmt = $pdo->prepare("SELECT price_per_kg FROM variety WHERE variety_id = ?");
    $stmt->execute([$variety_id]);
    $price = $stmt->fetchColumn();

    $total = $qty * $price;

    // Insert Order
    $stmt = $pdo->prepare("
        INSERT INTO orders
        (buyer_id, variety_id, required_quantity, price_per_kg, total_amount, company_id)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $buyer_id,
        $variety_id,
        $qty,
        $price,
        $total,
        1
    ]);

    $msg = "Order placed successfully! Waiting for Admin approval.";
}

// Get variety details
$stmt = $pdo->prepare("SELECT * FROM variety WHERE variety_id = ?");
$stmt->execute([$variety_id]);
$v = $stmt->fetch();
?>

<div class="table-container" style="max-width: 600px; margin: 0 auto;">
    <h3>Order Maize:
        <?php echo $v['type'] ?? 'Select Variety'; ?>
    </h3>
    <?php if ($msg): ?>
        <div class="status status-approved">
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>
    <form method="POST">
        <input type="hidden" name="variety_id" value="<?php echo $variety_id; ?>">
        <div class="form-group">
            <label style="color:#333;">Quantity (KG)</label>
            <input type="number" name="quantity" class="btn"
                style="width:100%; border:1px solid #ddd; background:white; color:#333;" required
                placeholder="Enter quantity to order">
        </div>
        <p style="margin-bottom:1rem; color:#666;">Price per KG: LKR
            <?php echo number_format($v['price_per_kg'], 2); ?>
        </p>
        <button type="submit" class="login-btn">Place Order</button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>