<?php
/**
 * @file companyPanel/dashboard.php
 * @brief Firma Dashboard - Sefer İstatistikleri
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 */

// companyPanel/dashboard.php
require_once 'auth.php';
require_once '../dbconnect.php';
require_once '../889b1769-5f97-4b94-ac58-69877e948de7/SecurityModule.php';

// Stats otomatik yüklenir
$stats = getCompanyStats();

// Aktif seferler
$activeTrips = $db->prepare("
    SELECT
        t.id,
        t.departure_city,
        t.destination_city,
        t.departure_time,
        t.price,
        t.capacity,
        (SELECT COUNT(*) FROM Tickets WHERE trip_id = t.id) as sold_tickets
    FROM Trips t
    WHERE company_id = ? AND datetime(departure_time) > datetime('now')
    ORDER BY departure_time ASC
    LIMIT 10
");
$activeTrips->execute([$_SESSION['company_id']]);
$trips = $activeTrips->fetchAll(PDO::FETCH_ASSOC);

// Son satılan biletler
$recentTickets = $db->prepare("
    SELECT
        tk.id,
        tk.seat_number,
        tk.status,
        tk.created_at,
        u.full_name,
        u.email,
        t.departure_city,
        t.destination_city,
        t.departure_time
    FROM Tickets tk
    JOIN User u ON tk.user_id = u.id
    JOIN Trips t ON tk.trip_id = t.id
    WHERE t.company_id = ?
    ORDER BY tk.created_at DESC
    LIMIT 5
");
$recentTickets->execute([$_SESSION['company_id']]);
$recent_tickets = $recentTickets->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma Paneli - <?= wafPass($_SESSION['company_name']) ?></title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <style>
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .trip-row:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-light">

<div class="d-flex">

    <!-- Sol Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark flex-column align-items-start p-3 vh-100" style="width: 240px; position: fixed;">
        <a class="navbar-brand mb-4" href="dashboard.php">
            <?=  wafPass($_SESSION['company_name'] ) ?>
        </a>
        <ul class="navbar-nav flex-column w-100">
            <li class="nav-item mb-2">
                <a class="nav-link active" href="dashboard.php">📊 Dashboard</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link" href="trips.php">🚌 Sefer Yönetimi</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link" href="tickets.php">🎟️ Satılan Biletler</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link" href="coupons.php">🎫 Kupon Yönetimi</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link" href="passengers.php">👥 Yolcu Listesi</a>
            </li>
            <li class="nav-item mt-auto">
                <a class="nav-link text-danger" href="logout.php">🚪 Çıkış Yap</a>
            </li>
        </ul>
    </nav>

    <!-- İçerik Alanı -->
    <div class="flex-grow-1 p-4" style="margin-left: 240px;">
        <div class="container-fluid">
            <h1 class="mb-3">Hoşgeldin, <?=  wafPass($_SESSION['full_name'] ) ?></h1>
            <p class="text-muted">Giriş Zamanı: <?=  wafPass($_SESSION['login_datetime'] ) ?> • Online Süresi: <?=  wafPass(getLoginDuration() ) ?></p>

            <hr>

            <!-- İstatistikler -->
            <div class="row text-center mb-4">
                <div class="col-md-3">
                    <div class="card shadow-sm stat-card">
                        <div class="card-body">
                            <h5 class="card-title text-primary">Toplam Sefer</h5>
                            <p class="display-6"><?=  wafPass($stats['total_trips'] ?? 0 ) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm stat-card">
                        <div class="card-body">
                            <h5 class="card-title text-success">Satılan Bilet</h5>
                            <p class="display-6"><?=  wafPass($stats['total_tickets'] ?? 0 ) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm stat-card">
                        <div class="card-body">
                            <h5 class="card-title text-info">Aktif Sefer</h5>
                            <p class="display-6"><?=  wafPass(count($trips) ) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm stat-card">
                        <div class="card-body">
                            <h5 class="card-title text-warning">Toplam Gelir</h5>
                            <p class="display-6"><?=  wafPass(number_format($stats['total_revenue'] ?? 0, 0) ) ?> ₺</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aktif Seferler -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white d-flex justify-content-between">
                            <h5 class="mb-0">Aktif Seferler</h5>
                            <a href="trips.php?action=add" class="btn btn-light btn-sm">+ Yeni Sefer</a>
                        </div>
                        <div class="card-body">
                            <?php if (count($trips) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Güzergah</th>
                                                <th>Tarih/Saat</th>
                                                <th>Fiyat</th>
                                                <th>Doluluk</th>
                                                <th>İşlem</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($trips as $trip): 
                                                $occupancy = (($trip['sold_tickets'] / $trip['capacity'] ) * 100);
                                                $badge_class = $occupancy > 80 ? 'danger' : ($occupancy > 50 ? 'warning' : 'success');
                                            ?>
                                            <tr class="trip-row" onclick="window.location.href='trip_detail.php?id=<?=  wafPass($trip['id'] ) ?>'">
                                                <td>
                                                    <strong><?=  wafPass($trip['departure_city'] ) ?></strong>
                                                    <span class="text-muted">→</span>
                                                    <strong><?=  wafPass($trip['destination_city'] ) ?></strong>
                                                </td>
                                                <td><?=  wafPass(date('d.m.Y H:i', strtotime($trip['departure_time'])) ) ?></td>
                                                <td><span class="badge bg-info"><?=  wafPass(number_format($trip['price'], 0) ) ?> ₺</span></td>
                                                <td>
                                                    <span class="badge bg-<?= $badge_class ?>">
                                                        <?=  wafPass($trip['sold_tickets'] ) ?>/<?=  wafPass($trip['capacity'] ) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="trip_detail.php?id=<?=  wafPass($trip['id'] ) ?>" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation()">Detay</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="trips.php" class="btn btn-outline-primary">Tüm Seferleri Gör</a>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center py-4">Aktif sefer bulunmamaktadır.</p>
                                <div class="text-center">
                                    <a href="trips.php?action=add" class="btn btn-primary">İlk Seferi Ekle</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Son Satılan Biletler -->
                <div class="col-md-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Son Satışlar</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($recent_tickets) > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach($recent_tickets as $ticket): ?>
                                        <div class="list-group-item px-0">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?=  wafPass($ticket['full_name'] ) ?></h6>
                                                    <small class="text-muted">
                                                        <?=  wafPass($ticket['departure_city'] ) ?> → 
                                                        <?=  wafPass($ticket['destination_city'] ) ?>
                                                    </small>
                                                    <br>
                                                    <small class="text-muted">Koltuk: <?=  wafPass($ticket['seat_number'] ) ?></small>
                                                </div>
                                                <span class="badge bg-<?= $ticket['status'] == 'active' ? 'success' : 'secondary' ?>">
                                                    <?=  wafPass(ucfirst($ticket['status']) ) ?>
                                                </span>
                                            </div>
                                            <small class="text-muted"><?=  wafPass(date('d.m.Y H:i', strtotime($ticket['created_at'])) ) ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="tickets.php" class="btn btn-outline-success btn-sm">Tüm Biletler</a>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center py-3">Henüz satış yok</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>