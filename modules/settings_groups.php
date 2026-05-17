<?php
session_start();

// Security headers
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

$settings = getSettings($pdo);
$companyProfile = getCompanyProfile($pdo);
$productValues = getDropdownValues($pdo, 'Product');

// Handle add new group product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_group'])) {
    $groupName = $_POST['group_name'];
    $product1 = $_POST['product_1'];
    $product2 = $_POST['product_2'];
    
    $stmt = $pdo->prepare("INSERT INTO group_products (group_name, product_1_id, product_2_id) VALUES (?, ?, ?)");
    $stmt->execute([$groupName, $product1, $product2]);
    $success = "Group product created successfully!";
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM group_products WHERE id = ?")->execute([$id]);
    header('Location: settings_groups.php?deleted=1');
    exit;
}

// Get all group products
$groupProductsStmt = $pdo->query("
    SELECT gp.*, p1.value as product1_name, p2.value as product2_name
    FROM group_products gp
    JOIN dropdown_values p1 ON gp.product_1_id = p1.id
    JOIN dropdown_values p2 ON gp.product_2_id = p2.id
    ORDER BY gp.group_name ASC
");
$groupProducts = $groupProductsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $settings['theme'] ?? 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Products - ERP System</title>
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
                <li>
                    <a href="#" onclick="toggleSubmenu('settingsMenu')">Settings ▼</a>
                    <ul class="sidebar-submenu show" id="settingsMenu">
                        <li><a href="settings_appearance.php">Appearance</a></li>
                        <li><a href="settings_company.php">Company Profile</a></li>
                        <li><a href="settings_dropdowns.php">Dropdown Manager</a></li>
                        <li><a href="settings_groups.php" class="active">Group Products</a></li>
                        <li><a href="settings_companies.php">Company Manager</a></li>
                    </ul>
                </li>
                <li><a href="../includes/logout.php">Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1>Group Products Manager</h1>
            </div>

            <?php if (isset($success)): ?>
                <div class="card" style="background: var(--success-color); color: white;">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="card" style="background: var(--danger-color); color: white;">
                    Group product deleted successfully!
                </div>
            <?php endif; ?>

            <!-- Add New Group Product Form -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Add New Group Product</h2>
                </div>

                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="group_name">Group Product Name</label>
                            <input type="text" id="group_name" name="group_name" class="form-control" 
                                   placeholder="e.g., Combo Pack A" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="product_1">Select Product 1</label>
                            <select id="product_1" name="product_1" class="form-control" required>
                                <option value="">Select Product</option>
                                <?php foreach ($productValues as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['value']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="product_2">Select Product 2</label>
                            <select id="product_2" name="product_2" class="form-control" required>
                                <option value="">Select Product</option>
                                <?php foreach ($productValues as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['value']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" name="add_group" class="btn btn-primary">Save Group</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Group Products Table -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Existing Group Products</h2>
                    <span style="background: var(--primary-color); color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.875rem;">
                        <?php echo count($groupProducts); ?> groups
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Group Name</th>
                                <th>Product 1</th>
                                <th>Product 2</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($groupProducts)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">No group products defined</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($groupProducts as $gp): ?>
                                    <tr>
                                        <td><?php echo $gp['id']; ?></td>
                                        <td><?php echo htmlspecialchars($gp['group_name']); ?></td>
                                        <td><?php echo htmlspecialchars($gp['product1_name']); ?></td>
                                        <td><?php echo htmlspecialchars($gp['product2_name']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-delete" 
                                                    onclick="if(confirm('Delete this group product?')) window.location.href='?delete=<?php echo $gp['id']; ?>'" 
                                                    title="Delete">🗑</button>
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
</body>
</html>
