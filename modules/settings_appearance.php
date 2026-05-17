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

// Handle theme update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_appearance'])) {
    $theme = $_POST['theme'];
    $accentColor = $_POST['accent_color'];
    $layout = $_POST['layout'];
    
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                           ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute(['theme', $theme, $theme]);
    $stmt->execute(['accent_color', $accentColor, $accentColor]);
    $stmt->execute(['layout', $layout, $layout]);
    
    $success = "Appearance settings updated successfully!";
    $settings = getSettings($pdo); // Refresh settings
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $settings['theme'] ?? 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appearance Settings - ERP System</title>
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
                        <li><a href="settings_appearance.php" class="active">Appearance</a></li>
                        <li><a href="settings_company.php">Company Profile</a></li>
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
                <h1>Appearance Manager</h1>
            </div>

            <?php if (isset($success)): ?>
                <div class="card" style="background: var(--success-color); color: white;">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="theme">Theme</label>
                            <select id="theme" name="theme" class="form-control">
                                <option value="light" <?php echo ($settings['theme'] ?? 'light') === 'light' ? 'selected' : ''; ?>>Light</option>
                                <option value="dark" <?php echo ($settings['theme'] ?? 'light') === 'dark' ? 'selected' : ''; ?>>Dark</option>
                                <option value="auto" <?php echo ($settings['theme'] ?? 'light') === 'auto' ? 'selected' : ''; ?>>Auto (System)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="accent_color">Accent Color</label>
                            <input type="color" id="accent_color" name="accent_color" class="form-control" 
                                   value="<?php echo $settings['accent_color'] ?? '#3b82f6'; ?>" 
                                   style="height: 50px; padding: 5px;">
                        </div>
                        
                        <div class="form-group">
                            <label for="layout">Layout</label>
                            <select id="layout" name="layout" class="form-control">
                                <option value="auto" <?php echo ($settings['layout'] ?? 'auto') === 'auto' ? 'selected' : ''; ?>>Auto</option>
                                <option value="desktop" <?php echo ($settings['layout'] ?? 'auto') === 'desktop' ? 'selected' : ''; ?>>Desktop</option>
                                <option value="tablet" <?php echo ($settings['layout'] ?? 'auto') === 'tablet' ? 'selected' : ''; ?>>Tablet</option>
                                <option value="mobile" <?php echo ($settings['layout'] ?? 'auto') === 'mobile' ? 'selected' : ''; ?>>Mobile</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="margin-top: 2rem;">
                        <button type="submit" name="update_appearance" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
            </div>

            <!-- Preview -->
            <div class="card">
                <h3>Preview</h3>
                <p>Current Theme: <strong><?php echo ucfirst($settings['theme'] ?? 'light'); ?></strong></p>
                <p>Accent Color: <span style="display: inline-block; width: 20px; height: 20px; background: <?php echo $settings['accent_color'] ?? '#3b82f6'; ?>; border-radius: 3px;"></span></p>
                <p>Layout: <strong><?php echo ucfirst($settings['layout'] ?? 'auto'); ?></strong></p>
            </div>
        </main>
    </div>

    <script src="../assets/js/app.js"></script>
    <script>
        // Update theme preview in real-time
        document.getElementById('theme').addEventListener('change', function() {
            document.documentElement.setAttribute('data-theme', this.value);
        });
        
        document.getElementById('accent_color').addEventListener('input', function() {
            document.documentElement.style.setProperty('--primary-color', this.value);
        });
    </script>
</body>
</html>
