<?php
$pageTitle = "Order Details";

require_once "../config/db.php";
require_once "../includes/header.php";

checkRole("admin");

if (!isset($_GET['id'])) {
    die("Invalid Order");
}

$order_id = $_GET['id'];

/* ===========================
   ORDER DETAILS
=========================== */

$stmt = $pdo->prepare("
SELECT
o.*,
b.buyer_name,
c.company_name,
v.type
FROM orders o
JOIN buyer b ON o.buyer_id=b.buyer_id
JOIN company c ON o.company_id=c.company_id
JOIN variety v ON o.variety_id=v.variety_id
WHERE o.order_id=?
");

$stmt->execute([$order_id]);
$order=$stmt->fetch(PDO::FETCH_ASSOC);

if(!$order){
    die("Order not found.");
}

/* ===========================
   SUPPLY DETAILS
=========================== */


$stmt = $pdo->prepare("
SELECT
    os.quantity_used,
    m.maize_id,
    m.quantity,
    l.land_name,
    f.name

FROM order_supply os

JOIN maize m
    ON os.maize_id = m.maize_id

JOIN land l
    ON m.land_id = l.land_id

JOIN farmer f
    ON l.farmer_id = f.farmer_id

WHERE os.order_id = ?
");

$stmt->execute([$order_id]);
$supplies=$stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="table-container">

<h3>Order Details</h3>

<table>

<tr>
<th width="220">Order ID</th>
<td>#<?php echo $order['order_id']; ?></td>
</tr>

<tr>
<th>Buyer</th>
<td><?php echo $order['buyer_name']; ?></td>
</tr>

<tr>
<th>Company</th>
<td><?php echo $order['company_name']; ?></td>
</tr>

<tr>
<th>Variety</th>
<td><?php echo $order['type']; ?></td>
</tr>

<tr>
<th>Quantity</th>
<td><?php echo number_format($order['required_quantity']); ?> KG</td>
</tr>

<tr>
<th>Total</th>
<td>LKR <?php echo number_format($order['total_amount'],2); ?></td>
</tr>

<tr>
<th>Status</th>
<td><?php echo ucfirst($order['status']); ?></td>
</tr>

</table>

<br><br>

<h3>Supply Allocation (FIFO)</h3>

<table>

<thead>

<tr>

<th>Farmer</th>

<th>Land</th>

<th>Maize ID</th>

<th>Original Supply</th>

<th>Used For Order</th>

</tr>

</thead>

<tbody>

<?php foreach($supplies as $row){ ?>

<tr>

<td><?php echo htmlspecialchars($row['name']); ?></td>

<td><?php echo htmlspecialchars($row['land_name']); ?></td>

<td><?php echo $row['maize_id']; ?></td>

<td><?php echo number_format($row['quantity']); ?> KG</td>

<td><?php echo number_format($row['quantity_used']); ?> KG</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

<?php require_once "../includes/footer.php"; ?>