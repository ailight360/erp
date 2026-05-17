<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

$settings = getSettings($pdo);
$companyProfile = getCompanyProfile($pdo);
$sources = getDropdownValues($pdo, 'Sources');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $entryDate = $_POST['entry_date'];
        $shift = $_POST['shift'];
        $transactionType = $_POST['transaction_type'];
        $source = $_POST['source'];
        
        $pp = $_POST['pp'] ?? 0;
        $lldpe = $_POST['lldpe'] ?? 0;
        $hdpe = $_POST['hdpe'] ?? 0;
        $fg_pp = $_POST['fg_pp'] ?? 0;
        $tpt = $_POST['tpt'] ?? 0;
        $cc = $_POST['cc'] ?? 0;
        
        // Calculate totals (Every bag is 25 kg)
        $totalKgs = $pp + $lldpe + $hdpe + $fg_pp + $tpt + $cc;
        $totalBags = round($totalKgs / 25);
        
        $stmt = $pdo->prepare("INSERT INTO resin_transactions 
                              (entry_date, shift, transaction_type, source, pp, lldpe, hdpe, fg_pp, tpt, cc, total_bags, total_kgs) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$entryDate, $shift, $transactionType, $source, $pp, $lldpe, $hdpe, $fg_pp, $tpt, $cc, $totalBags, $totalKgs]);
        
        $success = "Resin transaction saved successfully! Total: $totalBags Bags ($totalKgs KGs)";
    } catch (Exception $e) {
        $error = "Error saving record: " . $e->getMessage();
    }
}

// Get resin records
$resinRecords = $pdo->query("SELECT * FROM resin_transactions ORDER BY entry_date DESC, id DESC LIMIT 50")->fetchAll();

// Calculate current balance
$balanceStmt = $pdo->query("
    SELECT 
        SUM(CASE WHEN transaction_type = 'In' THEN total_kgs ELSE -total_kgs END) as balance
    FROM resin_transactions
")->fetch();
$currentBalance = $balanceStmt['balance'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $settings['theme'] ?? 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resin Management - ERP System</title>
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
                <li><a href="resin.php" class="active">Resin</a></li>
                <li><a href="../includes/logout.php">Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1>Resin Data In/Out</h1>
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

            <!-- Current Balance Card -->
            <div class="card" style="background: linear-gradient(135deg, var(--primary-color), #8b5cf6); color: white;">
                <h2>Current Resin Balance</h2>
                <p style="font-size: 2.5rem; font-weight: bold; margin-top: 1rem;">
                    <?php echo number_format(abs($currentBalance), 2); ?> KG 
                    (<?php echo round(abs($currentBalance) / 25); ?> Bags)
                </p>
                <small><?php echo $currentBalance >= 0 ? 'Positive Balance' : 'Negative Balance'; ?></small>
            </div>

            <!-- Entry Form -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Add Resin Transaction</h2>
                </div>

                <form method="POST">
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
                            <label for="transaction_type">Transaction</label>
                            <select id="transaction_type" name="transaction_type" class="form-control" required>
                                <option value="In">In</option>
                                <option value="Out">Out</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="source">Source</label>
                            <select id="source" name="source" class="form-control" required>
                                <option value="">Select Source</option>
                                <?php foreach ($sources as $src): ?>
                                    <option value="<?php echo htmlspecialchars($src['value']); ?>">
                                        <?php echo htmlspecialchars($src['value']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <h3 style="margin: 1.5rem 0 1rem;">Material Quantities (KG)</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="pp">PP</label>
                            <input type="number" id="pp" name="pp" class="form-control" step="0.01" value="0">
                        </div>
                        <div class="form-group">
                            <label for="lldpe">LLDPE</label>
                            <input type="number" id="lldpe" name="lldpe" class="form-control" step="0.01" value="0">
                        </div>
                        <div class="form-group">
                            <label for="hdpe">HDPE</label>
                            <input type="number" id="hdpe" name="hdpe" class="form-control" step="0.01" value="0">
                        </div>
                        <div class="form-group">
                            <label for="fg_pp">FG PP</label>
                            <input type="number" id="fg_pp" name="fg_pp" class="form-control" step="0.01" value="0">
                        </div>
                        <div class="form-group">
                            <label for="tpt">TPT</label>
                            <input type="number" id="tpt" name="tpt" class="form-control" step="0.01" value="0">
                        </div>
                        <div class="form-group">
                            <label for="cc">CC</label>
                            <input type="number" id="cc" name="cc" class="form-control" step="0.01" value="0">
                        </div>
                    </div>

                    <div style="margin-top: 2rem;">
                        <button type="submit" class="btn btn-success">Save Transaction</button>
                        <button type="reset" class="btn btn-secondary">Clear</button>
                    </div>
                </form>
            </div>

            <!-- Records Table -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Resin Transaction Records</h2>
                </div>

                <div class="table-responsive">
                    <table class="data-table" id="resinTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Shift</th>
                                <th>Transaction</th>
                                <th>Source</th>
                                <th>PP</th>
                                <th>LLDPE</th>
                                <th>HDPE</th>
                                <th>FG PP</th>
                                <th>TPT</th>
                                <th>CC</th>
                                <th>Bags</th>
                                <th>KGs</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($resinRecords)): ?>
                                <tr>
                                    <td colspan="13" style="text-align: center;">No records found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($resinRecords as $record): ?>
                                    <tr style="<?php echo $record['transaction_type'] === 'In' ? 'background: #f0fdf4;' : 'background: #fef2f2;'; ?>">
                                        <td><?php echo htmlspecialchars($record['entry_date']); ?></td>
                                        <td><?php echo htmlspecialchars($record['shift']); ?></td>
                                        <td>
                                            <span style="padding: 0.25rem 0.5rem; border-radius: 3px; 
                                                  background: <?php echo $record['transaction_type'] === 'In' ? '#22c55e' : '#ef4444'; ?>; 
                                                  color: white; font-size: 0.875rem;">
                                                <?php echo htmlspecialchars($record['transaction_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($record['source']); ?></td>
                                        <td><?php echo number_format($record['pp'], 2); ?></td>
                                        <td><?php echo number_format($record['lldpe'], 2); ?></td>
                                        <td><?php echo number_format($record['hdpe'], 2); ?></td>
                                        <td><?php echo number_format($record['fg_pp'], 2); ?></td>
                                        <td><?php echo number_format($record['tpt'], 2); ?></td>
                                        <td><?php echo number_format($record['cc'], 2); ?></td>
                                        <td><?php echo $record['total_bags']; ?></td>
                                        <td><?php echo number_format($record['total_kgs'], 2); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-edit" title="Edit">✏</button>
                                                <button class="btn btn-sm btn-delete" 
                                                        onclick="deleteRecord(<?php echo $record['id']; ?>)" title="Delete">🗑</button>
                                            </div>
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
    <script>
        function deleteRecord(id) {
            if (confirm('Are you sure you want to delete this transaction?')) {
                window.location.href = `../includes/delete_resin.php?id=${id}`;
            }
        }
    </script>
</body>
</html>
