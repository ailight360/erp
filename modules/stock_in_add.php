<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

$settings = getSettings($pdo);
$companyProfile = getCompanyProfile($pdo);
$mfgValues = getDropdownValues($pdo, 'MFG');
$productValues = getDropdownValues($pdo, 'Product');
$qtyUnits = getDropdownValues($pdo, 'Qty_UoM');
$pkgUnits = getDropdownValues($pdo, 'Pkg_UoM');
$groupProducts = getGroupProducts($pdo);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        $entryDate = $_POST['entry_date'];
        $shift = $_POST['shift'];
        $mfgSource = $_POST['mfg_source'];
        
        // Insert stock in header
        $stmt = $pdo->prepare("INSERT INTO stock_in (entry_date, shift, mfg_source) VALUES (?, ?, ?)");
        $stmt->execute([$entryDate, $shift, $mfgSource]);
        $stockInId = $pdo->lastInsertId();
        
        // Insert items
        if (isset($_POST['product_id'])) {
            foreach ($_POST['product_id'] as $key => $productId) {
                $stmt = $pdo->prepare("INSERT INTO stock_in_items (stock_in_id, product_type, product_id, qty, qty_unit, pkg, pkg_unit) 
                                      VALUES (?, 'Single', ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $stockInId,
                    $productId,
                    $_POST['qty'][$key],
                    $_POST['qty_unit'][$key],
                    $_POST['pkg'][$key],
                    $_POST['pkg_unit'][$key]
                ]);
            }
        }
        
        if (isset($_POST['group_product_id'])) {
            foreach ($_POST['group_product_id'] as $key => $groupId) {
                $stmt = $pdo->prepare("INSERT INTO stock_in_items (stock_in_id, product_type, product_id, qty, qty_unit, pkg, pkg_unit) 
                                      VALUES (?, 'Group', ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $stockInId,
                    $groupId,
                    $_POST['qty_group'][$key],
                    $_POST['qty_unit_group'][$key],
                    $_POST['pkg_group'][$key],
                    $_POST['pkg_unit_group'][$key]
                ]);
            }
        }
        
        $pdo->commit();
        $success = "Stock In record saved successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error saving record: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $settings['theme'] ?? 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Stock - ERP System</title>
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
                    <a href="#" onclick="toggleSubmenu('stockInMenu')">Stock In ▼</a>
                    <ul class="sidebar-submenu show" id="stockInMenu">
                        <li><a href="stock_in_add.php" class="active">Add New Stock</a></li>
                        <li><a href="stock_in_manager.php">Stock In Manager</a></li>
                    </ul>
                </li>
                <li><a href="../includes/logout.php">Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1>Add New Stock In</h1>
            </div>

            <?php if (isset($success)): ?>
                <div class="card" style="background: var(--success-color); color: white;">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="card" style="background: var(--danger-color); color: white;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <form method="POST" id="stockInForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="entry_date">Date</label>
                            <input type="date" id="entry_date" name="entry_date" class="form-control" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="shift">Shift</label>
                            <select id="shift" name="shift" class="form-control" required>
                                <option value="Day">Day</option>
                                <option value="Night">Night</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="mfg_source">MFG Source</label>
                            <select id="mfg_source" name="mfg_source" class="form-control" required>
                                <option value="">Select MFG</option>
                                <?php foreach ($mfgValues as $mfg): ?>
                                    <option value="<?php echo htmlspecialchars($mfg['value']); ?>">
                                        <?php echo htmlspecialchars($mfg['value']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <button type="button" class="btn btn-sm btn-success" onclick="addProductRow()">+ Single Product</button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="addGroupProductRow()">+ Group Product</button>
                    </div>

                    <div id="productRows">
                        <!-- Dynamic rows will be added here -->
                    </div>

                    <div style="margin-top: 2rem;">
                        <button type="submit" class="btn btn-success">Save</button>
                        <button type="button" class="btn btn-secondary" onclick="clearForm()">Clear</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script src="../assets/js/app.js"></script>
    <script>
        // Override helper functions with actual data
        function getProductOptions() {
            return <?php echo json_encode(
                array_map(function($p) {
                    return '<option value="' . $p['id'] . '">' . htmlspecialchars($p['value']) . '</option>';
                }, $productValues)
            ); ?>.join('');
        }

        function getGroupProductOptions() {
            return <?php echo json_encode(
                array_map(function($g) {
                    return '<option value="' . $g['id'] . '">' . htmlspecialchars($g['group_name']) . '</option>';
                }, $groupProducts)
            ); ?>.join('');
        }

        function getUnitOptions(type) {
            let options = [];
            <?php foreach ($qtyUnits as $unit): ?>
            if (type === 'Qty_UoM') {
                options.push('<option value="<?php echo htmlspecialchars($unit['value']); ?>"><?php echo htmlspecialchars($unit['value']); ?></option>');
            }
            <?php endforeach; ?>
            
            <?php foreach ($pkgUnits as $unit): ?>
            if (type === 'Pkg_UoM') {
                options.push('<option value="<?php echo htmlspecialchars($unit['value']); ?>"><?php echo htmlspecialchars($unit['value']); ?></option>');
            }
            <?php endforeach; ?>
            
            return options.join('');
        }

        // Add one row by default
        document.addEventListener('DOMContentLoaded', function() {
            addProductRow();
        });
    </script>
</body>
</html>
