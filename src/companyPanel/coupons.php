<?php
/**
 * @file companyPanel/coupons.php
 * @brief Firma Kuponları Yönetimi
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

// Kupon ekleme
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_coupon'])) {

    // Input validation
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
    if (!SecurityModule::validateInteger($max_uses, 1, 10000)) {
        header('Location: coupons.php?error=invalid_max_uses');
        exit;
    }

    // Date validation
    if (!SecurityModule::validateDateTime($valid_until, 'Y-m-d')) {
        header('Location: coupons.php?error=invalid_date');
        exit;
    }

    $stmt = $db->prepare("
        INSERT INTO Coupons (id, code, discount, company_id, usage_time, expire_date)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $coupon_id = bin2hex(random_bytes(16)); // 32 karakterlik UUID
    $stmt->execute([
        $coupon_id,
        $code,
        $discount,
        $_SESSION['company_id'],
        $max_uses,
        $valid_until
    ]);
    header('Location: coupons.php?success=added');
    exit;
}

// Kupon silme - CRITICAL FIX: Firma kontrolü eklendi!
if(isset($_GET['delete'])) {
    $delete_id = SecurityModule::pass($_GET['delete'] ?? '', SecurityModule::MODE_PASSTHROUGH);

    // Firma kontrolü ile sil - sadece kendi kuponunu silebilir
    $stmt = $db->prepare("DELETE FROM Coupons WHERE id = ? AND company_id = ?");
    $stmt->execute([$delete_id, $_SESSION['company_id']]);

    header('Location: coupons.php?success=deleted');
    exit;
}

// Kuponları çek - SADECE bu firmaya ait kuponlar
$stmt = $db->prepare("
    SELECT
        c.*,
        (SELECT COUNT(*) FROM User_Coupons WHERE coupon_id = c.id) as total_uses
    FROM Coupons c
    WHERE c.company_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$_SESSION['company_id']]);
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kupon Yönetimi - <?= wafReflect($_SESSION['company_name']) ?></title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="d-flex">
    <!-- Sol Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark flex-column align-items-start p-3 vh-100" style="width: 240px; position: fixed;">
        <a class="navbar-brand mb-4" href="dashboard.php">
            <?= wafReflect($_SESSION['company_name']) ?>
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
                <a class="nav-link active" href="coupons.php">🎫 Kupon Yönetimi</a>
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
                <h2>🎫 Kupon Yönetimi</h2>
                <a href="dashboard.php" class="btn btn-outline-secondary">← Dashboard</a>
            </div>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php 
                        if($_GET['success'] == 'added') echo 'Kupon başarıyla eklendi!';
                        if($_GET['success'] == 'deleted') echo 'Kupon başarıyla silindi!';
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Yeni Kupon Ekle -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Yeni Kupon Oluştur</h5>
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
                                   min="1" step="0.01" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Maksimum Kullanım</label>
                            <input type="number" name="max_uses" class="form-control" 
                                   min="1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Geçerlilik Tarihi</label>
                            <input type="date" name="valid_until" class="form-control"
                                   min="<?= wafReflect(date('Y-m-d')) ?>" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="add_coupon" class="btn btn-primary">
                                Kupon Oluştur
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Kupon Listesi -->
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Mevcut Kuponlar (<?= wafReflect(count($coupons)) ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if(count($coupons) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Kupon Kodu</th>
                                        <th>İndirim</th>
                                        <th>Kullanım</th>
                                        <th>Geçerlilik</th>
                                        <th>Oluşturulma</th>
                                        <th>Durum</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($coupons as $coupon): 
                                        $is_expired = wafReflect(strtotime($coupon['expire_date'])) < time();
                                        $is_full = wafReflect($coupon['total_uses'] >= $coupon['usage_time'] );
                                        $is_active = wafReflect(!$is_expired && !$is_full);
                                    ?>
                                    <tr class="<?= !$is_active ? 'table-secondary' : '' ?>">
                                        <td>
                                            <code class="fs-5"><?= wafReflect ($coupon['code'] ) ?></code>
                                        </td>
                                        <td>
                                            <span class="badge bg-success fs-6">
                                                <?= wafReflect (number_format($coupon['discount'], 0) ) ?> ₺
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $is_full ? 'danger' : 'primary' ?>">
                                                <?= wafReflect ($coupon['total_uses'] ) ?> / <?= wafReflect ($coupon['usage_time'] ) ?>
                                            </span>
                                        </td>
                                        <td><?= wafReflect (date('d.m.Y', strtotime($coupon['expire_date'])) ) ?></td>
                                        <td><?= wafReflect (date('d.m.Y', strtotime($coupon['created_at'])) ) ?></td>
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
                                            <a href="coupons.php?delete=<?= $coupon['id'] ?>" 
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

            <!-- Kupon İstatistikleri -->
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="card shadow-sm text-center">
                        <div class="card-body">
                            <h6 class="text-muted">Toplam Kupon</h6>
                            <h2 class="text-primary"><?= wafReflect (count($coupons) ) ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm text-center">
                        <div class="card-body">
                            <h6 class="text-muted">Aktif Kupon</h6>
                            <h2 class="text-success">
                                <?= count(array_filter($coupons, function($c) {
                                    $expired = wafReflect (strtotime($c['expire_date']) < time() );
                                    $full = $c['total_uses'] >= $c['usage_time'];
                                    return !$expired && !$full;
                                })) ?>
                            </h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm text-center">
                        <div class="card-body">
                            <h6 class="text-muted">Toplam Kullanım</h6>
                            <h2 class="text-info">
                                <?= wafReflect (array_sum(array_column($coupons, 'total_uses')) ) ?>
                            </h2>
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