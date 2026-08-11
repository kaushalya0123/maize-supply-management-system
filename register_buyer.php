<?php
// register_buyer.php
require_once 'config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $buyer_name = $_POST['buyer_name'] ?? '';

    if (!empty($username) && !empty($password) && !empty($buyer_name)) {
        try {
            $pdo->beginTransaction();

            // 1. Create User
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'buyer')");
            $stmt->execute([$username, $hashed_password]);
            $user_id = $pdo->lastInsertId();

            // 2. Create Buyer linked to the user account
            // 2. Create Buyer linked to the user account
$stmt = $pdo->prepare("
    INSERT INTO buyer (buyer_name, company_id)
    VALUES (?, ?)
");

$stmt->execute([
    $buyer_name,
    1
]);

$buyer_id = $pdo->lastInsertId();

// 3. Update User with buyer_id
$stmt = $pdo->prepare("
    UPDATE users
    SET buyer_id = ?
    WHERE id = ?
");

$stmt->execute([
    $buyer_id,
    $user_id
]);
            // 3. Update User with buyer_id
        $stmt = $pdo->prepare("UPDATE users SET buyer_id = ? WHERE id = ?");
        $stmt->execute([$buyer_id, $user_id]);

            $pdo->commit();
            $success = "Registration successful! You can now login.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();

        }
    } else {
        $error = "All fields are required.";
    }
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buyer Registration - Maize System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Buyer Registration</h1>
            <p>Join the Maize Supply Network</p>
        </div>

        <?php if ($error): ?>
            <div class="error-msg">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="error-msg"
                style="background: rgba(46, 125, 50, 0.2); border-color: var(--primary); color: var(--primary-light);">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="buyer_name">Full Name / Business Name</label>
                <input type="text" id="buyer_name" name="buyer_name" required placeholder="Enter buyer name">
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required placeholder="Choose a username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Choose a password">
            </div>
            <button type="submit" class="login-btn">REGISTER</button>
        </form>

        <div class="register-link">
            <p>Already have an account? <a href="index.php">Login here</a></p>
        </div>
    </div>
</body>

</html>