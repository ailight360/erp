<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

$settings = getSettings($pdo);
$companyProfile = getCompanyProfile($pdo);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $logoPath = '';
    
    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
        $uploadDir = '../assets/logos/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['logo']['name']);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
            $logoPath = 'assets/logos/' . $fileName;
        }
    }
    
    $companyName = $_POST['company_name'];
    $address = $_POST['address'];
    $mobile = $_POST['mobile'];
    $email = $_POST['email'];
    
    // Check if profile exists
    $existing = $pdo->query("SELECT id FROM company_profile LIMIT 1")->fetch();
    
    if ($existing) {
        $stmt = $pdo->prepare("UPDATE company_profile SET company_name = ?, address = ?, mobile = ?, email = ?" . 
                             ($logoPath ? ", logo_path = ?" : "") . " WHERE id = ?");
        $params = $logoPath ? [$companyName, $address, $mobile, $email, $logoPath, $existing['id']] 
                            : [$companyName, $address, $mobile, $email, $existing['id']];
        $stmt->execute($params);
    } else {
        $stmt = $pdo->prepare("INSERT INTO company_profile (logo_path, company_name, address, mobile, email) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$logoPath, $companyName, $address, $mobile, $email]);
    }
    
    $success = "Company profile updated successfully!";
    $companyProfile = getCompanyProfile($pdo); // Refresh
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $settings['theme'] ?? 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Profile - ERP System</title>
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
                        <li><a href="settings_company.php" class="active">Company Profile</a></li>
                        <li><a href="settings_dropdowns.php">Dropdown Manager</a></li>
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
                <h1>Company Profile</h1>
            </div>

            <?php if (isset($success)): ?>
                <div class="card" style="background: var(--success-color); color: white;">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="logo">Company Logo</label>
                        <?php if ($companyProfile['logo_path']): ?>
                            <div style="margin-bottom: 1rem;">
                                <img src="../<?php echo htmlspecialchars($companyProfile['logo_path']); ?>" 
                                     alt="Logo" style="max-height: 100px; border: 1px solid var(--border-color); padding: 0.5rem;">
                            </div>
                        <?php endif; ?>
                        <input type="file" id="logo" name="logo" class="form-control" accept="image/*">
                        <small>Recommended size: 200x100 pixels (PNG, JPG)</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="company_name">Company Name</label>
                            <input type="text" id="company_name" name="company_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($companyProfile['company_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="mobile">Mobile</label>
                            <input type="text" id="mobile" name="mobile" class="form-control" 
                                   value="<?php echo htmlspecialchars($companyProfile['mobile'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($companyProfile['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" class="form-control" rows="3"><?php echo htmlspecialchars($companyProfile['address'] ?? ''); ?></textarea>
                    </div>

                    <div style="margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary">Save Profile</button>
                    </div>
                </form>
            </div>

            <!-- Profile Preview -->
            <div class="card">
                <h3>Profile Preview (as shown on printouts)</h3>
                <div style="border: 1px solid var(--border-color); padding: 2rem; margin-top: 1rem;">
                    <?php if ($companyProfile['logo_path']): ?>
                        <img src="../<?php echo htmlspecialchars($companyProfile['logo_path']); ?>" 
                             alt="Logo" style="max-height: 80px; display: block; margin-bottom: 1rem;">
                    <?php endif; ?>
                    <h2 style="color: var(--primary-color);"><?php echo htmlspecialchars($companyProfile['company_name'] ?? 'Company Name'); ?></h2>
                    <p><?php echo nl2br(htmlspecialchars($companyProfile['address'] ?? 'Address')); ?></p>
                    <p>📞 <?php echo htmlspecialchars($companyProfile['mobile'] ?? 'Mobile'); ?></p>
                    <p>✉️ <?php echo htmlspecialchars($companyProfile['email'] ?? 'Email'); ?></p>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/app.js"></script>
</body>
</html>
