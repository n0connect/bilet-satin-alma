<?php
/**
 * @file adminPanel/auth.php
 * @brief Admin Authentication Guard
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 */

// adminPanel/auth.php
require_once '../session_helper.php';
require_once '../889b1769-5f97-4b94-ac58-69877e948de7/SecurityModule.php';

// Session başlat
startSession();

// Timeout kontrolü (30 dakika)
if (!checkSessionTimeout()) {
    $_SESSION['error'] = 'Oturum süreniz doldu. Lütfen tekrar giriş yapın.';
    header('Location: /adminPanel/login.php');
    exit;
}

// Login kontrolü
if (!isLoggedIn()) {
    $_SESSION['error'] = 'Lütfen önce giriş yapın.';
    header('Location: /adminPanel/login.php');
    exit;
}

// Admin rolü kontrolü
if (!hasRole('admin')) {
    // Yanlış panelde, doğru panele yönlendir
    session_unset();
    session_destroy();
    header('Location: ../login.php');
    exit;
}

// Admin-specific helper fonksiyonlar
function getSystemStats() {
    require_once '../dbconnect.php';
    global $db;
    
    $stats = $db->query("
        SELECT 
            (SELECT COUNT(*) FROM User WHERE role = 'user') as total_users,
            (SELECT COUNT(*) FROM User WHERE role = 'company') as total_companies,
            (SELECT COUNT(*) FROM User WHERE role = 'admin') as total_admins,
            (SELECT COUNT(*) FROM Bus_Company) as total_bus_companies,
            (SELECT COUNT(*) FROM Trips) as total_trips,
            (SELECT COUNT(*) FROM Tickets WHERE status = 'paid') as total_sold_tickets,
            (SELECT SUM(price) FROM Tickets t JOIN Trips tr ON t.trip_id = tr.id WHERE t.status = 'paid') as total_revenue
    ")->fetch(PDO::FETCH_ASSOC);
    
    return $stats;
}

// Son kayıt olan kullanıcılar
function getRecentUsers($limit = 10) {
    require_once '../dbconnect.php';
    global $db;
    
    $stmt = $db->prepare("
        SELECT id, full_name, email, role, balance, created_at 
        FROM User 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Tüm firmalar
function getAllCompanies() {
    require_once '../dbconnect.php';
    global $db;
    
    return $db->query("
        SELECT bc.*, 
               (SELECT COUNT(*) FROM Trips WHERE company_id = bc.id) as trip_count,
               (SELECT full_name FROM User WHERE company_id = bc.id AND role = 'company' LIMIT 1) as admin_name
        FROM Bus_Company bc
        ORDER BY bc.name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

// Login duration helper
function getLoginDuration() {
    if (isset($_SESSION['login_time'])) {
        $duration = time() - $_SESSION['login_time'];
        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        $seconds = $duration % 60;
        
        if ($hours > 0) {
            return "{$hours} saat {$minutes} dakika";
        } elseif ($minutes > 0) {
            return "{$minutes} dakika {$seconds} saniye";
        } else {
            return "{$seconds} saniye";
        }
    }
    return "Bilinmiyor";
}
?>