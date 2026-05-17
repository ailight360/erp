<?php
// includes/delete_resin.php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM resin_transactions WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = "Record deleted successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting record: " . $e->getMessage();
    }
}

header('Location: ../modules/resin.php');
exit;
?>
