<?php
// admin/manage_buyers.php
$pageTitle = 'Manage Buyers';
require_once '../config/db.php';
require_once '../includes/header.php';
checkRole('admin');

$msg = '';
$error = '';

// Handle Delete Buyer
if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];
    try {
        $pdo->beginTransaction();

        // 1. Get user_id associated with buyer
        $stmt = $pdo->prepare("SELECT user_id FROM buyer WHERE buyer_id = ?");
        $stmt->execute([$deleteId]);
        $userId = $stmt->fetchColumn();

        // 2. Delete Buyer
        $stmt = $pdo->prepare("DELETE FROM buyer WHERE buyer_id = ?");
        $stmt->execute([$deleteId]);

        // 3. Delete User account
        if ($userId) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
        }

        $pdo->commit();
        $msg = "Buyer and association account deleted successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error deleting buyer: " . $e->getMessage();
    }
}

// Fetch Search Query
$search = $_GET['search'] ?? '';
$query = "SELECT b.*, c.company_name, u.username
          FROM buyer b
          LEFT JOIN company c ON b.company_id = c.company_id
          LEFT JOIN users u ON b.buyer_id = u.buyer_id";

if ($search) {
    $query .= " WHERE b.buyer_name LIKE :search OR c.company_name LIKE :search OR u.username LIKE :search";
}

$stmt = $pdo->prepare($query);
if ($search) {
    $stmt->execute(['search' => "%$search%"]);
} else {
    $stmt->execute();
}
$buyers = $stmt->fetchAll();
?>

<div class="top-bar" style="margin-bottom: 2rem; background: white; padding: 1rem 2rem; border-radius: 15px;">
    <h2 style="font-size: 1.5rem; color: #2e7d32; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-shopping-cart"></i> Buyer Management
    </h2>
    <form method="GET" class="search-box" style="width: 400px; margin-left: auto;">
        <i class="fas fa-search" style="color: #666;"></i>
        <input type="text" name="search" placeholder="Search buyers, companies, usernames..."
            value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" style="display:none;">Search</button>
    </form>
</div>

<?php if ($msg): ?>
    <div class="status status-approved" style="margin-bottom: 1.5rem;">
        <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
        <?php echo $msg; ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="status status-pending" style="margin-bottom: 1.5rem;">
        <i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="table-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h3 style="color: #4a5568; font-size: 1.1rem;">All Registered Buyers</h3>
        <span style="font-size: 0.85rem; color: #718096;">
            <?php echo count($buyers); ?> records found
        </span>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Buyer Name</th>
                <th>Company</th>
                <th>Username</th>
                <th style="text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($buyers as $b): ?>
                <tr>
                    <td>
                        <?php echo $b['buyer_id']; ?>
                    </td>
                    <td><strong style="color: #1a202c;">
                            <?php echo htmlspecialchars($b['buyer_name']); ?>
                        </strong></td>
                    <td><span
                            style="background: #e3f2fd; color: #0288d1; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 600;">
                            <?php echo htmlspecialchars($b['company_name'] ?? 'N/A'); ?>
                        </span></td>
                    <td>
                        <?php echo htmlspecialchars($b['username'] ?? 'N/A'); ?>
                    </td>
                    <td style="text-align: right;">
                        <a href="?delete_id=<?php echo $b['buyer_id']; ?>"
                            onclick="return confirm('Are you sure you want to delete this buyer? This will also remove their login access.')"
                            class="btn"
                            style="background: #fff5f5; color: #c53030; border: 1px solid #feb2b2; padding: 6px 12px; font-size: 0.8rem;">
                            <i class="fas fa-trash-can"></i> Delete
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($buyers)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 3rem; color: #718096;">
                        <i class="fas fa-folder-open"
                            style="font-size: 2.5rem; display: block; margin-bottom: 1rem; opacity: 0.3;"></i>
                        No buyers found matching your criteria.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>