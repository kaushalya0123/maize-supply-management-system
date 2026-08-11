<?php
// includes/header.php
require_once __DIR__ . '/auth.php';
checkLogin();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> - Maize System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php if(!empty($msg)): ?>

<script>

Swal.fire({
    icon:'error',
    title:'Validation Error',
    text:'<?= addslashes($msg); ?>',
    confirmButtonColor:'#2e7d32'
});

</script>

<?php endif; ?>

    <style>
        body {
            display: block;
            background: #eef2f7;
            /* Soft premium blue-grey background */
            color: #1a202c;
            min-height: 100vh;
        }

        .sidebar {
    width: 280px;
    height: 100vh;
    background: linear-gradient(180deg, #1b5e20 0%, #01579b 100%);
    color: white;
    position: fixed;
    left: 0;
    top: 0;
    padding: 1.5rem 0;
    display: flex;
    flex-direction: column;
    box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
    z-index: 1000;

    overflow-y: auto;
    overflow-x: hidden;
}

        .sidebar-brand {
            padding: 0 2rem 2rem 2rem;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            font-size: 1.4rem;
            color: #fbc02d;
        }

        .sidebar-brand i {
            font-size: 1.8rem;
        }

        .sidebar ul {
            list-style: none;
            padding: 0 1rem;
        }

        .sidebar ul li {
            margin-bottom: 0.4rem;
        }

        .sidebar ul li a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 1.2rem;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
        }

        .sidebar ul li a i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background: rgba(255, 255, 255, 0.1);
            color: #fbc02d;
            transform: translateX(5px);
        }

        .logout-item {
            margin-top: auto;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 1rem !important;
        }

        .logout-item a {
            color: #ff8a80 !important;
        }

        .main-content {
            margin-left: 280px;
            padding: 2rem 3rem;
            transition: all 0.3s;
        }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .sidebar {
                display: none;
                /* Hide sidebar on mobile */
            }

            .main-content {
                margin-left: 0;
                padding: 1.5rem;
                padding-bottom: 5rem;
                /* Space for bottom nav */
            }

            .top-bar {
                margin-bottom: 1.5rem;
                padding: 1rem;
            }

            .search-box {
                width: 100%;
                display: none;
                /* Hide search to save space */
            }

            .user-info {
                display: none;
                /* Hide text, just show avatar */
            }

            .mobile-nav {
                display: flex !important;
            }
        }

        /* Bottom Nav for Mobile */
        .mobile-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(90deg, #1b5e20 0%, #01579b 100%);
            height: 70px;
            z-index: 2000;
            justify-content: space-around;
            align-items: center;
            box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.1);
        }

        .mobile-nav a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 0.7rem;
            gap: 5px;
        }

        .mobile-nav a.active {
            color: #fbc02d;
        }

        .mobile-nav a i {
            font-size: 1.2rem;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            padding: 1.2rem 2rem;
            border-radius: 20px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .search-box {
            background: #f0f2f5;
            padding: 10px 15px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            width: 300px;
        }

        .search-box input {
            border: none;
            background: transparent;
            outline: none;
            width: 100%;
            font-size: 0.9rem;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 12px;
            transition: 0.3s;
        }

        .user-profile:hover {
            background: #f0f2f5;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: #1b5e20;
            color: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .card {
            background: white;
            padding: 1.5rem;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #f0f2f5;
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06);
            border-color: #0288d1;
        }

        .card-icon {
            width: 54px;
            height: 54px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        .card-content h3 {
            font-size: 0.85rem;
            color: #718096;
            margin-bottom: 0.2rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-content .value {
            font-size: 1.6rem;
            font-weight: 700;
            color: #1a202c;
        }

        .bg-green {
            background: #e8f5e9;
            color: #1b5e20;
        }

        .bg-yellow {
            background: #fffde7;
            color: #f9a825;
        }

        .bg-blue {
            background: #e3f2fd;
            color: #0288d1;
        }

        .bg-red {
            background: #ffebee;
            color: #c62828;
        }

        .chart-container {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            margin-bottom: 2rem;
        }

        .table-container {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            margin-bottom: 2rem;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        th,
        td {
            text-align: left;
            padding: 16px;
            border-bottom: 1px solid #f0f2f5;
        }

        th {
            background: #fafafa;
            color: #4a5568;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tr:hover td {
            background: #fbfbfb;
        }

        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fffaf0;
            color: #dd6b20;
            border: 1px solid #fbd38d;
        }

        .status-approved {
            background: #f0fff4;
            color: #2f855a;
            border: 1px solid #9ae6b4;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            display: inline-block;
        }

        .btn-primary {
            background: #2e7d32;
            color: white;
            box-shadow: 0 4px 10px rgba(46, 125, 50, 0.2);
        }

        .btn-primary:hover {
            background: #1b5e20;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(46, 125, 50, 0.3);
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-seedling"></i>
            <span>MAIZE SYSTEM</span>
        </div>
        <ul>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li><a href="dashboard.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"><i
                            class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="manage_farmers.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_farmers.php' ? 'active' : ''; ?>"><i
                            class="fas fa-users"></i> Farmers</a></li>
                <li><a href="manage_supply.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_supply.php' ? 'active' : ''; ?>"><i
                            class="fas fa-truck-ramp-box"></i> Supply</a></li>
                <li><a href="manage_buyers.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_buyers.php' ? 'active' : ''; ?>"><i
                            class="fas fa-shopping-cart"></i> Buyers</a></li>
                <li><a href="manage_stock.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_stock.php' ? 'active' : ''; ?>"><i
                            class=" fas fa-boxes-stacked"></i> Stock</a></li>
                <li><a href="manage_varieties.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_varieties.php' ? 'active' : ''; ?>"><i
                            class="fas fa-seedling"></i> Varieties</a></li>
                <li><a href="manage_lands.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_lands.php' ? 'active' : ''; ?>"><i
                            class=" fas fa-map-location-dot"></i> Lands</a></li>
                <li><a href="manage_orders.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_orders.php' ? 'active' : ''; ?>"><i
                            class="fas fa-file-invoice"></i> Orders</a></li>
                <li><a href="manage_payments.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_payments.php' ? 'active' : ''; ?>">
                          <i class="fas fa-money-bill-wave"></i> Payments</a></li>
                <li><a href="reports.php"

                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>"><i
                            class="fas fa-chart-line"></i> Reports</a></li>
            
            
                            <?php elseif ($_SESSION['role'] === 'farmer'): ?>
                <li><a href="dashboard.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"><i
                            class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="supply_history.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'supply_history.php' ? 'active' : ''; ?>"><i
                            class="fas fa-history"></i> Supply History</a></li>
                <li><a href="payments.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'active' : ''; ?>"><i
                            class="fas fa-coins"></i> Payments</a></li>
                <li><a href="bonus.php"
                            class="<?php echo basename($_SERVER['PHP_SELF']) == 'bonus.php' ? 'active' : ''; ?>">
                <i class="fas fa-gift"></i> My Bonus</a></li>
           
                            <?php elseif ($_SESSION['role'] === 'buyer'): ?>
                <li><a href="dashboard.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"><i
                            class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="stock.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'stock.php' ? 'active' : ''; ?>"><i
                            class="fas fa-warehouse"></i> Available Stock</a></li>
                <li><a href="my_orders.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'my_orders.php' ? 'active' : ''; ?>"><i
                            class="fas fa-clipboard-list"></i> My Orders</a></li>
            <?php endif; ?>
            <li class="logout-item"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="top-bar">
            <div class="search-box">
                <i class="fas fa-search" style="color: #666;"></i>
                <input type="text" placeholder="Search orders, farmers, stock...">
            </div>
            <div class="user-profile">
                <div class="user-info" style="text-align: right;">
                    <span
                        style="display: block; font-weight: 700; font-size: 0.9rem;"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <span style="font-size: 0.75rem; color: #666;"><?php echo ucfirst($_SESSION['role']); ?>
                        Specialist</span>
                </div>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                </div>
            </div>
        </div>
        <!-- Mobile Bottom Navigation -->
        <div class="mobile-nav">
            <a href="dashboard.php"
                class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="manage_orders.php"
                    class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_orders.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-invoice"></i>
                    <span>Orders</span>
                </a>
                <a href="manage_stock.php"
                    class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_stock.php' ? 'active' : ''; ?>">
                    <i class="fas fa-boxes-stacked"></i>
                    <span>Stock</span>
                </a>
                <a href="manage_lands.php"
                    class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_lands.php' ? 'active' : ''; ?>">
                    <i class="fas fa-map-location-dot"></i>
                    <span>Lands</span>
                </a>
            <?php elseif ($_SESSION['role'] === 'farmer'): ?>
                <a href="supply_history.php"
                    class="<?php echo basename($_SERVER['PHP_SELF']) == 'supply_history.php' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i>
                    <span>Supply</span>
                </a>
            <?php elseif ($_SESSION['role'] === 'buyer'): ?>
                <a href="stock.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'stock.php' ? 'active' : ''; ?>">
                    <i class="fas fa-warehouse"></i>
                    <span>Stock</span>
                </a>
                <a href="my_orders.php"
                    class="<?php echo basename($_SERVER['PHP_SELF']) == 'my_orders.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Orders</span>
                </a>
            <?php endif; ?>
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>