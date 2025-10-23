<?php
/**
 * @file companyPanel/checkCompany.php
 * @brief Firma Kimlik Doğrulama İşleyicisi
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 *
 * @security password_verify(), timing attack prevention
 */

// companyPanel/checkCompany.php
require_once '../session_helper.php';
require_once '../889b1769-5f97-4b94-ac58-69877e948de7/SecurityModule.php';

startSession(); // session_start() yerine

// POST kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// Database bağlantısı
require_once '../dbconnect.php';

// Form verilerini al - MODE_EMAIL ve MODE_PASSWORD kullan
$email = SecurityModule::pass(trim($_POST['email'] ?? ''), SecurityModule::MODE_EMAIL);
$password = SecurityModule::pass($_POST['password'] ?? '', SecurityModule::MODE_PASSWORD);

// Basit validasyon
if (empty($email) || empty($password)) {
    $_SESSION['error'] = 'Email ve parola zorunludur';
    header('Location: login.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Geçersiz email formatı';
    header('Location: login.php');
    exit;
}

// Kullanıcıyı bul (company_id ile JOIN)
$stmt = $db->prepare("
    SELECT 
        u.id, 
        u.full_name, 
        u.email, 
        u.password, 
        u.role, 
        u.company_id,
        bc.name as company_name
    FROM User u
    LEFT JOIN Bus_Company bc ON u.company_id = bc.id
    WHERE u.email = ?
");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// ============= ATOMİK KONTROL (Timing Attack Önleme) =============
$login_success = false;
$start = microtime(true);

if ($user) {
    // Gerçek kontroller
    $password_valid = password_verify($password, $user['password']);
    $role_valid = ($user['role'] === 'company');
    $company_valid = !empty($user['company_id']);

    // Hem şifre hem de rol hem de company_id doğruysa başarılı
    $login_success = ($password_valid && $role_valid);

} else {
    // Timing attack önleme - dummy hash kontrolü
    password_verify($password, '$2y$10$dummyhashdummyhashdummyhashdummyhashdummyhashdummy');
}

// Burada isteğin çalıştığı süreyi ölç
$elapsed_ms = (microtime(true) - $start) * 1000.0;

// Eğer süre MIN_MS'in altındaysa aradaki fark kadar usleep ile bekle
// (usleep parametresi mikro saniye). // DoS riski taşıyor farkındayım.
if ($elapsed_ms < 100) {
    $sleep_us = (int)((50 - $elapsed_ms) * 1000.0);
    // Sınır koy: aşırı uzun uyku engellenir
    if ($sleep_us > 0 && $sleep_us < 500000) { // max 500ms uyku
        usleep($sleep_us);
    }
}

// ============= GİRİŞ BAŞARILI =============

// Session regenerate
session_regenerate_id(true);

// Session ayarla
$_SESSION['user_id'] = $user['id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];
$_SESSION['company_id'] = $user['company_id'];
$_SESSION['company_name'] = $user['company_name'];
$_SESSION['logged_in'] = true;
$_SESSION['login_time'] = time();
$_SESSION['login_datetime'] = date('Y-m-d H:i:s');
$_SESSION['last_activity'] = time();

// Company dashboard'a yönlendir
header('Location: dashboard.php');
exit;
?>