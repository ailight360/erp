<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

$settings = getSettings($pdo);
$companyProfile = getCompanyProfile($pdo);
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $settings['theme'] ?? 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $companyProfile['company_name'] ?? 'ERP System'; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <?php echo $companyProfile['company_name'] ?? 'ERP System'; ?>
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Dashboard</a>
                </li>
                <li>
                    <a href="#" onclick="toggleSubmenu('stockInMenu')">Stock In ▼</a>
                    <ul class="sidebar-submenu" id="stockInMenu">
                        <li><a href="modules/stock_in_add.php">Add New Stock</a></li>
                        <li><a href="modules/stock_in_manager.php">Stock In Manager</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#" onclick="toggleSubmenu('stockOutMenu')">Stock Out ▼</a>
                    <ul class="sidebar-submenu" id="stockOutMenu">
                        <li><a href="modules/stock_out_add.php">New Stock Out</a></li>
                        <li><a href="modules/stock_out_manager.php">Stock Out Manager</a></li>
                    </ul>
                </li>
                <li>
                    <a href="modules/inventory.php">Inventory</a>
                </li>
                <li>
                    <a href="modules/resin.php">Resin</a>
                </li>
                <li>
                    <a href="#" onclick="toggleSubmenu('settingsMenu')">Settings ▼</a>
                    <ul class="sidebar-submenu" id="settingsMenu">
                        <li><a href="modules/settings_appearance.php">Appearance</a></li>
                        <li><a href="modules/settings_company.php">Company Profile</a></li>
                        <li><a href="modules/settings_dropdowns.php">Dropdown Manager</a></li>
                        <li><a href="modules/settings_groups.php">Group Products</a></li>
                        <li><a href="modules/settings_companies.php">Company Manager</a></li>
                    </ul>
                </li>
                <li>
                    <a href="includes/logout.php">Logout</a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1>Dashboard</h1>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Welcome to ERP System</h2>
                </div>
                <p>Select an option from the sidebar to get started.</p>
            </div>

            <!-- Quick Stats -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div class="card">
                    <h3>Total Stock In</h3>
                    <p style="font-size: 2rem; color: var(--success-color);" id="totalStockIn">0</p>
                </div>
                <div class="card">
                    <h3>Total Stock Out</h3>
                    <p style="font-size: 2rem; color: var(--danger-color);" id="totalStockOut">0</p>
                </div>
                <div class="card">
                    <h3>Low Stock Items</h3>
                    <p style="font-size: 2rem; color: var(--warning-color);" id="lowStockItems">0</p>
                </div>
                <div class="card">
                    <h3>Resin Balance</h3>
                    <p style="font-size: 2rem; color: var(--primary-color);" id="resinBalance">0 KG</p>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        // Load dashboard stats
        async function loadDashboardStats() {
            try {
                const response = await fetch('includes/api_stats.php');
                const data = await response.json();
                
                document.getElementById('totalStockIn').textContent = data.stockIn || 0;
                document.getElementById('totalStockOut').textContent = data.stockOut || 0;
                document.getElementById('lowStockItems').textContent = data.lowStock || 0;
                document.getElementById('resinBalance').textContent = (data.resin || 0) + ' KG';
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        loadDashboardStats();
    </script>
</body>
</html>
