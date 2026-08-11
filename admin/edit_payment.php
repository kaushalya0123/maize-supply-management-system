<?php
$pageTitle = "Edit Payment";

require_once "../config/db.php";
require_once "../includes/header.php";

checkRole("admin");

if (!isset($_GET['id'])) {
    header("Location: manage_payments.php");
    exit;
}

$payment_id = $_GET['id'];

// Load payment
$stmt = $pdo->prepare("
SELECT
    p.*,
    f.name
FROM payment p
JOIN farmer f ON p.farmer_id=f.farmer_id
WHERE p.payment_id=?
");

$stmt->execute([$payment_id]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    die("Payment not found.");
}

// Update
if(isset($_POST['update'])){

    $bonus = floatval($_POST['bonus_amount']);

    $total = $payment['basic_amount'] + $bonus;

    $stmt = $pdo->prepare("
    UPDATE payment
    SET
        bonus_amount=?,
        total_paid=?
    WHERE payment_id=?
    ");

    $stmt->execute([
        $bonus,
        $total,
        $payment_id
    ]);

    echo "<script>
    alert('Payment Updated Successfully');
    window.location='manage_payments.php';
    </script>";
}
?>

<div class="table-container">

<h2>Edit Payment</h2>

<form method="post">

<div class="form-group">

<label>Farmer</label>

<input
type="text"
value="<?= htmlspecialchars($payment['name']); ?>"
class="form-control"
readonly>

</div>

<br>

<div class="form-group">

<label>Basic Amount</label>

<input
type="text"
value="<?= number_format($payment['basic_amount'],2); ?>"
class="form-control"
readonly>

</div>

<br>

<div class="form-group">

<label>Bonus Amount</label>

<input
type="number"
step="0.02"
name="bonus_amount"
value="<?= $payment['bonus_amount']; ?>"
class="form-control"
required>

</div>

<br>

<button
type="submit"
name="update"
class="btn btn-success">

<i class="fas fa-save"></i>

Update Payment

</button>

<a
href="manage_payments.php"
class="btn btn-secondary">

Cancel

</a>

</form>

</div>

<?php require_once "../includes/footer.php"; ?>