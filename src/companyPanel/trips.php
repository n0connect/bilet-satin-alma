<?php
/**
 * @file companyPanel/trips.php
 * @brief Sefer Yönetimi (CRUD)
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 *
 * @security validateFloat(), validateInteger(), validateDateTime()
 */

require_once 'auth.php';
require_once '../dbconnect.php';
require_once '../889b1769-5f97-4b94-ac58-69877e948de7/SecurityModule.php';

$action = SecurityModule::sanitize($_GET['action'] ?? 'list', SecurityModule::MODE_TEXT);
$trip_id = SecurityModule::pass($_GET['id'] ?? null, SecurityModule::MODE_PASSTHROUGH);

// Sefer Ekleme
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_trip'])) {

    // Input validation
    $departure_city = SecurityModule::sanitize($_POST['departure_city'] ?? '', SecurityModule::MODE_TEXT);
    $destination_city = SecurityModule::sanitize($_POST['destination_city'] ?? '', SecurityModule::MODE_TEXT);
    $departure_time = SecurityModule::pass($_POST['departure_time'] ?? '', SecurityModule::MODE_PASSTHROUGH);
    $price = SecurityModule::pass($_POST['price'] ?? 0, SecurityModule::MODE_PASSTHROUGH);
    $capacity = SecurityModule::pass($_POST['capacity'] ?? 0, SecurityModule::MODE_PASSTHROUGH);

    // Validation checks
    if (!SecurityModule::validateFloat($price, 0, 10000)) {
        header('Location: trips.php?error=invalid_price');
        exit;
    }

    if (!SecurityModule::validateInteger($capacity, 1, 60)) {
        header('Location: trips.php?error=invalid_capacity');
        exit;
    }

    if (!SecurityModule::validateDateTime($departure_time, 'Y-m-d\TH:i')) {
        header('Location: trips.php?error=invalid_datetime');
        exit;
    }

    $stmt = $db->prepare("
        INSERT INTO Trips (id, company_id, departure_city, destination_city, departure_time, actual_time, price, capacity)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $trip_id = bin2hex(random_bytes(16)); // 32 karakterlik UUID

    // Biniş zamanı = Kalkış zamanından 30 dakika önce (otomatik hesapla)
    $departure = new DateTime($departure_time);
    $actual = clone $departure;
    $actual->modify('-30 minutes');

    $stmt->execute([
        $trip_id,
        $_SESSION['company_id'],
        $departure_city,
        $destination_city,
        $departure->format('Y-m-d H:i:s'),
        $actual->format('Y-m-d H:i:s'),
        $price,
        $capacity
    ]);
    header('Location: trips.php?success=added');
    exit;
}

// Sefer Güncelleme
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_trip'])) {

    // Input validation
    $update_trip_id = SecurityModule::pass($_POST['trip_id'] ?? '', SecurityModule::MODE_PASSTHROUGH);
    $departure_city = SecurityModule::sanitize($_POST['departure_city'] ?? '', SecurityModule::MODE_TEXT);
    $destination_city = SecurityModule::sanitize($_POST['destination_city'] ?? '', SecurityModule::MODE_TEXT);
    $departure_time = SecurityModule::pass($_POST['departure_time'] ?? '', SecurityModule::MODE_PASSTHROUGH);
    $price = SecurityModule::pass($_POST['price'] ?? 0, SecurityModule::MODE_PASSTHROUGH);
    $capacity = SecurityModule::pass($_POST['capacity'] ?? 0, SecurityModule::MODE_PASSTHROUGH);

    // Validation checks
    if (!SecurityModule::validateFloat($price, 0, 10000)) {
        header('Location: trips.php?error=invalid_price');
        exit;
    }

    if (!SecurityModule::validateInteger($capacity, 1, 60)) {
        header('Location: trips.php?error=invalid_capacity');
        exit;
    }

    if (!SecurityModule::validateDateTime($departure_time, 'Y-m-d\TH:i')) {
        header('Location: trips.php?error=invalid_datetime');
        exit;
    }

    $stmt = $db->prepare("
        UPDATE Trips
        SET departure_city = ?, destination_city = ?, departure_time = ?, actual_time = ?, price = ?, capacity = ?
        WHERE id = ? AND company_id = ?
    ");

    // Biniş zamanı = Kalkış zamanından 30 dakika önce (otomatik hesapla)
    $departure = new DateTime($departure_time);
    $actual = clone $departure;
    $actual->modify('-30 minutes');

    $stmt->execute([
        $departure_city,
        $destination_city,
        $departure->format('Y-m-d H:i:s'),
        $actual->format('Y-m-d H:i:s'),
        $price,
        $capacity,
        $update_trip_id,
        $_SESSION['company_id']
    ]);
    header('Location: trips.php?success=updated');
    exit;
}

// Sefer Silme
if($action == 'delete' && $trip_id) {
    $stmt = $db->prepare("DELETE FROM Trips WHERE id = ? AND company_id = ?");
    $stmt->execute([$trip_id, $_SESSION['company_id']]);
    header('Location: trips.php?success=deleted');
    exit;
}

// Düzenleme için sefer bilgilerini çek
$edit_trip = null;
if($action == 'edit' && $trip_id) {
    $stmt = $db->prepare("SELECT * FROM Trips WHERE id = ? AND company_id = ?");
    $stmt->execute([$trip_id, $_SESSION['company_id']]);
    $edit_trip = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Tüm seferleri listele
$stmt = $db->prepare("
    SELECT
        t.*,
        (SELECT COUNT(*) FROM Tickets WHERE trip_id = t.id) as sold_tickets
    FROM Trips t
    WHERE company_id = ?
    ORDER BY departure_time DESC
");
$stmt->execute([$_SESSION['company_id']]);
$all_trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cities = ['İstanbul', 'Ankara', 'İzmir', 'Antalya', 'Bursa', 'Adana', 'Konya', 'Gaziantep', 'Mersin', 'Kayseri', 'Eskişehir', 'Diyarbakır', 'Samsun', 'Trabzon'];
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sefer Yönetimi - <?= wafReflect($_SESSION['company_name']) ?></title>
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
                <h2>🚌 Sefer Yönetimi</h2>
                <a href="dashboard.php" class="btn btn-outline-secondary">← Dashboard</a>
            </div>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php 
                        if($_GET['success'] == 'added') echo 'Sefer başarıyla eklendi!';
                        if($_GET['success'] == 'updated') echo 'Sefer başarıyla güncellendi!';
                        if($_GET['success'] == 'deleted') echo 'Sefer başarıyla silindi!';
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Sefer Ekleme/Düzenleme Formu -->
            <?php if($action == 'add' || $action == 'edit'): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?= $action == 'add' ? 'Yeni Sefer Ekle' : 'Sefer Düzenle' ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php if($action == 'edit'): ?>
                                <input type="hidden" name="trip_id" value="<?= SecurityModule::reflect($edit_trip['id']); ?>">
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Kalkış Şehri</label>
                                    <select name="departure_city" class="form-select" required>
                                        <option value="">Seçin</option>
                                        <?php foreach($cities as $city): ?>
                                            <option value="<?= $city ?>" <?= ($edit_trip && $edit_trip['departure_city'] == $city) ? 'selected' : '' ?>>
                                                <?= $city ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Varış Şehri</label>
                                    <select name="destination_city" class="form-select" required>
                                        <option value="">Seçin</option>
                                        <?php foreach($cities as $city): ?>
                                            <option value="<?= $city ?>" <?= ($edit_trip && $edit_trip['destination_city'] == $city) ? 'selected' : '' ?>>
                                                <?= $city ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Kalkış Zamanı</label>
                                    <input type="datetime-local" name="departure_time" class="form-control"
                                           value="<?= $edit_trip ? SecurityModule::reflect(date('Y-m-d\TH:i', strtotime($edit_trip['departure_time']))) : '' ?>" required>
                                    <small class="text-muted">Biniş zamanı otomatik olarak 30 dakika öncesi ayarlanacak</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Fiyat (TL)</label>
                                    <input type="number" name="price" class="form-control" step="0.01"
                                           value="<?= SecurityModule::reflect($edit_trip['price'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Kapasite</label>
                                    <input type="number" name="capacity" class="form-control" min="1" max="60"
                                           value="<?= SecurityModule::reflect($edit_trip['capacity'] ?? 45); ?>" required>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" name="<?= $action == 'add' ? 'add_trip' : 'update_trip' ?>" class="btn btn-primary">
                                    <?= $action == 'add' ? 'Sefer Ekle' : 'Güncelle' ?>
                                </button>
                                <a href="trips.php" class="btn btn-secondary">İptal</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Sefer Listesi -->
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white d-flex justify-content-between">
                    <h5 class="mb-0">Tüm Seferler (<?= SecurityModule::reflect(count($all_trips)); ?>)</h5>
                    <?php if($action != 'add'): ?>
                        <a href="trips.php?action=add" class="btn btn-light btn-sm">+ Yeni Sefer</a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Güzergah</th>
                                    <th>Kalkış Zamanı</th>
                                    <th>Fiyat</th>
                                    <th>Doluluk</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($all_trips as $trip):
                                    $is_past = strtotime($trip['departure_time']) < time();
                                ?>
                                <tr class="<?= $is_past ? 'table-secondary' : '' ?>">
                                    <td>
                                        <strong><?= SecurityModule::reflect($trip['departure_city']); ?></strong>
                                        <span class="text-muted">→</span>
                                        <strong><?= SecurityModule::reflect($trip['destination_city']); ?></strong>
                                    </td>
                                    <td><?= SecurityModule::reflect(date('d.m.Y H:i', strtotime($trip['departure_time']))); ?></td>
                                    <td><?= SecurityModule::reflect(number_format($trip['price'], 0)); ?> ₺</td>
                                    <td><?= SecurityModule::reflect($trip['sold_tickets']); ?> / <?= SecurityModule::reflect($trip['capacity']); ?></td>
                                    <td>
                                        <span class="badge bg-<?= $is_past ? 'secondary' : 'success' ?>">
                                            <?= $is_past ? 'Geçmiş' : 'Aktif' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="trip_detail.php?id=<?= SecurityModule::reflect($trip['id']); ?>" class="btn btn-outline-info">Detay</a>
                                            <?php if(!$is_past): ?>
                                                <a href="trips.php?action=edit&id=<?= SecurityModule::reflect($trip['id']); ?>" class="btn btn-outline-primary">Düzenle</a>
                                                <a href="trips.php?action=delete&id=<?= SecurityModule::reflect($trip['id']); ?>"
                                                   class="btn btn-outline-danger"
                                                   onclick="return confirm('Bu seferi silmek istediğinize emin misiniz?')">
                                                   Sil
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>