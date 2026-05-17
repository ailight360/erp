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

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$rowsPerPage = 10;
$offset = ($page - 1) * $rowsPerPage;

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchClause = '';
$params = [];

if ($search) {
    $searchClause = "WHERE s.id LIKE ? OR s.mfg_source LIKE ?";
    $params = ["%$search%", "%$search%"];
}

// Get total count
$countSql = "SELECT COUNT(*) as total FROM stock_in s";
if ($searchClause) {
    $countSql .= " " . $searchClause;
}
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRows = $countStmt->fetch()['total'];
$totalPages = ceil($totalRows / $rowsPerPage);

// Get stock in records
$sql = "SELECT s.*, 
               COALESCE(SUM(i.qty), 0) as total_qty,
               GROUP_CONCAT(DISTINCT i.qty_unit) as qty_units,
               COALESCE(SUM(i.pkg), 0) as total_pkg,
               GROUP_CONCAT(DISTINCT i.pkg_unit) as pkg_units
        FROM stock_in s
        LEFT JOIN stock_in_items i ON s.id = i.stock_in_id";
if ($searchClause) {
    $sql .= " " . $searchClause;
}
$sql .= " GROUP BY s.id
           ORDER BY s.entry_date DESC, s.id DESC
           LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);
$paramsForQuery = array_merge($params, [$rowsPerPage, $offset]);
$stmt->execute($paramsForQuery);
$stockInRecords = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $settings['theme'] ?? 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock In Manager - ERP System</title>
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
                        <li><a href="stock_in_add.php">Add New Stock</a></li>
                        <li><a href="stock_in_manager.php" class="active">Stock In Manager</a></li>
                    </ul>
                </li>
                <li><a href="../includes/logout.php">Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1>Stock In Manager</h1>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Stock In Records</h2>
                </div>

                <div class="search-bar">
                    <input type="text" class="form-control" placeholder="Search by ID or MFG..." 
                           onkeyup="searchTable(this, 'stockInTable')" id="searchInput">
                    <button class="btn btn-primary" onclick="window.location.href='stock_in_add.php'">+ Add New</button>
                </div>

                <div class="table-responsive">
                    <table class="data-table" id="stockInTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Shift</th>
                                <th>MFG</th>
                                <th>Total Qty + Unit</th>
                                <th>Total Pkg + Unit</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stockInRecords)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">No records found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($stockInRecords as $record): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($record['id']); ?></td>
                                        <td><?php echo htmlspecialchars($record['entry_date']); ?></td>
                                        <td><?php echo htmlspecialchars($record['shift']); ?></td>
                                        <td><?php echo htmlspecialchars($record['mfg_source']); ?></td>
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
                window.location.href = `../includes/delete_stock_in.php?id=${id}`;
            }
        }
    </script>
</body>
</html>
