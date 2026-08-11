<?php
// admin/dashboard.php
$pageTitle = 'Admin Dashboard';
require_once '../config/db.php';
require_once '../includes/header.php';
checkRole('admin');

// Fetch some stats
$buyerCount = $pdo->query("SELECT COUNT(*) FROM buyer")->fetchColumn();
$totalStock = $pdo->query("SELECT SUM(quantity) FROM stock")->fetchColumn() ?? 0;
$pendingOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$totalSupply = $pdo->query("
    SELECT COALESCE(SUM(quantity_used),0)
    FROM order_supply
")->fetchColumn();
$varietyCount = $pdo->query("SELECT COUNT(*) FROM variety")->fetchColumn();

// Fetch Advanced Stock Metrics for the Unified Doughnut Chart
$soldQty = $pdo->query("SELECT SUM(required_quantity) FROM orders WHERE status = 'approved'")->fetchColumn() ?? 0;
$availableStock = $pdo->query("SELECT SUM(quantity) FROM stock")->fetchColumn() ?? 0;
$pendingDemand = $pdo->query("SELECT SUM(required_quantity) FROM orders WHERE status = 'pending'")->fetchColumn() ?? 0;

// Total Farmer Bonus
$totalBonus = $pdo->query("
    SELECT COALESCE(SUM(bonus_amount),0)
    FROM payment
")->fetchColumn();
$shortage = max(0, $pendingDemand - $availableStock);

// Colors
$soldColor = "#f57c00"; // Orange
$stockColor = "#2e7d32"; // Green
$shortageColor = "#c62828"; // Dark Red
?>

<?php
// Dynamic Greeting
date_default_timezone_set('Asia/Colombo');
$hour = date('H');
$greeting = "Good Morning";
if ($hour >= 12 && $hour < 17)
    $greeting = "Good Afternoon";
elseif ($hour >= 17)
    $greeting = "Good Evening";
?>

<div style="margin-bottom: 2.5rem;">
    <h2 style="font-size: 1.8rem; font-weight: 800; color: #1a202c;"><?php echo $greeting; ?>, Admin</h2>
    <p style="color: #718096; font-size: 0.95rem;">Real-time inventory analysis and supply-chain health.</p>
</div>

<!-- NEW TOP SECTION: Five Stat Cards -->
<div class="dashboard-cards" style="margin-bottom: 2.5rem;">
    <div class="card">
        <div class="card-icon bg-yellow"><i class="fas fa-shopping-cart"></i></div>
        <div class="card-content">
            <h3>Buyers</h3>
            <div class="value"><?php echo number_format($buyerCount); ?></div>
        </div>
    </div>
    <div class="card">
        <div class="card-icon bg-blue"><i class="fas fa-boxes-stacked"></i></div>
        <div class="card-content">
            <h3>Current Stock</h3>
            <div class="value"><?php echo number_format($totalStock); ?> KG</div>
        </div>
    </div>
    <div class="card" onclick="location.href='manage_varieties.php';" style="cursor: pointer;">
        <div class="card-icon bg-green"><i class="fas fa-seedling"></i></div>
        <div class="card-content">
            <h3>Varieties</h3>
            <div class="value"><?php echo number_format($varietyCount); ?> Types</div>
        </div>
    </div>
    <div class="card">
        <div class="card-icon bg-red"><i class="fas fa-clock"></i></div>
        <div class="card-content">
            <h3>Pending Orders</h3>
            <div class="value"><?php echo number_format($pendingOrders); ?></div>
        </div>
    </div>
    <div class="card">
        <div class="card-icon bg-green"><i class="fas fa-truck-field"></i></div>
        <div class="card-content">
            <h3>Total Supply</h3>
            <div class="value"><?php echo number_format($totalSupply); ?> KG</div>
        </div>
       
    </div>
    <div class="card">
    <div class="card-icon bg-blue">
        <i class="fas fa-coins"></i>
    </div>

    <div class="card-content">
        <h3>Farmer Bonus Paid</h3>

        <div class="value">
            LKR <?php echo number_format($totalBonus,2); ?>
        </div>
    </div>
</div>

</div> 



<!-- Global Stock Status (Doughnut) underneath -->
<div class="chart-container"
    style="max-width: 450px; margin: 0 auto 2.5rem auto; text-align: center; position: relative; padding: 1.2rem;">
    <h3
        style="margin-bottom: 1rem; display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 0.95rem;">
        <i class="fas fa-warehouse" style="color: #1b5e20;"></i> Global Stock Status
    </h3>

    <div style="position: relative; height: 260px;">
        <canvas id="unifiedStockChart"></canvas>
        <!-- Center Text -->
        <div
            style="position: absolute; top: 58%; left: 50%; transform: translate(-50%, -50%); text-align: center; pointer-events: none;">
            <span
                style="font-size: 0.7rem; color: #718096; text-transform: uppercase; font-weight: 700;">Available</span><br>
            <span
                style="font-size: 1.8rem; font-weight: 800; color: #1a202c;"><?php echo number_format($availableStock); ?></span><br>
            <span style="font-size: 0.7rem; color: #718096;">KG</span>
        </div>
    </div>

    <?php if ($shortage > 0): ?>
        <div
            style="margin-top: 1rem; padding: 0.8rem; background: #fff5f5; border: 1px solid #feb2b2; border-radius: 10px; color: #c53030; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 0.85rem; animation: pulse 2s infinite;">
            <i class="fas fa-triangle-exclamation"></i>
            <strong>Shortage Warning:</strong> <?php echo number_format($shortage); ?> KG
        </div>
    <?php endif; ?>
</div>

<style>
    @keyframes pulse {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.02);
        }

        100% {
            transform: scale(1);
        }
    }
</style>



<div class="table-container">
    <h3 style="margin-bottom: 1.5rem;"><i class="fas fa-list-check" style="color: #2e7d32; margin-right: 10px;"></i>
        Recent Orders</h3>
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Buyer</th>
                <th>Variety</th>
                <th>Quantity</th>
                <th>Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stmt = $pdo->query("SELECT o.*, b.buyer_name, v.type 
                                FROM orders o 
                                JOIN buyer b ON o.buyer_id = b.buyer_id 
                                JOIN variety v ON o.variety_id = v.variety_id 
                                ORDER BY o.order_date DESC LIMIT 5");
            while ($row = $stmt->fetch()):
                ?>
                <tr>
                    <td><?php echo $row['order_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['buyer_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['type']); ?></td>
                    <td><?php echo $row['required_quantity']; ?> KG</td>
                    <td><?php echo date('Y-m-d', strtotime($row['order_date'])); ?></td>
                    <td><span
                            class="status status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span>
                    </td>
                    <td><a href="manage_orders.php?id=<?php echo $row['order_id']; ?>" class="btn btn-primary">Manage</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>

    // 4. Unified Stock Status Doughnut
    const unifiedCtx = document.getElementById('unifiedStockChart').getContext('2d');
    new Chart(unifiedCtx, {
        type: 'doughnut',
        data: {
            labels: ['Sold (Approved)', 'Available Stock', 'Shortage (Demand Gap)'],
            datasets: [{
                data: [<?php echo $soldQty; ?>, <?php echo $availableStock; ?>, <?php echo $shortage; ?>],
                backgroundColor: [
                    '#ef6c00', // Orange for Sold
                    '#2e7d32', // Green for Stock
                    '#c62828'  // Dark Red for Shortage
                ],
                borderWidth: 0,
                hoverOffset: 20
            }]
        },
        options: {
            cutout: '75%',
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 20, usePointStyle: true, font: { size: 12, weight: '600' } }
                },
                tooltip: {
                    titleFont: { size: 14 },
                    bodyFont: { size: 13 },
                    callbacks: {
                        label: function (context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const value = context.parsed;
                            const percent = ((value / total) * 100).toFixed(1);
                            return ` ${context.label}: ${value.toLocaleString()} KG (${percent}%)`;
                        }
                    }
                }
            },
            animation: {
                animateScale: true,
                animateRotate: true
            }
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>