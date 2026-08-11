<?php
$pageTitle = "My Bonus";

require_once "../config/db.php";
require_once "../includes/header.php";

checkRole("farmer");

$farmer_id = $_SESSION['farmer_id'];

$stmt = $pdo->prepare("
SELECT
    p.payment_date,
    p.basic_amount,
    p.bonus_amount,
    p.total_paid,
    o.order_id,
    os.quantity_used
FROM payment p
JOIN orders o
    ON p.order_id = o.order_id
JOIN order_supply os
    ON o.order_id = os.order_id
WHERE p.farmer_id = ?
ORDER BY p.payment_date DESC
");

$stmt->execute([$farmer_id]);
?>

<div class="table-container">

    <h3>My Bonus</h3>

    <table>

        <thead>

            <tr>

                <th>Order ID</th>
                <th>Quantity Used</th>
                <th>Basic Payment</th>
                <th>Bonus (2%)</th>

            </tr>

        </thead>

        <tbody>

        <?php
        $totalBonus = 0;

        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){

            $totalBonus += $row['bonus_amount'];
        ?>

            <tr>

                <td><?= $row['order_id']; ?></td>

                <td><?= number_format($row['quantity_used']); ?> KG</td>

                <td>
                    LKR <?= number_format($row['basic_amount'],2); ?>
                </td>

                <td style="color:green;font-weight:bold;">
                    LKR <?= number_format($row['bonus_amount'],2); ?>
                </td>

            </tr>

        <?php } ?>

        <?php if($stmt->rowCount() == 0){ ?>

            <tr>

                <td colspan="6" style="text-align:center;padding:25px;">
                    No bonus records found.
                </td>

            </tr>

        <?php } ?>

        </tbody>

    </table>

    <h3 style="margin-top:20px;color:#2e7d32;">
        Total Bonus :
        LKR <?= number_format($totalBonus,2); ?>
    </h3>

</div>

<?php require_once "../includes/footer.php"; ?>