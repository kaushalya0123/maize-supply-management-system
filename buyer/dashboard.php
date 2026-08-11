<?php
$pageTitle = "Buyer Dashboard";

require_once "../config/db.php";
require_once "../includes/header.php";

checkRole("buyer");

$buyerId = $_SESSION['buyer_id'];

// =====================
// My Orders
// =====================
$stmt = $pdo->prepare("
SELECT COUNT(*)
FROM orders
WHERE buyer_id = ?
");
$stmt->execute([$buyerId]);
$totalOrders = $stmt->fetchColumn();

// =====================
// Available Varieties
// =====================
$stmt = $pdo->query("
SELECT COUNT(DISTINCT variety_id)
FROM stock
WHERE quantity > 0
");
$totalVarieties = $stmt->fetchColumn();

// =====================
// Total Ordered Quantity
// =====================
$stmt = $pdo->prepare("
SELECT COALESCE(SUM(required_quantity),0)
FROM orders
WHERE buyer_id = ?
");
$stmt->execute([$buyerId]);
$totalQty = $stmt->fetchColumn();

// =====================
// Total Amount
// =====================
$stmt = $pdo->prepare("
SELECT COALESCE(SUM(total_amount),0)
FROM orders
WHERE buyer_id = ?
");
$stmt->execute([$buyerId]);
$totalAmount = $stmt->fetchColumn();
?>

<style>

.dashboard-title{
    margin-bottom:25px;
    font-size:28px;
    font-weight:700;
}

.stats-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(230px,1fr));
    gap:20px;
    margin-bottom:35px;
}

.stat-card{
    background:#fff;
    border-radius:18px;
    padding:25px;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
    transition:.3s;
}

.stat-card:hover{
    transform:translateY(-5px);
}

.stat-icon{
    width:65px;
    height:65px;
    border-radius:15px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:28px;
    color:#fff;
    margin-bottom:15px;
}

.blue{background:#2196f3;}
.green{background:#2e7d32;}
.orange{background:#fb8c00;}
.purple{background:#7b1fa2;}

.stat-title{
    color:#666;
    font-size:14px;
    margin-bottom:8px;
}

.stat-value{
    font-size:28px;
    font-weight:bold;
    color:#222;
}

.quick-title{
    margin-bottom:20px;
}

.quick-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
    gap:20px;
}

.quick-card{
    background:linear-gradient(135deg,#1b5e20,#2e7d32);
    color:#fff;
    text-decoration:none;
    border-radius:18px;
    padding:30px;
    text-align:center;
    transition:.3s;
    box-shadow:0 10px 25px rgba(0,0,0,.12);
}

.quick-card:hover{
    transform:translateY(-6px);
    color:#fff;
}

.quick-card i{
    font-size:42px;
    margin-bottom:15px;
}

.quick-card h3{
    margin-bottom:10px;
}

.quick-card p{
    margin:0;
    opacity:.9;
}

</style>

<h2 class="dashboard-title">
Buyer Dashboard
</h2>

<div class="stats-grid">

<div class="stat-card">
<div class="stat-icon blue">
<i class="fas fa-cart-shopping"></i>
</div>
<div class="stat-title">My Orders</div>
<div class="stat-value"><?= number_format($totalOrders) ?></div>
</div>

<div class="stat-card">
<div class="stat-icon green">
<i class="fas fa-seedling"></i>
</div>
<div class="stat-title">Available Varieties</div>
<div class="stat-value"><?= number_format($totalVarieties) ?></div>
</div>

<div class="stat-card">
<div class="stat-icon orange">
<i class="fas fa-weight-hanging"></i>
</div>
<div class="stat-title">Total Ordered (KG)</div>
<div class="stat-value"><?= number_format($totalQty) ?></div>
</div>

<div class="stat-card">
<div class="stat-icon purple">
<i class="fas fa-money-bill-wave"></i>
</div>
<div class="stat-title">Total Spent</div>
<div class="stat-value">
LKR <?= number_format($totalAmount,2) ?>
</div>
</div>

</div>

<h3 class="quick-title">
Quick Actions
</h3>

<div class="quick-grid">

<a href="stock.php" class="quick-card">
<i class="fas fa-warehouse"></i>
<h3>Available Stock</h3>
<p>Browse available maize stock.</p>
</a>

<a href="my_orders.php" class="quick-card">
<i class="fas fa-clipboard-list"></i>
<h3>My Orders</h3>
<p>View all your orders.</p>
</a>

<a href="../logout.php" class="quick-card">
<i class="fas fa-right-from-bracket"></i>
<h3>Logout</h3>
<p>Sign out from the system.</p>
</a>

</div>

<?php require_once "../includes/footer.php"; ?>