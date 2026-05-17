<?php
// includes/delete_stock_out.php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM stock_out WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = "Record deleted successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting record: " . $e->getMessage();
    }
}

header('Location: ../modules/stock_out_manager.php');
exit;
?>
