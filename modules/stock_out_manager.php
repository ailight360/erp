<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

$settings = getSettings($pdo);
$companyProfile = getCompanyProfile($pdo);

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$rowsPerPage = 10;
$offset = ($page - 1) * $rowsPerPage;

// Search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchClause = $search ? "WHERE s.id LIKE '%$search%' OR s.invoice_no LIKE '%$search%' OR c.name LIKE '%$search%'" : '';

// Get total count
$countStmt = $pdo->query("SELECT COUNT(*) as total FROM stock_out s 
                          LEFT JOIN companies c ON s.company_id = c.id $searchClause");
$totalRows = $countStmt->fetch()['total'];
$totalPages = ceil($totalRows / $rowsPerPage);

// Get stock out records
$stmt = $pdo->prepare("SELECT s.*, c.name as company_name,
                       COALESCE(SUM(i.qty), 0) as total_qty,
                       GROUP_CONCAT(DISTINCT i.qty_unit) as qty_units,
                       COALESCE(SUM(i.pkg), 0) as total_pkg,
                       GROUP_CONCAT(DISTINCT i.pkg_unit) as pkg_units
                       FROM stock_out s
                       LEFT JOIN stock_out_items i ON s.id = i.stock_out_id
                       LEFT JOIN companies c ON s.company_id = c.id
                       $searchClause
                       GROUP BY s.id
                       ORDER BY s.entry_date DESC, s.id DESC
                       LIMIT ? OFFSET ?");
$stmt->execute([$rowsPerPage, $offset]);
$stockOutRecords = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $settings['theme'] ?? 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Out Manager - ERP System</title>
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
                        <li><a href="stock_out_add.php">New Stock Out</a></li>
                        <li><a href="stock_out_manager.php" class="active">Stock Out Manager</a></li>
                    </ul>
                </li>
                <li><a href="../includes/logout.php">Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1>Stock Out Manager</h1>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Stock Out Records</h2>
                </div>

                <div class="search-bar">
                    <input type="text" class="form-control" placeholder="Search by ID, Invoice or Company..." 
                           onkeyup="searchTable(this, 'stockOutTable')" id="searchInput">
                    <button class="btn btn-primary" onclick="window.location.href='stock_out_add.php'">+ Add New</button>
                </div>

                <div class="table-responsive">
                    <table class="data-table" id="stockOutTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Invoice</th>
                                <th>Company Name</th>
                                <th>Total Qty + Unit</th>
                                <th>Total Pkg + Unit</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stockOutRecords)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">No records found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($stockOutRecords as $record): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($record['id']); ?></td>
                                        <td><?php echo htmlspecialchars($record['entry_date']); ?></td>
                                        <td><?php echo htmlspecialchars($record['invoice_no']); ?></td>
                                        <td><?php echo htmlspecialchars($record['company_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php echo number_format($record['total_qty'], 2); ?> 
                                            <small><?php echo htmlspecialchars($record['qty_units']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo number_format($record['total_pkg'], 2); ?> 
                                            <small><?php echo htmlspecialchars($record['pkg_units']); ?></small>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-view" title="View">👁</button>
                                                <button class="btn btn-sm btn-edit" title="Edit">✏</button>
                                                <button class="btn btn-sm btn-delete" 
                                                        onclick="deleteRecord(<?php echo $record['id']; ?>)" title="Delete">🗑</button>
                                                <button class="btn btn-sm btn-print" 
                                                        onclick="printDocument(<?php echo $record['id']; ?>)" title="Print">🖨</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination" id="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                               class="<?php echo $i === $page ? 'active' : ''; ?>"
                               style="padding: 0.5rem 1rem; text-decoration: none; border: 1px solid var(--border-color); 
                                      border-radius: 5px; color: var(--text-color);">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../assets/js/app.js"></script>
    <script>
        function deleteRecord(id) {
            if (confirm('Are you sure you want to delete this record?')) {
                window.location.href = `../includes/delete_stock_out.php?id=${id}`;
            }
        }
    </script>
</body>
</html>
