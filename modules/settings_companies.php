<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

$settings = getSettings($pdo);
$companyProfile = getCompanyProfile($pdo);

// Handle add new company
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_company'])) {
    $name = $_POST['name'];
    $address = $_POST['address'];
    $mobile = $_POST['mobile'];
    $remarks = $_POST['remarks'];
    
    $stmt = $pdo->prepare("INSERT INTO companies (name, address, mobile, remarks) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $address, $mobile, $remarks]);
    $success = "Company added successfully!";
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_company'])) {
    $id = $_POST['company_id'];
    $name = $_POST['name'];
    $address = $_POST['address'];
    $mobile = $_POST['mobile'];
    $remarks = $_POST['remarks'];
    
    $stmt = $pdo->prepare("UPDATE companies SET name = ?, address = ?, mobile = ?, remarks = ? WHERE id = ?");
    $stmt->execute([$name, $address, $mobile, $remarks, $id]);
    $success = "Company updated successfully!";
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM companies WHERE id = ?")->execute([$id]);
    header('Location: settings_companies.php?deleted=1');
    exit;
}

// Get all companies
$companies = $pdo->query("SELECT * FROM companies ORDER BY name ASC")->fetchAll();

// Get company for editing
$editCompany = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editCompany = $pdo->prepare("SELECT * FROM companies WHERE id = ?")->fetch([$editId]);
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $settings['theme'] ?? 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Manager - ERP System</title>
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
                        <li><a href="settings_groups.php">Group Products</a></li>
                        <li><a href="settings_companies.php" class="active">Company Manager</a></li>
                    </ul>
                </li>
                <li><a href="../includes/logout.php">Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1>Company Manager</h1>
            </div>

            <?php if (isset($success)): ?>
                <div class="card" style="background: var(--success-color); color: white;">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="card" style="background: var(--danger-color); color: white;">
                    Company deleted successfully!
                </div>
            <?php endif; ?>

            <!-- Add/Edit Company Form -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><?php echo $editCompany ? 'Edit Company' : 'Add New Company'; ?></h2>
                </div>

                <form method="POST">
                    <?php if ($editCompany): ?>
                        <input type="hidden" name="company_id" value="<?php echo $editCompany['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Company Name</label>
                            <input type="text" id="name" name="name" class="form-control" 
                                   value="<?php echo htmlspecialchars($editCompany['name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="mobile">Mobile</label>
                            <input type="text" id="mobile" name="mobile" class="form-control" 
                                   value="<?php echo htmlspecialchars($editCompany['mobile'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" class="form-control" rows="2"><?php echo htmlspecialchars($editCompany['address'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="remarks">Remarks</label>
                        <textarea id="remarks" name="remarks" class="form-control" rows="2" 
                                  placeholder="Additional notes about this company"><?php echo htmlspecialchars($editCompany['remarks'] ?? ''); ?></textarea>
                    </div>

                    <div style="margin-top: 2rem;">
                        <button type="submit" name="<?php echo $editCompany ? 'update_company' : 'add_company'; ?>" class="btn btn-primary">
                            <?php echo $editCompany ? 'Update Company' : 'Save Company'; ?>
                        </button>
                        <?php if ($editCompany): ?>
                            <a href="settings_companies.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Companies Table -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">All Companies</h2>
                    <span style="background: var(--primary-color); color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.875rem;">
                        <?php echo count($companies); ?> companies
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Company Name</th>
                                <th>Address</th>
                                <th>Mobile</th>
                                <th>Remarks</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($companies)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center;">No companies defined</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($companies as $company): ?>
                                    <tr>
                                        <td><?php echo $company['id']; ?></td>
                                        <td><?php echo htmlspecialchars($company['name']); ?></td>
                                        <td><?php echo htmlspecialchars($company['address']); ?></td>
                                        <td><?php echo htmlspecialchars($company['mobile']); ?></td>
                                        <td><?php echo htmlspecialchars($company['remarks']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="?edit=<?php echo $company['id']; ?>" class="btn btn-sm btn-edit" title="Edit">✏</a>
                                                <button class="btn btn-sm btn-delete" 
                                                        onclick="if(confirm('Delete this company? This may affect existing stock out records.')) window.location.href='?delete=<?php echo $company['id']; ?>'" 
                                                        title="Delete">🗑</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Usage Info -->
            <div class="card" style="background: var(--bg-color);">
                <h3>ℹ️ Usage Information</h3>
                <p>Companies added here will be available in the <strong>Stock Out</strong> module when creating new stock out transactions. The company information will also appear on printed invoices and delivery documents.</p>
            </div>
        </main>
    </div>

    <script src="../assets/js/app.js"></script>
</body>
</html>
