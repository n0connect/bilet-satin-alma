<?php
/**
 * @file companyPanel/trip_detail.php
 * @brief Firma Sefer Detayı
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 *
 * @security UUID validation, regex kontrolü
 */

require_once 'auth.php';
require_once '../dbconnect.php';
require_once '../889b1769-5f97-4b94-ac58-69877e948de7/SecurityModule.php';

$trip_id = SecurityModule::pass($_GET['id'] ?? null, SecurityModule::MODE_PASSTHROUGH);

if ($trip_id == null) {
    header('Location: dashboard.php');
    exit;
}

// Trip ID validasyonu
if (!$trip_id || !is_string($trip_id) || !preg_match('/^[a-f0-9]{32}$/i', $trip_id)) {
    header('Location: trips.php');
    exit;
}

// Bilet iptal işlemi
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_ticket'])) {
    $ticket_id = $_POST['ticket_id'] ?? null;

    if ($trip_id == null) {
    header('Location: dashboard.php');
    exit;
    }
    
    // Ticket ID validasyonu
    if (!$ticket_id || !is_string($ticket_id) || !preg_match('/^[a-f0-9]{32}$/i', $ticket_id)) {
        header("Location: trip_detail.php?id=$trip_id&error=invalid_ticket");
        exit;
    }
    
    // Biletin var olup olmadığını ve bu firmaya ait olup olmadığını kontrol et
    $verify_stmt = $db->prepare("
        SELECT tk.id, tk.user_id, t.price, t.company_id
        FROM Tickets tk
        JOIN Trips t ON tk.trip_id = t.id
        WHERE tk.id = ? AND tk.trip_id = ? AND tk.status = 'paid' AND t.company_id = ?
    ");
    $trip_id = SecurityModule::pass($trip_id, SecurityModule::MODE_PASSTHROUGH);
    $ticket_id = SecurityModule::pass($ticket_id, SecurityModule::MODE_PASSTHROUGH);

    $verify_stmt->execute([$ticket_id, $trip_id, SecurityModule::pass($_SESSION['company_id'], SecurityModule::MODE_PASSTHROUGH)]);
    $ticket_data = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$ticket_data) {
        header("Location: trip_detail.php?id=$trip_id&error=ticket_not_found");
        exit;
    }
    
    // Transaction başlat
    $db->beginTransaction();
    
    try {
        // Bileti iptal et
        $cancel_stmt = $db->prepare("UPDATE Tickets SET status = 'cancelled' WHERE id = ?");
        $cancel_stmt->execute([$ticket_id]);
        
        // Para iadesini yap
        $refund_stmt = $db->prepare("UPDATE User SET balance = balance + ? WHERE id = ?");
        $refund_stmt->execute([$ticket_data['price'], $ticket_data['user_id']]);
        
        // Commit
        $db->commit();
        
        header("Location: trip_detail.php?id=$trip_id&success=cancelled");
        exit;
    } catch (Exception $e) {
        // Rollback
        $db->rollBack();
        header("Location: trip_detail.php?id=$trip_id&error=cancel_failed");
        exit;
    }
}

// Sefer bilgilerini çek (firma kontrolü ile)
$stmt = $db->prepare("
    SELECT t.*, bc.name as company_name
    FROM Trips t
    JOIN Bus_Company bc ON t.company_id = bc.id
    WHERE t.id = ? AND t.company_id = ?
");
$stmt->execute([$trip_id, $_SESSION['company_id']]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$trip) {
    header('Location: trips.php');
    exit;
}

// Bu seferin biletlerini çek (cinsiyet bilgisi ile)
$tickets_stmt = $db->prepare("
    SELECT 
        tk.id,
        tk.seat_number,
        tk.status,
        tk.created_at,
        u.full_name,
        u.email,
        u.id as user_id,
        u.role as gender
    FROM Tickets tk
    JOIN User u ON tk.user_id = u.id
    WHERE tk.trip_id = ?
    ORDER BY tk.seat_number ASC
");
$tickets_stmt->execute([$trip_id]);
$tickets = $tickets_stmt->fetchAll(PDO::FETCH_ASSOC);

// Koltuk durumunu map'e çevir
$seat_map = [];
foreach($tickets as $ticket) {
    $seat_map[$ticket['seat_number']] = [
        'status' => $ticket['status'],
        'gender' => $ticket['gender']
    ];
}

$sold_count = count(array_filter($tickets, fn($t) => $t['status'] == 'active'));
$cancelled_count = count(array_filter($tickets, fn($t) => $t['status'] == 'cancelled'));
$available_seats = $trip['capacity'] - $sold_count;
$revenue = $sold_count * $trip['price'];
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sefer Detayı - <?= SecurityModule::reflect($_SESSION['company_name']); ?></title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <style>
        .seat-map {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 500px;
            margin: 0 auto;
        }
        .seat-row {
            display: grid;
            grid-template-columns: 45px 45px 20px 45px 45px;
            gap: 8px;
            justify-content: center;
        }
        .seat {
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            position: relative;
        }
        .seat::before {
            content: '';
            position: absolute;
            top: -3px;
            left: 50%;
            transform: translateX(-50%);
            width: 60%;
            height: 2px;
            background: currentColor;
            border-radius: 1px;
            opacity: 0.5;
        }
        .bus-front {
            text-align: center;
            padding: 12px;
            background: linear-gradient(135deg, #607D8B, #455A64);
            color: white;
            border-radius: 10px 10px 0 0;
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 10px;
        }
        .bus-aisle {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #BDBDBD;
            font-size: 18px;
        }
        .seat-sold-male {
            background-color: #BBDEFB;
            color: #1976D2;
            border-color: #1976D2;
        }
        .seat-sold-female {
            background-color: #F8BBD0;
            color: #C2185B;
            border-color: #C2185B;
        }
        .seat-available {
            background-color: #E8F5E9;
            color: #2E7D32;
            border-color: #4CAF50;
        }
        .seat-cancelled {
            background-color: #E0E0E0;
            color: #757575;
            border-color: #9E9E9E;
            opacity: 0.6;
        }
    </style>
</head>
<body class="bg-light">

<div class="d-flex">
    <!-- Sol Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark flex-column align-items-start p-3 vh-100" style="width: 240px; position: fixed;">
        <a class="navbar-brand mb-4" href="dashboard.php">
            <?= SecurityModule::reflect($_SESSION['company_name']); ?>
        </a>
        <ul class="navbar-nav flex-column w-100">
            <li class="nav-item mb-2">
                <a class="nav-link" href="dashboard.php">📊 Dashboard</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link active" href="trips.php">🚌 Sefer Yönetimi</a>
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
                <a class="nav-link text-danger" href="../logout.php">🚪 Çıkış Yap</a>
            </li>
        </ul>
    </nav>

    <!-- İçerik -->
    <div class="flex-grow-1 p-4" style="margin-left: 240px;">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>🚌 Sefer Detayı</h2>
                <a href="trips.php" class="btn btn-outline-secondary">← Seferlere Dön</a>
            </div>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    Bilet başarıyla iptal edildi ve para iadesi yapıldı!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php 
                        if($_GET['error'] == 'invalid_ticket') echo 'Geçersiz bilet ID!';
                        elseif($_GET['error'] == 'ticket_not_found') echo 'Bilet bulunamadı veya zaten iptal edilmiş!';
                        elseif($_GET['error'] == 'cancel_failed') echo 'İptal işlemi başarısız oldu!';
                        else echo 'Bir hata oluştu!';
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Sefer Bilgileri -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <?= SecurityModule::reflect($trip['departure_city']); ?> →
                                <?= SecurityModule::reflect($trip['destination_city']); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>🕐 Kalkış Zamanı:</strong> <?= SecurityModule::reflect(date('d.m.Y H:i', strtotime($trip['departure_time']))); ?></p>
                                    <p><strong>🚏 Biniş Zamanı:</strong> <?= SecurityModule::reflect(date('d.m.Y H:i', strtotime($trip['actual_time']))); ?></p>
                                    <p><strong>💰 Fiyat:</strong> <?= SecurityModule::reflect(number_format($trip['price'], 0)); ?> ₺</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>💺 Kapasite:</strong> <?= SecurityModule::reflect($trip['capacity']); ?> kişi</p>
                                    <p><strong>✅ Satılan:</strong> <?= SecurityModule::reflect($sold_count); ?> bilet</p>
                                    <p><strong>🔴 İptal:</strong> <?= SecurityModule::reflect($cancelled_count); ?> bilet</p>
                                    <p><strong>🟢 Boş:</strong> <?= SecurityModule::reflect($available_seats); ?> koltuk</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- İstatistikler -->
                <div class="col-md-4">
                    <div class="card shadow-sm mb-3">
                        <div class="card-body text-center">
                            <h6 class="text-muted">Doluluk Oranı</h6>
                            <h2 class="text-primary"><?= SecurityModule::reflect(round(($sold_count / $trip['capacity']) * 100)); ?>%</h2>
                        </div>
                    </div>
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <h6 class="text-muted">Toplam Gelir</h6>
                            <h2 class="text-success"><?= SecurityModule::reflect(number_format($revenue, 0)); ?> ₺</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Koltuk Haritası -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Koltuk Haritası</h5>
                </div>
                <div class="card-body">
                    <div class="seat-map">
                        <div class="bus-front">🚌 ŞOFÖR</div>
                        
                        <?php 
                        $seats_per_row = 4;
                        $rows = ceil($trip['capacity'] / $seats_per_row);
                        
                        for($row = 0; $row < $rows; $row++):
                            $seat1 = ($row * $seats_per_row) + 1;
                            $seat2 = ($row * $seats_per_row) + 2;
                            $seat3 = ($row * $seats_per_row) + 3;
                            $seat4 = ($row * $seats_per_row) + 4;
                        ?>
                            <div class="seat-row">
                                <?php 
                                // Sol pencere
                                if($seat1 <= $trip['capacity']):
                                    $seat_info = $seat_map[$seat1] ?? null;
                                    if($seat_info) {
                                        if($seat_info['status'] == 'active') {
                                            $class = 'seat-sold-' . $seat_info['gender'];
                                            $icon = $seat_info['gender'] == 'user' ? '♂' : '♀';
                                        } else {
                                            $class = 'seat-cancelled';
                                            $icon = '✗';
                                        }
                                    } else {
                                        $class = 'seat-available';
                                        $icon = '';
                                    }
                                ?>
                                    <div class="seat <?= $class ?>">
                                        <?= $seat1 ?><?= $icon ?>
                                    </div>
                                <?php else: ?>
                                    <div></div>
                                <?php endif; ?>
                                
                                <?php 
                                // Sol koridor
                                if($seat2 <= $trip['capacity']):
                                    $seat_info = $seat_map[$seat2] ?? null;
                                    if($seat_info) {
                                        if($seat_info['status'] == 'active') {
                                            $class = 'seat-sold-' . $seat_info['gender'];
                                            $icon = $seat_info['gender'] == 'user' ? '♂' : '♀';
                                        } else {
                                            $class = 'seat-cancelled';
                                            $icon = '✗';
                                        }
                                    } else {
                                        $class = 'seat-available';
                                        $icon = '';
                                    }
                                ?>
                                    <div class="seat <?= $class ?>">
                                        <?= $seat2 ?><?= $icon ?>
                                    </div>
                                <?php else: ?>
                                    <div></div>
                                <?php endif; ?>
                                
                                <!-- Koridor -->
                                <div class="bus-aisle">│</div>
                                
                                <?php 
                                // Sağ koridor
                                if($seat3 <= $trip['capacity']):
                                    $seat_info = $seat_map[$seat3] ?? null;
                                    if($seat_info) {
                                        if($seat_info['status'] == 'active') {
                                            $class = 'seat-sold-' . $seat_info['gender'];
                                            $icon = $seat_info['gender'] == 'user' ? '♂' : '♀';
                                        } else {
                                            $class = 'seat-cancelled';
                                            $icon = '✗';
                                        }
                                    } else {
                                        $class = 'seat-available';
                                        $icon = '';
                                    }
                                ?>
                                    <div class="seat <?= $class ?>">
                                        <?= $seat3 ?><?= $icon ?>
                                    </div>
                                <?php else: ?>
                                    <div></div>
                                <?php endif; ?>
                                
                                <?php 
                                // Sağ pencere
                                if($seat4 <= $trip['capacity']):
                                    $seat_info = $seat_map[$seat4] ?? null;
                                    if($seat_info) {
                                        if($seat_info['status'] == 'active') {
                                            $class = 'seat-sold-' . $seat_info['gender'];
                                            $icon = $seat_info['gender'] == 'user' ? '♂' : '♀';
                                        } else {
                                            $class = 'seat-cancelled';
                                            $icon = '✗';
                                        }
                                    } else {
                                        $class = 'seat-available';
                                        $icon = '';
                                    }
                                ?>
                                    <div class="seat <?= $class ?>">
                                        <?= $seat4 ?><?= $icon ?>
                                    </div>
                                <?php else: ?>
                                    <div></div>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <div class="text-center mt-3">
                        <span class="badge bg-success me-2">🟢 Boş</span>
                        <span class="badge" style="background: #1976D2;">♂ Erkek</span>
                        <span class="badge ms-2" style="background: #C2185B;">♀ Kadın</span>
                        <span class="badge bg-secondary ms-2">✗ İptal</span>
                    </div>
                </div>
            </div>

            <!-- Yolcu Listesi -->
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Yolcu Listesi (<?= count($tickets) ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if(count($tickets) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Koltuk</th>
                                        <th>Yolcu Adı</th>
                                        <th>Email</th>
                                        <th>Satın Alma</th>
                                        <th>Durum</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($tickets as $ticket): ?>
                                    <tr>
                                        <td><span class="badge bg-primary"><?= SecurityModule::reflect($ticket['seat_number']); ?></span></td>
                                        <td><?= SecurityModule::reflect($ticket['full_name']); ?></td>
                                        <td><?= SecurityModule::reflect($ticket['email']); ?></td>
                                        <td><?= SecurityModule::reflect(date('d.m.Y H:i', strtotime($ticket['created_at']))); ?></td>
                                        <td>
                                            <span class="badge bg-<?= $ticket['status'] == 'active' ? 'success' : 'secondary' ?>">
                                                <?= SecurityModule::reflect(ucfirst($ticket['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($ticket['status'] == 'active'): ?>
                                                <form method="POST" style="display:inline;"
                                                      onsubmit="return confirm('Bu bileti iptal etmek istediğinize emin misiniz? Para iadesi yapılacaktır.')">
                                                    <input type="hidden" name="ticket_id" value="<?= SecurityModule::reflect($ticket['id']); ?>">
                                                    <button type="submit" name="cancel_ticket" class="btn btn-sm btn-outline-danger">
                                                        İptal Et
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">İptal edildi</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted py-4">Bu sefer için henüz bilet satılmamış.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>