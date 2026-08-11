<?php
// farmer/payments.php

$pageTitle = 'Payment History';
require_once '../config/db.php';
require_once '../includes/header.php';

checkRole('farmer');

$farmerId = $_SESSION['farmer_id'];
?>

<div class="table-container">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <h3>
            <i class="fas fa-coins"></i>
            My Payment Records
        </h3>
    </div>

    <table>

        <thead>

            <tr>

                <th>Date</th>
                <th>Order ID</th>
                <th>Basic Payment</th>
                <th>Bonus (2%)</th>
                <th>Total Payment</th>

            </tr>

        </thead>

        <tbody>

        <?php

        $stmt = $pdo->prepare("
        SELECT *
        FROM payment
        WHERE farmer_id=?
        ORDER BY payment_date DESC
        ");

        $stmt->execute([$farmerId]);

        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){

        ?>

            <tr>

                <td><?= $row['payment_date']; ?></td>

                <td><?= $row['order_id']; ?></td>

                <td>
                    LKR <?= number_format($row['basic_amount'],2); ?>
                </td>

                <td style="color:green;font-weight:bold;">
                    LKR <?= number_format($row['bonus_amount'],2); ?>
                </td>

                <td>
                    <strong>
                        LKR <?= number_format($row['total_paid'],2); ?>
                    </strong>
                </td>

            </tr>

        <?php } ?>

        <?php if($stmt->rowCount()==0){ ?>

            <tr>

                <td colspan="5" style="text-align:center;padding:30px;">

                    No payment records found.

                </td>

            </tr>

        <?php } ?>

        </tbody>

    </table>

</div>

<?php require_once '../includes/footer.php'; ?>