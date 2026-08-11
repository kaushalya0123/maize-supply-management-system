<?php
// farmer/profile.php
$pageTitle = 'My Profile';
require_once '../config/db.php';
require_once '../includes/header.php';
checkRole('farmer');
// 1. DYNAMIC IDENTITY LOOKUP
$username = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT farmer_id FROM farmer WHERE user_id = (SELECT id FROM users WHERE username = ?)");
$stmt->execute([$username]);
$farmer = $stmt->fetch();
$farmerId = $farmer ? $farmer['farmer_id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM farmer WHERE farmer_id = ?");
$stmt->execute([$farmerId]);
$f = $stmt->fetch();
?>
<div class="table-container" style="max-width: 600px; margin: 0 auto;">
    <h3>Personal Information</h3>
    <p><strong>Name:</strong>
        <?php echo htmlspecialchars($f['name']); ?>
    </p>
    <p><strong>NIC:</strong>
        <?php echo htmlspecialchars($f['nic']); ?>
    </p>
    <p><strong>Phone:</strong>
        <?php echo htmlspecialchars($f['phone']); ?>
    </p>
    <p><strong>Address:</strong>
        <?php echo htmlspecialchars($f['address']); ?>
    </p>
</div>
<?php require_once '../includes/footer.php'; ?>