<?php
/**
 * @file addUser.php
 * @brief Yeni Kullanıcı Kayıt İşleyicisi
 *
 * Register formundan gelen POST verilerini işler, yeni kullanıcı kaydı oluşturur.
 * Validasyon ve güvenlik kontrolleri yapar.
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 *
 * @security
 *   - SecurityModule::pass() ile input validation (MODE_TEXT, MODE_EMAIL, MODE_PASSWORD)
 *   - Full name format kontrolü (regex)
 *   - Email validation (filter_var + uniqueness check)
 *   - Password strength check (min 6 chars)
 *   - password_hash() ile bcrypt hashing
 *   - UUID generation (random_bytes)
 *   - Turkish character support (mb_strtolower, turkishUcwords)
 */

session_start();
require_once '889b1769-5f97-4b94-ac58-69877e948de7/SecurityModule.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

require_once 'dbconnect.php';

// Form verilerini al - Selective modes kullan
$full_name = SecurityModule::pass(trim($_POST['full_name'] ?? ''), SecurityModule::MODE_TEXT);
$email = SecurityModule::pass(trim($_POST['email'] ?? ''), SecurityModule::MODE_EMAIL);
$password = SecurityModule::pass($_POST['password'] ?? '', SecurityModule::MODE_PASSWORD);

// Validasyon
if (empty($full_name) || empty($email) || empty($password)) {
    die('Tüm alanlar zorunludur. <a href="register.php">Geri dön</a>');
}

// Full name - başta ve sonda boşluk temizle
$full_name = trim($full_name);

// Full name - birden fazla boşluğu tek boşluğa çevir
$full_name = preg_replace('/\s+/', ' ', $full_name);

// Full name kontrolü - harf ile başlamalı, harf ile bitmeli, ortada sadece tek boşluk olabilir
if (!preg_match('/^[a-zA-ZğüşöçıİĞÜŞÖÇ]+(\s[a-zA-ZğüşöçıİĞÜŞÖÇ]+)*$/', $full_name)) {
    die('Ad soyad formatı hatalı. <a href="register.php">Geri dön</a>');
}

// Full name en az 3 karakter olmalı
if (strlen($full_name) < 3) {
    die('Ad soyad en az 3 karakter olmalı. <a href="register.php">Geri dön</a>');
}

// Full name en fazla 50 karakter olmalı
if (strlen($full_name) > 50) {
    die('Ad soyad en fazla 50 karakter olabilir. <a href="register.php">Geri dön</a>');
}

// Her kelimenin ilk harfini büyük yap (Türkçe karakterler dahil)
function turkishUcwords($string) {
    $string = mb_convert_case($string, MB_CASE_TITLE, "UTF-8");
    // Türkçe i harfi için özel düzenleme
    $string = str_replace(['İ', 'I'], ['İ', 'I'], $string);
    $string = preg_replace_callback('/\bi\b/u', function($matches) {
        return 'İ';
    }, $string);
    return $string;
}

$full_name = turkishUcwords(mb_strtolower($full_name, 'UTF-8'));

// Email format kontrolü
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die('Geçersiz email formatı. <a href="register.php">Geri dön</a>');
}

// Email'i küçük harfe çevir (case-insensitive)
$email = strtolower($email);

// Password kontrolü
if (strlen($password) < 6) {
    die('Parola en az 6 karakter olmalı. <a href="register.php">Geri dön</a>');
}

// Email kontrolü - zaten var mı?
$check = $db->prepare("SELECT id FROM User WHERE email = ?");
$check->execute([$email]);

if ($check->fetch()) {
    die('Bu email adresi kayıtlı. <a href="login.php?status=false">Giriş yap</a>');
}

// UUID oluştur (32 karakterlik)
$user_id = bin2hex(random_bytes(16));

// Password hash
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Kullanıcıyı ekle
$sql = "INSERT INTO User (id, full_name, email, role, password) VALUES (?, ?, ?, 'user', ?)";
$stmt = $db->prepare($sql);

try {
    $stmt->execute([$user_id, $full_name, $email, $hashed_password]);
    echo "Kayıt başarılı! <strong>{$full_name}</strong> olarak kaydedildiniz.<br>";
    echo "<a href='login.php'>Giriş yapabilirsiniz</a>";
} catch (PDOException $e) {
    die('Kayıt sırasında hata oluştu. <a href="register.php?status=true">Tekrar dene</a>');
}
?>