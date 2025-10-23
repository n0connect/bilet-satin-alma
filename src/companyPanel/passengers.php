<?php
/**
 * @file companyPanel/passengers.php
 * @brief Yolcu Listesi
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 */

require_once 'auth.php';
require_once '../dbconnect.php';
require_once '../889b1769-5f97-4b94-ac58-69877e948de7/SecurityModule.php';

// Yolcu verilerini çek
$stmt = $db->prepare("
    SELECT 
        u.id,
        u.full_name,
        u.email,
        COUNT(tk.id) as total_tickets,
        SUM(CASE WHEN tk.status = 'active' THEN 1 ELSE 0 END) as active_tickets,
        SUM(t.price) as total_spent,
        MAX(tk.created_at) as last_purchase
    FROM User u
    JOIN Tickets tk ON u.id = tk.user_id
    JOIN Trips t ON tk.trip_id = t.id
    WHERE t.company_id = ?
    GROUP BY u.id
    ORDER BY total_tickets DESC
");
$stmt->execute([$_SESSION['company_id']]);
$passengers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// İstatistikler
$total_passengers = count($passengers);
$total_tickets_sold = array_sum(array_column($passengers, 'total_tickets'));
$total_revenue = array_sum(array_column($passengers, 'total_spent'));
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yolcu Listesi - <?= SecurityModule::reflect($_SESSION['company_name']); ?></title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
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
                <a class="nav-link" href="trips.php">🚌 Sefer Yönetimi</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link" href="tickets.php">🎟️ Satılan Biletler</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link" href="coupons.php">🎫 Kupon Yönetimi</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link active" href="passengers.php">👥 Yolcu Listesi</a>
            </li>
            <li class="nav-item mt-auto">
                <a class="nav-link text-danger" href="logout.php">🚪 Çıkış Yap</a>
            </li>
        </ul>
    </nav>

    <!-- İçerik -->
    <div class="flex-grow-1 p-4" style="margin-left: 240px;">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>👥 Yolcu Listesi</h2>
                <a href="dashboard.php" class="btn btn-outline-secondary">← Dashboard</a>
            </div>

            <!-- İstatistikler -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card shadow-sm text-center">
                        <div class="card-body">
                            <h6 class="text-muted">Toplam Yolcu</h6>
                            <h2 class="text-primary"><?= SecurityModule::reflect($total_passengers); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm text-center">
                        <div class="card-body">
                            <h6 class="text-muted">Satılan Bilet</h6>
                            <h2 class="text-success"><?= SecurityModule::reflect($total_tickets_sold); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm text-center">
                        <div class="card-body">
                            <h6 class="text-muted">Toplam Gelir</h6>
                            <h2 class="text-info"><?= SecurityModule::reflect(number_format($total_revenue, 0)); ?> ₺</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Yolcu Listesi -->
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Tüm Yolcular</h5>
                </div>
                <div class="card-body">
                    <?php if(count($passengers) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Yolcu Adı</th>
                                        <th>Email</th>
                                        <th>Toplam Bilet</th>
                                        <th>Aktif Bilet</th>
                                        <th>Harcama</th>
                                        <th>Son Satın Alma</th>
                                        <th>Sadakat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($passengers as $index => $passenger): 
                                        $loyalty_level = $passenger['total_tickets'] >= 10 ? 'VIP' : 
                                                       ($passenger['total_tickets'] >= 5 ? 'Gold' : 'Silver');
                                        $loyalty_badge = $loyalty_level == 'VIP' ? 'danger' : 
                                                       ($loyalty_level == 'Gold' ? 'warning' : 'secondary');
                                    ?>
                                    <tr>
                                        <td>
                                            <?php if($index < 3): ?>
                                                <span class="badge bg-warning me-2">⭐</span>
                                            <?php endif; ?>
                                            <?= SecurityModule::reflect($passenger['full_name']); ?>
                                        </td>
                                        <td><?= SecurityModule::reflect($passenger['email']); ?></td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <?= SecurityModule::reflect($passenger['total_tickets']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">
                                                <?= SecurityModule::reflect($passenger['active_tickets']); ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold"><?= SecurityModule::reflect(number_format($passenger['total_spent'], 0)); ?> ₺</td>
                                        <td><?= SecurityModule::reflect(date('d.m.Y', strtotime($passenger['last_purchase']))); ?></td>
                                        <td>
                                            <span class="badge bg-<?= SecurityModule::reflect($loyalty_badge); ?>">
                                                <?= SecurityModule::reflect($loyalty_level); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php else: ?>
                        <p class="text-center text-muted py-5">Henüz yolcu kaydı bulunmuyor.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>