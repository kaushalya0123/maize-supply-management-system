<?php
// setup_db.php
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'maize_system';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Create Database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $pdo->exec("USE $dbname");
    echo "Database '$dbname' ensured.\n";

    // 2. Create Tables
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'farmer', 'buyer') NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS farmer (
        farmer_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        nic VARCHAR(15) UNIQUE,
        phone VARCHAR(15),
        address TEXT,
        user_id INT,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS company (
        company_id INT AUTO_INCREMENT PRIMARY KEY,
        company_name VARCHAR(100) NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS buyer (
        buyer_id INT AUTO_INCREMENT PRIMARY KEY,
        buyer_name VARCHAR(100) NOT NULL,
        company_id INT,
        user_id INT,
        FOREIGN KEY (company_id) REFERENCES company(company_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS land (
        land_id INT AUTO_INCREMENT PRIMARY KEY,
        farmer_id INT,
        land_name VARCHAR(100),
        land_size DECIMAL(10,2),
        unit VARCHAR(10) DEFAULT 'Acres',
        FOREIGN KEY (farmer_id) REFERENCES farmer(farmer_id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS variety (
        variety_id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50) NOT NULL,
        price_per_kg DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS maize (
        maize_id INT AUTO_INCREMENT PRIMARY KEY,
        farmer_id INT,
        land_id INT,
        variety_id INT,
        quantity DECIMAL(10,2),
        price_per_kg DECIMAL(10,2),
        supply_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (farmer_id) REFERENCES farmer(farmer_id),
        FOREIGN KEY (land_id) REFERENCES land(land_id),
        FOREIGN KEY (variety_id) REFERENCES variety(variety_id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS stock (
        stock_id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT,
        variety_id INT,
        quantity DECIMAL(10,2),
        last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES company(company_id),
        FOREIGN KEY (variety_id) REFERENCES variety(variety_id),
        UNIQUE KEY unique_company_variety (company_id, variety_id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        order_id INT AUTO_INCREMENT PRIMARY KEY,
        buyer_id INT,
        variety_id INT,
        required_quantity DECIMAL(10,2),
        order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        FOREIGN KEY (buyer_id) REFERENCES buyer(buyer_id),
        FOREIGN KEY (variety_id) REFERENCES variety(variety_id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS payment (
        payment_id INT AUTO_INCREMENT PRIMARY KEY,
        maize_id INT,
        farmer_id INT,
        basic_amount DECIMAL(10,2),
        bonus_amount DECIMAL(10,2),
        total_paid DECIMAL(10,2),
        payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (maize_id) REFERENCES maize(maize_id),
        FOREIGN KEY (farmer_id) REFERENCES farmer(farmer_id)
    )");

    echo "Tables ensured.\n";

    // 3. Initial Data
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $hashed_admin = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (username, password, role) VALUES ('admin', '$hashed_admin', 'admin')");
        echo "Admin user created (admin/admin123).\n";
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM company");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO company (company_name) VALUES ('Ampro Maize Corp')");
        echo "Default company created.\n";
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM variety");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO variety (type, price_per_kg) VALUES ('Hybrid', 125.00), ('Normal', 110.00)");
        echo "Initial varieties created.\n";
    }

    echo "Database setup completed successfully.\n";

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage() . "\n");
}
?>