<?php
// includes/api_stats.php - Dashboard statistics API
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';

try {
    // Total Stock In count
    $stockInStmt = $pdo->query("SELECT COUNT(*) FROM stock_in");
    $stockIn = $stockInStmt->fetchColumn();

    // Total Stock Out count
    $stockOutStmt = $pdo->query("SELECT COUNT(*) FROM stock_out");
    $stockOut = $stockOutStmt->fetchColumn();

    // Low stock items (products with net qty <= 0)
    $lowStockStmt = $pdo->query("
        SELECT COUNT(*) FROM (
            SELECT 
                p.id,
                COALESCE(SUM(CASE WHEN si.product_id = p.id THEN si.qty ELSE 0 END), 0) -
                COALESCE(SUM(CASE WHEN so.product_id = p.id THEN so.qty ELSE 0 END), 0) as net_qty
            FROM dropdown_values p
            LEFT JOIN stock_in_items si ON p.id = si.product_id AND si.product_type = 'Single'
            LEFT JOIN stock_out_items so ON p.id = so.product_id AND so.product_type = 'Single'
            WHERE p.type = 'Product'
            GROUP BY p.id
            HAVING net_qty <= 0
        ) as low_stock
    ");
    $lowStock = $lowStockStmt->fetchColumn();

    // Resin balance
    $resinStmt = $pdo->query("
        SELECT 
            COALESCE(SUM(CASE WHEN transaction_type = 'In' THEN total_kgs ELSE -total_kgs END), 0) as balance
        FROM resin_transactions
    ");
    $resin = $resinStmt->fetchColumn();

    echo json_encode([
        'stockIn' => (int)$stockIn,
        'stockOut' => (int)$stockOut,
        'lowStock' => (int)$lowStock,
        'resin' => round($resin, 2)
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
