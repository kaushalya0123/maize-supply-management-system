<?php
$pageTitle = "Payment Report";
require_once '../config/db.php';
require_once '../includes/header.php';
checkRole('admin');

// Summary Cards
$totalPayments = $pdo->query("SELECT COUNT(*) FROM payment")->fetchColumn();

$totalBonus = $pdo->query("SELECT SUM(bonus_amount) FROM payment")->fetchColumn() ?? 0;

$totalPaid = $pdo->query("SELECT SUM(total_paid) FROM payment")->fetchColumn() ?? 0;

$todayPaid = $pdo->query("
SELECT SUM(total_paid)
FROM payment
WHERE payment_date = CURDATE()
")->fetchColumn() ?? 0;
?>

<div style="text-align:right; margin-bottom:20px;">
    <button onclick="window.print()" class="btn btn-primary">
        <i class="fas fa-print"></i> Print Report
    </button>
</div>


<h2 style="margin-bottom:25px;">
    <i class="fas fa-money-bill-wave"></i>
    Payment Report
</h2>
<h1 style='color:navi blue;'>Payment Report Working</h1>
.
<div class="dashboard-cards">

    <div class="card">
        <div class="card-icon bg-blue">
            <i class="fas fa-money-check-dollar"></i>
        </div>
        <div class="card-content">
            <h3>Total Payments</h3>
            <div class="value"><?= $totalPayments; ?></div>
        </div>
    </div>

    <div class="card">
        <div class="card-icon bg-green">
            <i class="fas fa-coins"></i>
        </div>
        <div class="card-content">
            <h3>Total Bonus</h3>
            <div class="value">Rs. <?= number_format($totalBonus,2); ?></div>
        </div>
    </div>

    <div class="card">
        <div class="card-icon bg-yellow">
            <i class="fas fa-wallet"></i>
        </div>
        <div class="card-content">
            <h3>Total Paid</h3>
            <div class="value">Rs. <?= number_format($totalPaid,2); ?></div>
        </div>
    </div>

    <div class="card">
        <div class="card-icon bg-red">
            <i class="fas fa-calendar-day"></i>
        </div>
        <div class="card-content">
            <h3>Today's Payments</h3>
            <div class="value">Rs. <?= number_format($todayPaid,2); ?></div>
        </div>
    </div>

</div>
<?php

$stmt = $pdo->query("
SELECT
    p.*,
    f.name
FROM payment p
JOIN farmer f ON p.farmer_id = f.farmer_id
ORDER BY p.payment_date DESC
");

?>

<div class="table-container" style="margin-top:30px;">



<h3>
    <i class="fas fa-table"></i>
    Payment Details
</h3>

<table>

<thead>

<tr>

<th>Payment ID</th>

<th>Farmer</th>

<th>Order ID</th>

<th>Basic Amount</th>

<th>Bonus</th>

<th>Total Paid</th>

<th>Payment Date</th>



</tr>

</thead>

<tbody>

<?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>

<tr>

<td><?= $row['payment_id']; ?></td>

<td><?= htmlspecialchars($row['name']); ?></td>

<td><?= $row['order_id']; ?></td>

<td>Rs. <?= number_format($row['basic_amount'],2); ?></td>

<td>Rs. <?= number_format($row['bonus_amount'],2); ?></td>

<td>
    <strong>
        Rs. <?= number_format($row['total_paid'],2); ?>
    </strong>
</td>

<td><?= $row['payment_date']; ?></td>

<td>



</td>

</tr>

<?php endwhile; ?>

</tbody>

</table>

</div>

<?php require_once '../includes/footer.php'; ?>