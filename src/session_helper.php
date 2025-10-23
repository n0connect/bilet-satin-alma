<?php
/**
 * @file session_helper.php
 * @brief Session Yönetim Yardımcı Fonksiyonları
 *
 * Session başlatma, kontrol etme, timeout yönetimi ve rol bazlı
 * işlemler için yardımcı fonksiyonlar içerir.
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 *
 * @functions
 *   - startSession(): Session'ı güvenli ayarlarla başlatır
 *   - isLoggedIn(): Kullanıcı giriş kontrolü
 *   - checkSessionTimeout(): 30 dakika timeout kontrolü
 *   - hasRole(): Tek rol kontrolü
 *   - hasAnyRole(): Çoklu rol kontrolü
 *   - redirectToPanel(): Rol bazlı yönlendirme
 *   - secureLogout(): Güvenli çıkış (session + cookie temizleme)
 *
 * @security
 *   - httponly: true (XSS koruması)
 *   - samesite: Lax (CSRF koruması)
 *   - use_strict_mode: 1
 *   - use_only_cookies: 1
 *   - 30 dakika timeout
 */


/**
 * Session başlat - eğer başlamamışsa
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Session güvenlik ayarları
        ini_set('session.use_only_cookies', 1);
        ini_set('session.use_strict_mode', 1);
        
        session_set_cookie_params([
            'lifetime' => 0, // Tarayıcı kapanana kadar
            'path' => '/',
            'domain' => '', // Boş bırak, otomatik alsın
            'secure' => false, // HTTPS kullanıyorsan true yap
            'httponly' => true, // JavaScript erişimini engelle
            'samesite' => 'Lax' // CSRF koruması
        ]);
        
        session_start();
    }
}

/**
 * Kullanıcı giriş yapmış mı kontrol et
 */
function isLoggedIn() {
    startSession();
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Session timeout kontrolü (30 dakika)
 */
function checkSessionTimeout($timeout = 1800) { // 30 dakika = 1800 saniye
    startSession();
    
    if (isset($_SESSION['last_activity'])) {
        $inactive = time() - $_SESSION['last_activity'];
        
        if ($inactive > $timeout) {
            // Session timeout olmuş
            session_unset();
            session_destroy();
            return false;
        }
    }
    
    // Aktiviteyi güncelle
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Rol kontrolü
 */
function hasRole($role) {
    startSession();
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Çoklu rol kontrolü
 */
function hasAnyRole($roles = ['admin', 'company', 'user']) {
    startSession();
    return isset($_SESSION['role']) && in_array($_SESSION['role'], $roles);
}

/**
 * Panel bazlı yönlendirme
 */
function redirectToPanel($role = null) {
    $role = $role ?? $_SESSION['role'] ?? null;
    
    switch($role) {
        case 'admin':
            return '/adminPanel/dashboard.php?status=true';
        case 'company':
            return '/companyPanel/dashboard.php?status=true';
        case 'user':
            return '/dashboard.php?status=true';
        default:
            return '/login.php?status=true';
    }
}

/**
 * Güvenli çıkış
 */
function secureLogout() {
    startSession();
    
    // Session değişkenlerini temizle
    $_SESSION = array();
    
    // Session cookie'sini sil
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Session'ı yok et
    session_destroy();

    header('Location: index.php');
}
?>