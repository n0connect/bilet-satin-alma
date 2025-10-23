<?php
/**
 * @file adminPanel/checkAdmin.php
 * @brief Admin Kimlik Doğrulama İşleyicisi
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 *
 * @security SecurityModule::pass(), password_verify(), timing attack prevention
 */

// adminPanel/checkAdmin.php
require_once '../session_helper.php';
require_once '../889b1769-5f97-4b94-ac58-69877e948de7/SecurityModule.php';

startSession(); // session_start() yerine

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

require_once '../dbconnect.php';

// Form verilerini al - MODE_EMAIL ve MODE_PASSWORD kullan
$email = SecurityModule::pass(trim($_POST['email'] ?? ''), SecurityModule::MODE_EMAIL);
$password = SecurityModule::pass($_POST['password'] ?? '', SecurityModule::MODE_PASSWORD);

// Validasyon
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

// Sadece admin rolüne sahip kullanıcıyı bul
$stmt = $db->prepare("SELECT id, full_name, email, password, role, balance FROM User WHERE email = ? AND role = 'admin'");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// ------------- RESPONSE TIME A GÖRE VULNERABILITY OLUŞTURABİLİR
// ------------- PRODUCTION'DA ISLEMLER ATOMIKLESTIRILMELIDIR.
$login_success = false;
// ------------- ------------ ------------ ----------- ----------- ------------
$start = microtime(true);

if ($user) {
    // Gerçek kontroller
    $password_valid = password_verify($password, $user['password']);
    $role_valid = ($user['role'] === 'admin');
    
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

// Tek nokta kontrolü
if (!$login_success) {
    $_SESSION['error'] = 'Email veya parola hatalı';
    header('Location: login.php');
    exit;
}

// Session regenerate - güvenlik için
session_regenerate_id(true);

// Session ayarla
$_SESSION['user_id'] = $user['id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];
$_SESSION['balance'] = $user['balance'] ?? 0;
$_SESSION['logged_in'] = true;
$_SESSION['login_time'] = time();
$_SESSION['login_datetime'] = date('Y-m-d H:i:s');
$_SESSION['last_activity'] = time();

header('Location: dashboard.php');
exit;
?>