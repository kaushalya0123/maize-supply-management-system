<?php
$pageTitle = "Payment Receipt";

require_once '../config/db.php';
require_once '../includes/auth.php';   // header.php වෙනුවට auth.php

checkLogin();
checkRole('admin');

if (!isset($_GET['id'])) {
    die("Invalid Payment");
}

$payment_id = $_GET['id'];

$stmt = $pdo->prepare("
SELECT
    p.*,
    f.name
FROM payment p
LEFT JOIN farmer f ON p.farmer_id = f.farmer_id
WHERE p.payment_id = ?
");

$stmt->execute([$payment_id]);

$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("Payment Not Found");
}
?>
<!DOCTYPE html>
<html>

<head>

<title>Payment Receipt</title>

<style>

body{
    font-family:Arial;
    padding:40px;
}

.receipt{
    width:700px;
    margin:auto;
    border:2px solid #333;
    padding:30px;
}

table{
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
}

td{
    padding:10px;
    border:1px solid #ddd;
}

h2{
    text-align:center;
}

</style>

</head>

<body>

<div class="receipt">

<div style="text-align:center;">
    <img src="../image/logo.png" width="90">
    <h2>ABC Maize Company</h2>
    <h3>Farmer Payment Receipt</h3>
</div>

<table>

<tr>
<td><strong>Receipt No</strong></td>
<td>PAY-<?= str_pad($row['payment_id'],5,'0',STR_PAD_LEFT); ?></td>
</tr>

<tr>
<td><strong>Farmer</strong></td>
<td><?= htmlspecialchars($row['name']); ?></td>
</tr>

<tr>
<td><strong>Order ID</strong></td>
<td><?= $row['order_id'] ?? '-'; ?></td>
</tr>

<tr>
<td><strong>Basic Amount</strong></td>
<td>Rs. <?= number_format($row['basic_amount'],2); ?></td>
</tr>

<tr>
<td><strong>Bonus</strong></td>
<td>Rs. <?= number_format($row['bonus_amount'],2); ?></td>
</tr>

<tr>
<td><strong>Total Paid</strong></td>
<td><strong>Rs. <?= number_format($row['total_paid'],2); ?></strong></td>
</tr>

<tr>
<td><strong>Payment Date</strong></td>
<td><?= $row['payment_date']; ?></td>
</tr>

</table>

<br><br><br>

<div style="text-align:right;">
..................................<br>
Authorized Signature
</div>

</div>

<script>
window.print();
</script>

</body>
</html>