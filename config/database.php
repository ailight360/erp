<?php
// config/database.php
$host = 'localhost';
$dbname = 'erp_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// Helper Functions
function getDropdownValues($pdo, $type) {
    $stmt = $pdo->prepare("SELECT id, value FROM dropdown_values WHERE type = ? ORDER BY value ASC");
    $stmt->execute([$type]);
    return $stmt->fetchAll();
}

function getGroupProducts($pdo) {
    $stmt = $pdo->query("SELECT * FROM group_products ORDER BY group_name ASC");
    return $stmt->fetchAll();
}

function getCompanies($pdo) {
    $stmt = $pdo->query("SELECT * FROM companies ORDER BY name ASC");
    return $stmt->fetchAll();
}

function getCompanyProfile($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM company_profile LIMIT 1");
        $result = $stmt->fetch();
        return $result ?: ['company_name' => 'ERP System', 'address' => '', 'mobile' => '', 'email' => ''];
    } catch (PDOException $e) {
        return ['company_name' => 'ERP System', 'address' => '', 'mobile' => '', 'email' => ''];
    }
}

function getSettings($pdo) {
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings ?: ['theme' => 'light', 'accent_color' => '#3b82f6', 'layout' => 'auto'];
    } catch (PDOException $e) {
        return ['theme' => 'light', 'accent_color' => '#3b82f6', 'layout' => 'auto'];
    }
}
?>
