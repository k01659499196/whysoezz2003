<?php
// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'imperium_shop');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site settings
define('SITE_NAME', 'IMPERIUM SHOP');
define('SITE_TAGLINE', 'Premium Digital Products');

// Global database instance
global $db;

// ========================================
// HELPER FUNCTIONS
// ========================================

function sanitize($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize($value);
        }
        return $data;
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /imperium-shop/user/login.php');
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: /imperium-shop/');
        exit;
    }
}

function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . 'đ';
}

function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}

function getCartCount() {
    if (!isLoggedIn()) return 0;
    
    global $db;
    if (!isset($db)) {
        require_once __DIR__ . '/database.php';
        $db = new Database();
    }
    
    $result = $db->fetchOne(
        "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?",
        [$_SESSION['user_id']]
    );
    
    return (int)($result['total'] ?? 0);
}

function getUserBalance() {
    if (!isLoggedIn()) return 0;
    
    global $db;
    if (!isset($db)) {
        require_once __DIR__ . '/database.php';
        $db = new Database();
    }
    
    $result = $db->fetchOne("SELECT balance FROM users WHERE id = ?", [$_SESSION['user_id']]);
    return (float)($result['balance'] ?? 0);
}

function getPaymentSetting($key) {
    global $db;
    if (!isset($db)) {
        require_once __DIR__ . '/database.php';
        $db = new Database();
    }
    
    $result = $db->fetchOne("SELECT setting_value FROM payment_settings WHERE setting_key = ?", [$key]);
    return $result['setting_value'] ?? '';
}

// CSRF Protection
function getCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken() {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . getCsrfToken() . '">';
}
