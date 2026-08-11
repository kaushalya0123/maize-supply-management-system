<?php
// farmer/view_bill.php
$pageTitle = 'Supply Invoice / Bill';
require_once '../config/db.php';
require_once '../includes/header.php';
checkRole('farmer');

$maizeId = $_GET['id'] ?? 0;

// 1. DYNAMIC IDENTITY LOOKUP (Link to logged-in user)
$username = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT farmer_id FROM farmer WHERE user_id = (SELECT id FROM users WHERE username = ?)");
$stmt->execute([$username]);
$farmer = $stmt->fetch();
$farmerId = $farmer ? $farmer['farmer_id'] : 0;

// 2. FETCH SUPPLY & PAYMENT DETAILS
$stmt = $pdo->prepare("SELECT m.*, l.land_name, v.type 
                      FROM maize m 
                      JOIN land l ON m.land_id = l.land_id 
                      JOIN variety v ON m.variety_id = v.variety_id 
                      WHERE m.maize_id = ? AND m.farmer_id = ?");
$stmt->execute([$maizeId, $farmerId]);
$data = $stmt->fetch();

if (!$data) {
    echo "<div class='table-container'><h3>Error: Bill not found or access denied.</h3></div>";
    require_once '../includes/footer.php';
    exit;
}

// Fetch Payment Details if exist
$stmt = $pdo->prepare("SELECT * FROM payment WHERE farmer_id = ?"); // Simplified for now
$stmt->execute([$farmerId]);
$payment = $stmt->fetch();
?>

<div class="table-container"
    style="max-width: 800px; margin: 0 auto; padding: 3rem; background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); color: #2d3748;">

    <div
        style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #edf2f7; padding-bottom: 2rem; margin-bottom: 2rem;">
        <div>
            <h1 style="color: #2e7d32; margin: 0; font-size: 1.8rem;"><i class="fas fa-seedling"></i> MAIZE SYSTEM</h1>
            <p style="color: #718096; margin-top: 5px;">Agricultural Supply Invoice</p>
        </div>
        <div style="text-align: right;">
            <h2 style="margin: 0; font-size: 1.2rem; color: #1a202c;">Bill #MS-
                <?php echo str_pad($data['maize_id'], 5, '0', STR_PAD_LEFT); ?>
            </h2>
            <p style="color: #718096; margin-top: 5px;">Date:
                <?php echo date('F j, Y'); ?>
            </p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 3rem;">
        <div>
            <h4
                style="color: #a0aec0; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; margin-bottom: 10px;">
                Farmer Details</h4>
            <p style="margin: 0; font-weight: 700; font-size: 1.1rem;">
                <?php echo htmlspecialchars($_SESSION['username']); ?>
            </p>
            <p style="margin: 5px 0; color: #4a5568;">Farmer ID: #
                <?php echo $farmerId; ?>
            </p>
        </div>
        <div>
            <h4
                style="color: #a0aec0; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; margin-bottom: 10px;">
                Land Details</h4>
            <p style="margin: 0; font-weight: 700;">
                <?php echo htmlspecialchars($data['land_name']); ?>
            </p>
            <p style="margin: 5px 0; color: #4a5568;">Location: Registered Plot</p>
        </div>
    </div>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 3rem;">
        <thead>
            <tr style="background: #f7fafc;">
                <th style="padding: 15px; text-align: left; border-bottom: 1px solid #edf2f7;">Description</th>
                <th style="padding: 15px; text-align: right; border-bottom: 1px solid #edf2f7;">Quantity</th>
                <th style="padding: 15px; text-align: right; border-bottom: 1px solid #edf2f7;">Base Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 20px 15px; border-bottom: 1px solid #edf2f7;">
                    <strong>Maize Supply (
                        <?php echo htmlspecialchars($data['type']); ?>)
                    </strong><br>
                    <small style="color: #718096;">Standard agricultural variety</small>
                </td>
                <td style="padding: 20px 15px; text-align: right; border-bottom: 1px solid #edf2f7;">
                    <?php echo number_format($data['quantity']); ?> KG
                </td>
                <td style="padding: 20px 15px; text-align: right; border-bottom: 1px solid #edf2f7;">
                    LKR
                    <?php echo number_format($data['total_ammount'], 2); ?>
                </td>
            </tr>
        </tbody>
    </table>

    <div style="width: 300px; margin-left: auto;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
            <span style="color: #718096;">Subtotal</span>
            <span style="font-weight: 600;">LKR
                <?php echo number_format($data['total_ammount'], 2); ?>
            </span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; color: #2e7d32;">
            <span>Bonus Applied</span>
            <span style="font-weight: 600;">+ LKR 0.00</span>
        </div>
        <div
            style="display: flex; justify-content: space-between; padding-top: 15px; border-top: 2px solid #edf2f7; margin-top: 15px;">
            <span style="font-weight: 700; font-size: 1.2rem;">Total Payment</span>
            <span style="font-weight: 800; font-size: 1.2rem; color: #2e7d32;">LKR
                <?php echo number_format($data['total_ammount'], 2); ?>
            </span>
        </div>
    </div>

    <div
        style="margin-top: 4rem; text-align: center; padding: 20px; border-top: 1px dashed #e2e8f0; color: #a0aec0; font-size: 0.85rem;">
        <p>This is a computer-generated document. No signature required.</p>
        <div style="margin-top: 20px;">
            <button onclick="window.print()" class="btn btn-primary" style="background: #4a5568;"><i
                    class="fas fa-print"></i> Print Bill</button>
            <a href="supply_history.php" class="btn" style="margin-left: 10px;"><i class="fas fa-arrow-left"></i> Back
                to History</a>
        </div>
    </div>

</div>

<?php require_once '../includes/footer.php'; ?>