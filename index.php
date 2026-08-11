<?php
// index.php
require_once 'config/db.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Verify the password against the hashed password in the database.
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['buyer_id'] = $user['buyer_id'];
            $_SESSION['farmer_id'] = $user['farmer_id'];

            switch ($user['role']) {
                case 'admin':
                    header("Location: admin/dashboard.php");
                    break;
                case 'farmer':
                    header("Location: farmer/dashboard.php");
                    break;
                case 'buyer':
                    header("Location: buyer/dashboard.php");
                    break;
                default:
                    $error = "Invalid role assigned.";
            }
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Please enter both username and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Maize Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Maize Stock & Supply</h1>
            <p>Order Management System - Ampara</p>
        </div>

        <?php if ($error): ?>
            <div class="error-msg">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required placeholder="Enter your username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
            </div>
            <button type="submit" class="login-btn">LOGIN</button>
        </form>

        <div class="register-link">
            <p>Are you a buyer? <a href="register_buyer.php">Create an Account</a></p>
        </div>
    </div>
</body>

</html>