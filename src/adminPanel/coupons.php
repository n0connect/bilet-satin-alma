<?php
/**
 * @file adminPanel/coupons.php
 * @brief Global Kupon Yönetimi
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

// Kupon ekleme (Admin - Global kupon)
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_coupon'])) {

    $code = strtoupper(trim($_POST['code'] ?? ''));
    $discount = $_POST['discount_amount'] ?? 0;
    $max_uses = $_POST['max_uses'] ?? 0;
    $valid_until = $_POST['valid_until'] ?? '';

    // Kupon kodu formatı kontrolü (sadece harf ve rakam, 4-20 karakter)
    if (!preg_match('/^[A-Z0-9]{4,20}$/', $code)) {
        header('Location: coupons.php?error=invalid_code');
        exit;
    }

    // Discount validation
    if (!SecurityModule::validateFloat($discount, 10, 1000)) {
        header('Location: coupons.php?error=invalid_discount');
        exit;
    }

    // Max uses validation
    if (!SecurityModule::validateInteger($max_uses, 1, 100000)) {
        header('Location: coupons.php?error=invalid_max_uses');
        exit;
    }

    // Date validation
    if (!SecurityModule::validateDateTime($valid_until, 'Y-m-d')) {
        header('Location: coupons.php?error=invalid_date');
        exit;
    }

    $coupon_id = bin2hex(random_bytes(16)); // 32 karakterlik UUID

    // Admin global kupon oluşturur (company_id = NULL)
    $stmt = $db->prepare("
        INSERT INTO Coupons (id, code, discount, company_id, usage_time, expire_date)
        VALUES (?, ?, ?, NULL, ?, ?)
    ");
    $stmt->execute([
        $coupon_id,
        $code,
        $discount,
        $max_uses,
        $valid_until
    ]);

    header('Location: coupons.php?success=added');
    exit;
}

// Kupon silme - Admin tüm kuponları silebilir
if(isset($_GET['delete'])) {
    $delete_id = SecurityModule::pass($_GET['delete'] ?? '', SecurityModule::MODE_PASSTHROUGH);

    $stmt = $db->prepare("DELETE FROM Coupons WHERE id = ?");
    $stmt->execute([$delete_id]);

    header('Location: coupons.php?success=deleted');
    exit;
}

// Tüm kuponları çek (hem global hem firma kuponları)
$stmt = $db->prepare("
    SELECT
        c.*,
        bc.name as company_name,
        (SELECT COUNT(*) FROM User_Coupons WHERE coupon_id = c.id) as total_uses
    FROM Coupons c
    LEFT JOIN Bus_Company bc ON c.company_id = bc.id
    ORDER BY c.created_at DESC
");
$stmt->execute();
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kupon Yönetimi - Admin Paneli</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex">

    <!-- Sol Navbar -->
    <nav class="navbar navbar-expand-lg bg-body-tertiary flex-column align-items-stretch p-3 border-end" style="min-height:100vh; width:260px;">
        <a class="navbar-brand mb-4 text-center fw-bold text-primary" href="dashboard.php">🎫 NoTicket<br>Admin Panel</a>
        <ul class="navbar-nav flex-column">
            <li class="nav-item"><a class="nav-link" href="dashboard.php">📊 Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="companies.php">🚌 Firmalar</a></li>
            <li class="nav-item"><a class="nav-link" href="company_admins.php">👥 Firma Adminleri</a></li>
            <li class="nav-item"><a class="nav-link active" href="coupons.php">🎫 Kuponlar</a></li>
            <li class="nav-item mt-auto"><a class="nav-link text-danger" href="logout.php">🚪 Çıkış</a></li>
        </ul>
    </nav>

    <!-- Ana İçerik -->
    <main class="flex-fill p-4">
        <div class="container-fluid">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>🎫 Kupon Yönetimi</h2>
                <a href="dashboard.php" class="btn btn-outline-secondary">← Dashboard</a>
            </div>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php
                        if($_GET['success'] == 'added') echo 'Global kupon başarıyla oluşturuldu!';
                        if($_GET['success'] == 'deleted') echo 'Kupon başarıyla silindi!';
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php
                        if($_GET['error'] == 'invalid_code') echo 'Kupon kodu 4-20 karakter arası olmalı ve sadece harf/rakam içermeli!';
                        if($_GET['error'] == 'invalid_discount') echo 'İndirim miktarı 10-1000 TL arası olmalı!';
                        if($_GET['error'] == 'invalid_max_uses') echo 'Maksimum kullanım 1-100000 arası olmalı!';
                        if($_GET['error'] == 'invalid_date') echo 'Geçersiz tarih formatı!';
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Yeni Global Kupon Ekle -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Yeni Global Kupon Oluştur</h5>
                    <small>Global kuponlar tüm firmalar için geçerlidir</small>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Kupon Kodu</label>
                            <input type="text" name="code" class="form-control"
                                   placeholder="YILBASI2025" required maxlength="20"
                                   style="text-transform: uppercase;">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">İndirim Miktarı (₺)</label>
                            <input type="number" name="discount_amount" class="form-control"
                                   min="10" max="1000" step="0.01" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Maksimum Kullanım</label>
                            <input type="number" name="max_uses" class="form-control"
                                   min="1" max="100000" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Geçerlilik Tarihi</label>
                            <input type="date" name="valid_until" class="form-control"
                                   min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="add_coupon" class="btn btn-primary">
                                Global Kupon Oluştur
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Kupon Listesi -->
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Tüm Kuponlar (<?= wafReflect(count($coupons)) ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if(count($coupons) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Kupon Kodu</th>
                                        <th>İndirim</th>
                                        <th>Kapsam</th>
                                        <th>Kullanım</th>
                                        <th>Geçerlilik</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($coupons as $coupon):
                                        $is_expired = strtotime($coupon['expire_date']) < time();
                                        $is_full = $coupon['total_uses'] >= $coupon['usage_time'];
                                        $is_active = !$is_expired && !$is_full;
                                    ?>
                                    <tr class="<?= !$is_active ? 'table-secondary' : '' ?>">
                                        <td>
                                            <code class="fs-5"><?= wafReflect($coupon['code']) ?></code>
                                        </td>
                                        <td>
                                            <span class="badge bg-success fs-6">
                                                <?= wafReflect(number_format($coupon['discount'], 0)) ?> ₺
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($coupon['company_id'] === null): ?>
                                                <span class="badge bg-primary">🌐 Global</span>
                                            <?php else: ?>
                                                <span class="badge bg-info"><?= wafReflect($coupon['company_name']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $is_full ? 'danger' : 'primary' ?>">
                                                <?= wafReflect($coupon['total_uses']) ?> / <?= wafReflect($coupon['usage_time']) ?>
                                            </span>
                                        </td>
                                        <td><?= wafReflect(date('d.m.Y', strtotime($coupon['expire_date']))) ?></td>
                                        <td>
                                            <?php if($is_active): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php elseif($is_expired): ?>
                                                <span class="badge bg-secondary">Süresi Doldu</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Kullanım Doldu</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="coupons.php?delete=<?= wafReflect($coupon['id']) ?>"
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Bu kuponu silmek istediğinize emin misiniz?')">
                                                Sil
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted py-5">Henüz kupon oluşturulmamış.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- İstatistikler -->
            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="card shadow-sm text-center">
                        <div class="card-body">
                            <h6 class="text-muted">Toplam Kupon</h6>
                            <h2 class="text-primary"><?= wafReflect(count($coupons)) ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm text-center">
                        <div class="card-body">
                            <h6 class="text-muted">Global Kupon</h6>
                            <h2 class="text-info">
                                <?= count(array_filter($coupons, fn($c) => $c['company_id'] === null)) ?>
                            </h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm text-center">
                        <div class="card-body">
                            <h6 class="text-muted">Aktif Kupon</h6>
                            <h2 class="text-success">
                                <?= count(array_filter($coupons, function($c) {
                                    $expired = strtotime($c['expire_date']) < time();
                                    $full = $c['total_uses'] >= $c['usage_time'];
                                    return !$expired && !$full;
                                })) ?>
                            </h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm text-center">
                        <div class="card-body">
                            <h6 class="text-muted">Toplam Kullanım</h6>
                            <h2 class="text-warning">
                                <?= wafReflect(array_sum(array_column($coupons, 'total_uses'))) ?>
                            </h2>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
