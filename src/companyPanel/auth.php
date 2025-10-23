<?php
/**
 * @file companyPanel/auth.php
 * @brief Company Authentication Guard
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 */

// companyPanel/auth.php
require_once '../session_helper.php';
require_once '../889b1769-5f97-4b94-ac58-69877e948de7/SecurityModule.php';

// Session başlat
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

// Company rolü kontrolü
if (!hasRole('company')) {
    // Yanlış panelde, doğru panele yönlendir
    header('Location: ' . redirectToPanel());
    exit;
}

// Stats fonksiyonunu burada tanımlayalım
function getCompanyStats() {
    global $db;
    
    if(!isset($_SESSION['company_id'])) {
        return [
            'total_trips' => 0,
            'total_tickets' => 0,
            'total_revenue' => 0
        ];
    }
    
    // Toplam sefer sayısı
    $trips_stmt = $db->prepare("SELECT COUNT(*) FROM Trips WHERE company_id = ?");
    $trips_stmt->execute([$_SESSION['company_id']]);
    $total_trips = $trips_stmt->fetchColumn();
    
    // Toplam satılan bilet
    $tickets_stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM Tickets tk
        JOIN Trips t ON tk.trip_id = t.id
        WHERE t.company_id = ? AND tk.status = 'active'
    ");
    $tickets_stmt->execute([$_SESSION['company_id']]);
    $total_tickets = $tickets_stmt->fetchColumn();
    
    // Toplam gelir
    $revenue_stmt = $db->prepare("
        SELECT SUM(t.price) 
        FROM Tickets tk
        JOIN Trips t ON tk.trip_id = t.id
        WHERE t.company_id = ? AND tk.status = 'active'
    ");
    $revenue_stmt->execute([$_SESSION['company_id']]);
    $total_revenue = $revenue_stmt->fetchColumn() ?? 0;
    
    return [
        'total_trips' => $total_trips,
        'total_tickets' => $total_tickets,
        'total_revenue' => $total_revenue
    ];
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