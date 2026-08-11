<?php
$pageTitle='Farmer Dashboard';
require_once '../config/db.php';
require_once '../includes/header.php';
checkRole('farmer');

$farmerId=$_SESSION['farmer_id'];

// totals
$stmt=$pdo->prepare("SELECT COALESCE(SUM(os.quantity_used),0)
FROM order_supply os
JOIN maize m ON os.maize_id=m.maize_id
JOIN land l ON m.land_id=l.land_id
WHERE l.farmer_id=?");
$stmt->execute([$farmerId]);
$totalKg=$stmt->fetchColumn();

$stmt=$pdo->prepare("SELECT COALESCE(SUM(total_paid),0),
COALESCE(SUM(bonus_amount),0),COUNT(*) FROM payment WHERE farmer_id=?");
$stmt->execute([$farmerId]);
list($totalPaid,$totalBonus,$totalOrders)=$stmt->fetch(PDO::FETCH_NUM);
?>

<style>
.dashboard-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;margin-bottom:30px}
.stat-card{background:#fff;border-radius:16px;padding:25px;box-shadow:0 8px 20px rgba(0,0,0,.08);text-align:center}
.stat-card i{font-size:40px;color:#2e7d32;margin-bottom:10px}
.stat-card h4{margin:8px 0;color:#666}
.stat-card h2{margin:0;color:#1b5e20}
.quick-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px}
.quick-card{display:block;text-decoration:none;background:linear-gradient(135deg,#2e7d32,#388e3c);color:#fff;border-radius:18px;padding:28px;text-align:center;transition:.25s}
.quick-card:hover{transform:translateY(-5px)}
.quick-card i{font-size:42px;margin-bottom:12px}
.quick-card h3{margin:8px 0}
.quick-card p{opacity:.9;margin:0}
</style>

<h2 style="margin-bottom:25px">Farmer Dashboard</h2>

<div class="dashboard-grid">
<div class="stat-card">
<i class="fas fa-wheat-awn"></i>
<h4>Total Supplied</h4>
<h2><?=number_format($totalKg)?> KG</h2>
</div>

<div class="stat-card">
<i class="fas fa-wallet"></i>
<h4>Payments Earned</h4>
<h2>LKR <?=number_format($totalPaid,2)?></h2>
</div>

<div class="stat-card">
<i class="fas fa-gift"></i>
<h4>Total Bonus</h4>
<h2>LKR <?=number_format($totalBonus,2)?></h2>
</div>

<div class="stat-card">
<i class="fas fa-file-invoice"></i>
<h4>Total Payments</h4>
<h2><?=number_format($totalOrders)?></h2>
</div>
</div>

<h3 style="margin:30px 0 20px">Quick Actions</h3>

<div class="quick-grid">
<a href="supply_history.php" class="quick-card">
<i class="fas fa-history"></i>
<h3>Supply History</h3>
<p>View all supplied maize records.</p>
</a>

<a href="payments.php" class="quick-card">
<i class="fas fa-money-bill-wave"></i>
<h3>Payments</h3>
<p>Check your payment details.</p>
</a>

<a href="bonus.php" class="quick-card">
<i class="fas fa-award"></i>
<h3>My Bonus</h3>
<p>View all bonus payments.</p>
</a>
</div>

<?php require_once '../includes/footer.php'; ?>
