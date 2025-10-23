<?php
/**
 * @file auth.php
 * @brief Authentication Guard - Sayfa Koruma Modülü
 *
 * Her korumalı sayfanın başına include edilmesi gereken authentication guard.
 * Giriş kontrolü, timeout kontrolü ve yönlendirme yapar.
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 *
 * @usage
 *   require_once 'auth.php'; // Sayfanın en başına ekle
 *
 * @security
 *   - Session timeout kontrolü (30 dakika)
 *   - Login kontrolü
 *   - Otomatik yönlendirme (login.php)
 */

require_once 'session_helper.php';

// Session başlat (zaten başlamışsa tekrar başlatmaz)
startSession();

// Timeout kontrolü
if (!checkSessionTimeout()) {
    header('Location: login.php?timeout=1');
    exit;
}

// Login kontrolü
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Login süresi hesapla (opsiyonel - göstermek istersen)
function getLoginDuration() {
    if (isset($_SESSION['login_time'])) {
        $duration = time() - $_SESSION['login_time'];
        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        return "{$hours} saat {$minutes} dakika";
    }
    return "Bilinmiyor"; // Anormal durum.
}
?>