<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

$settings = getSettings($pdo);
$companyProfile = getCompanyProfile($pdo);
$productValues = getDropdownValues($pdo, 'Product');
$qtyUnits = getDropdownValues($pdo, 'Qty_UoM');
$pkgUnits = getDropdownValues($pdo, 'Pkg_UoM');

// Calculate inventory
$inventoryStmt = $pdo->query("
    SELECT 
        p.value as product_name,
        COALESCE(SUM(CASE WHEN si.product_id = p.id THEN si.qty ELSE 0 END), 0) -
        COALESCE(SUM(CASE WHEN so.product_id = p.id THEN so.qty ELSE 0 END), 0) as net_qty,
        GROUP_CONCAT(DISTINCT si.qty_unit SEPARATOR ', ') as qty_units,
        COALESCE(SUM(CASE WHEN si.product_id = p.id THEN si.pkg ELSE 0 END), 0) -
        COALESCE(SUM(CASE WHEN so.product_id = p.id THEN so.pkg ELSE 0 END), 0) as net_pkg,
        GROUP_CONCAT(DISTINCT si.pkg_unit SEPARATOR ', ') as pkg_units
    FROM dropdown_values p
    LEFT JOIN stock_in_items si ON p.id = si.product_id AND si.product_type = 'Single'
    LEFT JOIN stock_out_items so ON p.id = so.product_id AND so.product_type = 'Single'
    WHERE p.type = 'Product'
    GROUP BY p.id, p.value
");
$inventoryItems = $inventoryStmt->fetchAll();

// Get group products availability
$groupProductsStmt = $pdo->query("
    SELECT gp.*, p1.value as product1_name, p2.value as product2_name
    FROM group_products gp
    JOIN dropdown_values p1 ON gp.product_1_id = p1.id
    JOIN dropdown_values p2 ON gp.product_2_id = p2.id
");
$groupProducts = $groupProductsStmt->fetchAll();

// Hide zero stock filter
$hideZero = isset($_GET['hide_zero']) && $_GET['hide_zero'] === '1';
if ($hideZero) {
    $inventoryItems = array_filter($inventoryItems, function($item) {
        return $item['net_qty'] > 0 || $item['net_pkg'] > 0;
    });
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $settings['theme'] ?? 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Manager - ERP System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <?php echo $companyProfile['company_name'] ?? 'ERP System'; ?>
            </div>
            <ul class="sidebar-menu">
                <li><a href="../index.php">Dashboard</a></li>
                <li><a href="inventory.php" class="active">Inventory</a></li>
                <li><a href="../includes/logout.php">Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1>Inventory Manager</h1>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Current Inventory</h2>
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <input type="text" class="form-control" placeholder="Search products..." 
                               onkeyup="searchTable(this, 'inventoryTable')" style="width: 250px;">
                        
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" id="hideZero" onchange="toggleHideZero()" 
                                   <?php echo $hideZero ? 'checked' : ''; ?>>
                            Hide Zero Stock
                        </label>
                        
                        <button class="btn btn-secondary" onclick="window.print()">🖨 Print Inventory</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="data-table" id="inventoryTable">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Product Qty</th>
                                <th>Qty Unit</th>
                                <th>Pkg Qty</th>
                                <th>Pkg Unit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($inventoryItems)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">No inventory items found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($inventoryItems as $item): ?>
                                    <tr style="<?php echo ($item['net_qty'] <= 0 && $item['net_pkg'] <= 0) ? 'background: #fef2f2;' : ''; ?>">
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td><?php echo number_format($item['net_qty'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($item['qty_units'] ?? 'N/A'); ?></td>
                                        <td><?php echo number_format($item['net_pkg'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($item['pkg_units'] ?? 'N/A'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Group Products Availability -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Group Product Availability</h2>
                </div>

                <div class="table-responsive">
                    <table class="data-table" id="groupProductsTable">
                        <thead>
                            <tr>
                                <th>Group Product Name</th>
                                <th>Product 1</th>
                                <th>Product 2</th>
                                <th>Available Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($groupProducts)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center;">No group products defined</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($groupProducts as $gp): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($gp['group_name']); ?></td>
                                        <td><?php echo htmlspecialchars($gp['product1_name']); ?></td>
                                        <td><?php echo htmlspecialchars($gp['product2_name']); ?></td>
                                        <td>
                                            <?php
                                            // Calculate minimum available quantity between the two products
                                            $stmt = $pdo->prepare("
                                                SELECT 
                                                    COALESCE(SUM(CASE WHEN si.product_id IN (?, ?) THEN si.qty ELSE 0 END), 0) -
                                                    COALESCE(SUM(CASE WHEN so.product_id IN (?, ?) THEN so.qty ELSE 0 END), 0) as available
                                                FROM stock_in_items si
                                                LEFT JOIN stock_out_items so ON si.product_id = so.product_id
                                                WHERE si.product_id IN (?, ?)
                                            ");
                                            $stmt->execute([$gp['product_1_id'], $gp['product_2_id'], 
                                                           $gp['product_1_id'], $gp['product_2_id'],
                                                           $gp['product_1_id'], $gp['product_2_id']]);
                                            $available = $stmt->fetch()['available'];
                                            echo number_format($available, 2);
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/app.js"></script>
    <script>
        function toggleHideZero() {
            const hideZero = document.getElementById('hideZero').checked;
            window.location.href = `?hide_zero=${hideZero ? '1' : '0'}`;
        }
    </script>
</body>
</html>
