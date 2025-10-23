<?php
/**
 * @file companyPanel/tickets.php
 * @brief Satılan Biletler Listesi
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 */

require_once 'auth.php';
require_once '../dbconnect.php';
require_once '../889b1769-5f97-4b94-ac58-69877e948de7/SecurityModule.php';

// Filtreleme
$filter = SecurityModule::sanitize($_GET['filter'] ?? 'all', SecurityModule::MODE_TEXT);
$search = SecurityModule::sanitize($_GET['search'] ?? '', SecurityModule::MODE_TEXT);

// Biletleri çek
$query = "
    SELECT 
        tk.id,
        tk.seat_number,
        tk.status,
        tk.created_at,
        u.full_name,
        u.email,
        t.departure_city,
        t.destination_city,
        t.departure_time,
        t.price,
        t.id as trip_id
    FROM Tickets tk
    JOIN User u ON tk.user_id = u.id
    JOIN Trips t ON tk.trip_id = t.id
    WHERE t.company_id = ?
";

$params = [$_SESSION['company_id']];

if($filter == 'active') {
    $query .= " AND tk.status = 'active'";
} elseif($filter == 'cancelled') {
    $query .= " AND tk.status = 'cancelled'";
}

if(!empty($search)) {
    $query .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR t.departure_city LIKE ? OR t.destination_city LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$query .= " ORDER BY tk.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_tickets = count($tickets);
$active_tickets = count(array_filter($tickets, fn($t) => $t['status'] == 'active'));
$cancelled_tickets = count(array_filter($tickets, fn($t) => $t['status'] == 'cancelled'));
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Satılan Biletler - <?= SecurityModule::reflect($_SESSION['company_name']); ?></title>
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
                <a class="nav-link active" href="tickets.php">🎟️ Satılan Biletler</a>
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

    <!-- İçerik -->
    <div class="flex-grow-1 p-4" style="margin-left: 240px;">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>🎟️ Satılan Biletler</h2>
                <a href="dashboard.php" class="btn btn-outline-secondary">← Dashboard</a>
            </div>

            <!-- İstatistikler -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card shadow-sm text-center">
                        <div class="card-body">
                            <h6 class="text-muted">Toplam Bilet</h6>
                            <h2 class="text-primary"><?= $total_tickets ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm text-center">
                        <div class="card-body">
                            <h6 class="text-muted">Aktif Biletler</h6>
                            <h2 class="text-success"><?= $active_tickets ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm text-center">
                        <div class="card-body">
                            <h6 class="text-muted">İptal Edilenler</h6>
                            <h2 class="text-danger"><?= $cancelled_tickets ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtreler -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Durum Filtresi</label>
                            <select name="filter" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?= $filter == 'all' ? 'selected' : '' ?>>Tümü</option>
                                <option value="active" <?= $filter == 'active' ? 'selected' : '' ?>>Aktif</option>
                                <option value="cancelled" <?= $filter == 'cancelled' ? 'selected' : '' ?>>İptal</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Arama</label>
                            <input type="text" name="search" class="form-control"
                                   placeholder="Yolcu adı, email veya şehir ara..."
                                   value="<?= SecurityModule::reflect($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">Ara</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Bilet Listesi -->
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Bilet Listesi</h5>
                </div>
                <div class="card-body">
                    <?php if(count($tickets) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Bilet ID</th>
                                        <th>Yolcu</th>
                                        <th>Güzergah</th>
                                        <th>Kalkış</th>
                                        <th>Koltuk</th>
                                        <th>Fiyat</th>
                                        <th>Satın Alma</th>
                                        <th>Durum</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($tickets as $ticket): ?>
                                    <tr>
                                        <td><code><?= SecurityModule::reflect(substr($ticket['id'], 0, 32)); ?></code></td>
                                        <td>
                                            <div><?= SecurityModule::reflect($ticket['full_name']); ?></div>
                                            <small class="text-muted"><?= SecurityModule::reflect($ticket['email']); ?></small>
                                        </td>
                                        <td>
                                            <?= SecurityModule::reflect($ticket['departure_city']); ?> →
                                            <?= SecurityModule::reflect($ticket['destination_city']); ?>
                                        </td>
                                        <td><?= SecurityModule::reflect(date('d.m.Y H:i', strtotime($ticket['departure_time']))); ?></td>
                                        <td><span class="badge bg-primary"><?= SecurityModule::reflect($ticket['seat_number']); ?></span></td>
                                        <td><?= SecurityModule::reflect(number_format($ticket['price'], 0)); ?> ₺</td>
                                        <td><?= SecurityModule::reflect(date('d.m.Y H:i', strtotime($ticket['created_at']))); ?></td>
                                        <td>
                                            <span class="badge bg-<?= $ticket['status'] == 'active' ? 'success' : 'secondary' ?>">
                                                <?= SecurityModule::reflect(ucfirst($ticket['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="trip_detail.php?id=<?= SecurityModule::reflect($ticket['trip_id']); ?>"
                                               class="btn btn-sm btn-outline-info">
                                               Sefer Detayı
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted py-5">
                            <?= !empty($search) || $filter != 'all' ? 'Filtreye uygun bilet bulunamadı.' : 'Henüz satılan bilet bulunmuyor.' ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>