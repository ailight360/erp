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

// Handle add new dropdown value
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_value'])) {
    $type = $_POST['dropdown_type'];
    $value = $_POST['dropdown_value'];
    
    $stmt = $pdo->prepare("INSERT INTO dropdown_values (type, value) VALUES (?, ?)");
    $stmt->execute([$type, $value]);
    $success = "Dropdown value added successfully!";
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM dropdown_values WHERE id = ?")->execute([$id]);
    header('Location: settings_dropdowns.php?deleted=1');
    exit;
}

// Get all dropdown values grouped by type
$dropdownTypes = ['Product', 'MFG', 'Qty_UoM', 'Pkg_UoM', 'Sources'];
$allDropdowns = [];
foreach ($dropdownTypes as $type) {
    $stmt = $pdo->prepare("SELECT * FROM dropdown_values WHERE type = ? ORDER BY value ASC");
    $stmt->execute([$type]);
    $allDropdowns[$type] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $settings['theme'] ?? 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dropdown Manager - ERP System</title>
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
                        <li><a href="settings_dropdowns.php" class="active">Dropdown Manager</a></li>
                        <li><a href="settings_groups.php">Group Products</a></li>
                        <li><a href="settings_companies.php">Company Manager</a></li>
                    </ul>
                </li>
                <li><a href="../includes/logout.php">Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1>Dropdown Manager</h1>
            </div>

            <?php if (isset($success)): ?>
                <div class="card" style="background: var(--success-color); color: white;">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="card" style="background: var(--danger-color); color: white;">
                    Dropdown value deleted successfully!
                </div>
            <?php endif; ?>

            <!-- Add New Value Form -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Add New Dropdown Value</h2>
                </div>

                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="dropdown_type">Dropdown Type</label>
                            <select id="dropdown_type" name="dropdown_type" class="form-control" required>
                                <option value="">Select Type</option>
                                <option value="Product">Product</option>
                                <option value="MFG">MFG</option>
                                <option value="Qty_UoM">Qty UoM</option>
                                <option value="Pkg_UoM">Pkg UoM</option>
                                <option value="Sources">Sources</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="dropdown_value">Value</label>
                            <input type="text" id="dropdown_value" name="dropdown_value" class="form-control" 
                                   placeholder="Enter value" required>
                        </div>
                        
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" name="add_value" class="btn btn-primary">Add Value</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Dropdown Tables -->
            <?php foreach ($allDropdowns as $type => $values): ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><?php echo str_replace('_', ' ', $type); ?> Values</h2>
                        <span style="background: var(--primary-color); color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.875rem;">
                            <?php echo count($values); ?> items
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Value</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($values)): ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center;">No values defined</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($values as $item): ?>
                                        <tr>
                                            <td><?php echo $item['id']; ?></td>
                                            <td><?php echo htmlspecialchars($item['value']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-delete" 
                                                        onclick="if(confirm('Delete this value?')) window.location.href='?delete=<?php echo $item['id']; ?>'" 
                                                        title="Delete">🗑</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </main>
    </div>

    <script src="../assets/js/app.js"></script>
</body>
</html>
