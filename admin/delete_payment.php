<?php
$pageTitle = "Delete Payment";

require_once "../config/db.php";
require_once "../includes/header.php";

checkRole("admin");

if (!isset($_GET['id'])) {
    header("Location: manage_payments.php");
    exit;
}

$payment_id = (int)$_GET['id'];

// Check payment exists
$stmt = $pdo->prepare("
SELECT payment_id
FROM payment
WHERE payment_id=?
");
$stmt->execute([$payment_id]);

if(!$stmt->fetch()){
    echo "<script>
    alert('Payment not found.');
    window.location='manage_payments.php';
    </script>";
    exit;
}

// Delete payment
$stmt = $pdo->prepare("
DELETE FROM payment
WHERE payment_id=?
");

$stmt->execute([$payment_id]);

echo "<script>
alert('Payment deleted successfully.');
window.location='manage_payments.php';
</script>";
?>

<?php require_once "../includes/footer.php"; ?>