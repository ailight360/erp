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
$groupProducts = getGroupProducts($pdo);
$companies = getCompanies($pdo);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        $entryDate = $_POST['entry_date'];
        $invoiceNo = $_POST['invoice_no'];
        $companyId = $_POST['company_id'];
        $note = $_POST['note'];
        
        // Insert stock out header
        $stmt = $pdo->prepare("INSERT INTO stock_out (entry_date, invoice_no, company_id, note) VALUES (?, ?, ?, ?)");
        $stmt->execute([$entryDate, $invoiceNo, $companyId, $note]);
        $stockOutId = $pdo->lastInsertId();
        
        // Insert items
        if (isset($_POST['product_id'])) {
            foreach ($_POST['product_id'] as $key => $productId) {
                $stmt = $pdo->prepare("INSERT INTO stock_out_items (stock_out_id, product_type, product_id, qty, qty_unit, pkg, pkg_unit) 
                                      VALUES (?, 'Single', ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $stockOutId,
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
                $stmt = $pdo->prepare("INSERT INTO stock_out_items (stock_out_id, product_type, product_id, qty, qty_unit, pkg, pkg_unit) 
                                      VALUES (?, 'Group', ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $stockOutId,
                    $groupId,
                    $_POST['qty_group'][$key],
                    $_POST['qty_unit_group'][$key],
                    $_POST['pkg_group'][$key],
                    $_POST['pkg_unit_group'][$key]
                ]);
            }
        }
        
        $pdo->commit();
        $success = "Stock Out record saved successfully!";
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
    <title>New Stock Out - ERP System</title>
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
                    <a href="#" onclick="toggleSubmenu('stockOutMenu')">Stock Out ▼</a>
                    <ul class="sidebar-submenu show" id="stockOutMenu">
                        <li><a href="stock_out_add.php" class="active">New Stock Out</a></li>
                        <li><a href="stock_out_manager.php">Stock Out Manager</a></li>
                    </ul>
                </li>
                <li><a href="../includes/logout.php">Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1>New Stock Out</h1>
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
                <form method="POST" id="stockOutForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="entry_date">Date</label>
                            <input type="date" id="entry_date" name="entry_date" class="form-control" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="invoice_no">Invoice Number</label>
                            <input type="text" id="invoice_no" name="invoice_no" class="form-control" placeholder="Enter invoice or auto-generate">
                            <label style="margin-top: 0.5rem;">
                                <input type="checkbox" id="auto_invoice" onchange="toggleAutoInvoice()"> Auto-generate Invoice
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="company_id">Company Name</label>
                            <select id="company_id" name="company_id" class="form-control" required>
                                <option value="">Select Company</option>
                                <?php foreach ($companies as $company): ?>
                                    <option value="<?php echo $company['id']; ?>">
                                        <?php echo htmlspecialchars($company['name']); ?>
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

                    <div class="form-group" style="margin-top: 1rem;">
                        <label for="note">Note</label>
                        <textarea id="note" name="note" class="form-control" rows="3" placeholder="Enter any additional notes..."></textarea>
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
        let invoiceCounter = 1;

        function toggleAutoInvoice() {
            const checkbox = document.getElementById('auto_invoice');
            const invoiceInput = document.getElementById('invoice_no');
            
            if (checkbox.checked) {
                const today = new Date().toISOString().replace(/-/g, '').slice(2);
                invoiceInput.value = 'INV-' + today + '-' + String(invoiceCounter++).padStart(4, '0');
                invoiceInput.readOnly = true;
            } else {
                invoiceInput.readOnly = false;
                invoiceInput.value = '';
            }
        }

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

        document.addEventListener('DOMContentLoaded', function() {
            addProductRow();
        });
    </script>
</body>
</html>
