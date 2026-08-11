<?php
// admin/manage_farmers.php
$pageTitle = 'Manage Farmers';
require_once '../config/db.php';
require_once '../includes/header.php';
checkRole('admin');

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_farmer'])) {

$name = trim($_POST['name']);
$nic = strtoupper(trim($_POST['nic']));
$phone = trim($_POST['phone']);
$address = trim($_POST['address']);
$username = trim($_POST['username']);
$password = $_POST['password'];

// Name validation
if (!preg_match("/^[A-Za-z ]{3,100}$/", $name)) {
    $msg = "Invalid farmer name.";
}

// NIC validation
elseif (!preg_match("/^([0-9]{9}[VvXx]|[0-9]{12})$/", $nic)) {
    $msg = "Invalid NIC. Enter either 123456789V or 200012345678.";
}

// Phone validation
elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
    $msg = "Phone number must contain exactly 10 digits.";
}

// Username validation
elseif (!preg_match("/^[A-Za-z0-9_]{4,20}$/", $username)) {
    $msg = "Username must be 4-20 characters.";
}

// Password validation
elseif (strlen($password) < 6) {
    $msg = "Password must be at least 6 characters.";
}

else {

    try {

        $pdo->beginTransaction();

        // Check duplicate NIC
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM farmer WHERE nic=?");
        $stmt->execute([$nic]);

        if($stmt->fetchColumn() > 0){
            throw new Exception("NIC already exists.");
        }

        // Check duplicate Phone
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM farmer WHERE phone=?");
        $stmt->execute([$phone]);

        if($stmt->fetchColumn() > 0){
            throw new Exception("Phone number already exists.");
        }

        // Check duplicate Username
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username=?");
        $stmt->execute([$username]);

        if($stmt->fetchColumn() > 0){
            throw new Exception("Username already exists.");
        }

        $hashed_password = password_hash($password,PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
        INSERT INTO users(username,password,role)
        VALUES(?,?,'farmer')
        ");

        $stmt->execute([$username,$hashed_password]);

        $userId = $pdo->lastInsertId();

        $stmt = $pdo->prepare("
        INSERT INTO farmer(name,nic,phone,address,user_id)
        VALUES(?,?,?,?,?)
        ");

        $stmt->execute([$name,$nic,$phone,$address,$userId]);

        $pdo->commit();

        $msg = "Farmer registered successfully.";

    } catch(Exception $e){

        $pdo->rollBack();

        $msg = $e->getMessage();

    }

}

    $name = $_POST['name'];
    $nic = $_POST['nic'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $pdo->beginTransaction();

        // 1. Create User Account first to get the ID
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'farmer')");
        $stmt->execute([$username, $hashed_password]);
        $userId = $pdo->lastInsertId();

        // 2. Create Farmer linked to the User
        $stmt = $pdo->prepare("INSERT INTO farmer (name, nic, phone, address, user_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $nic, $phone, $address, $userId]);

        $pdo->commit();
        $msg = "Farmer and login account created successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $msg = "Error: " . $e->getMessage();
    }
}

// Handle Delete Farmer
if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM farmer WHERE farmer_id = ?");
        $stmt->execute([$deleteId]);
        $msg = "Farmer deleted successfully!";
    } catch (PDOException $e) {
        $msg = "Error deleting farmer: " . $e->getMessage();
    }
}
?>

<div class="table-container" style="max-width: 600px; margin: 0 auto 2rem auto;">
    <h3>Register New Farmer</h3>
    <?php if ($msg): ?>
        <div class="status status-approved">
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group"><label style="color:#333;">Full Name</label><input type="text" name="name" class="btn"
                style="width:100%; border:1px solid #ddd; background:white; color:#333;" required></div>
        <div class="form-group">
    <label style="color:#333;">NIC</label>

    <input
        type="text"
        name="nic"
        class="btn"
        maxlength="12"
        pattern="([0-9]{9}[VvXx]|[0-9]{12})"
        title="Enter NIC like 123456789V or 200012345678"
        style="width:100%; border:1px solid #ddd; background:white; color:#333;"
        required>
</div>
      <div class="form-group">
    <label style="color:#333;">Phone</label>

    <input
        type="text"
        name="phone"
        class="btn"
        maxlength="10"
        pattern="[0-9]{10}"
        title="Phone number must contain exactly 10 digits"
        style="width:100%; border:1px solid #ddd; background:white; color:#333;"
        required>
</div>
  
        <div class="form-group"><label style="color:#333;">Address</label><input type="text" name="address" class="btn"
                style="width:100%; border:1px solid #ddd; background:white; color:#333;" required></div>
        <div class="form-group"><label style="color:#333;">Username (for Login)</label><input type="text"
                name="username" class="btn" style="width:100%; border:1px solid #ddd; background:white; color:#333;"
                required></div>
        <div class="form-group"><label style="color:#333;">Password</label><input type="password" name="password"
                class="btn" style="width:100%; border:1px solid #ddd; background:white; color:#333;" required></div>
        <button type="submit" name="add_farmer" class="login-btn">Register Farmer</button>
    </form>
</div>

<div class="table-container">
    <h3>Existing Farmers</h3>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>NIC</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $farmers = $pdo->query("SELECT * FROM farmer")->fetchAll();
            foreach ($farmers as $f): ?>
                <tr>
                    <td><?php echo htmlspecialchars($f['name']); ?></td>
                    <td><?php echo htmlspecialchars($f['nic']); ?></td>
                    <td><?php echo htmlspecialchars($f['phone']); ?></td>
                    <td><?php echo htmlspecialchars($f['address']); ?></td>
                    <td>
                        <a href="?delete_id=<?php echo $f['farmer_id']; ?>"
                            onclick="return confirm('Are you sure you want to delete this farmer?')"
                            class="status status-rejected"
                            style="text-decoration: none; padding: 5px 10px; font-size: 0.8rem;">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>