<?php
/**
 * @file adminPanel/companies.php
 * @brief Otobüs Firmaları Yönetimi
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 *
 * @security SecurityModule::sanitize(), UUID validation
 */

require_once 'auth.php';
require_once '../dbconnect.php';
require_once '../889b1769-5f97-4b94-ac58-69877e948de7/SecurityModule.php';

// Firma ekleme
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_company'])) {

    $company_name = SecurityModule::sanitize($_POST['company_name'] ?? '', SecurityModule::MODE_TEXT);

    if(empty($company_name)) {
        header('Location: companies.php?error=empty_name');
        exit;
    }

    $company_id = bin2hex(random_bytes(16)); // 32 karakterlik UUID - kriptografik güvenli

    $stmt = $db->prepare("INSERT INTO Bus_Company (id, name, created_at) VALUES (?, ?, datetime('now'))");
    $stmt->execute([$company_id, $company_name]);

    header('Location: companies.php?success=added');
    exit;
}

// Firma silme
if(isset($_GET['delete'])) {
    $delete_id = SecurityModule::pass($_GET['delete'] ?? '', SecurityModule::MODE_PASSTHROUGH);

    // Önce bu firmaya ait seferleri kontrol et
    $check_stmt = $db->prepare("SELECT COUNT(*) FROM Trips WHERE company_id = ?");
    $check_stmt->execute([$delete_id]);
    $trip_count = $check_stmt->fetchColumn();

    if($trip_count > 0) {
        header('Location: companies.php?error=has_trips');
        exit;
    }

    // Firmayı sil
    $stmt = $db->prepare("DELETE FROM Bus_Company WHERE id = ?");
    $stmt->execute([$delete_id]);

    header('Location: companies.php?success=deleted');
    exit;
}

// Tüm firmaları listele
$companies = getAllCompanies();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Firma Yönetimi - Admin Paneli</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex">

    <!-- Sol Navbar -->
    <nav class="navbar navbar-expand-lg bg-body-tertiary flex-column align-items-stretch p-3 border-end" style="min-height:100vh; width:260px;">
        <a class="navbar-brand mb-4 text-center fw-bold text-primary" href="dashboard.php">🎫 NoTicket<br>Admin Panel</a>
        <ul class="navbar-nav flex-column">
            <li class="nav-item"><a class="nav-link" href="dashboard.php">📊 Dashboard</a></li>
            <li class="nav-item"><a class="nav-link active" href="companies.php">🚌 Firmalar</a></li>
            <li class="nav-item"><a class="nav-link" href="company_admins.php">👥 Firma Adminleri</a></li>
            <li class="nav-item"><a class="nav-link" href="coupons.php">🎫 Kuponlar</a></li>
            <li class="nav-item mt-auto"><a class="nav-link text-danger" href="logout.php">🚪 Çıkış</a></li>
        </ul>
    </nav>

    <!-- Ana İçerik -->
    <main class="flex-fill p-4">
        <div class="container-fluid">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>🚌 Firma Yönetimi</h2>
                <a href="dashboard.php" class="btn btn-outline-secondary">← Dashboard</a>
            </div>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php
                        if($_GET['success'] == 'added') echo 'Firma başarıyla eklendi!';
                        if($_GET['success'] == 'deleted') echo 'Firma başarıyla silindi!';
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php
                        if($_GET['error'] == 'empty_name') echo 'Firma adı boş olamaz!';
                        if($_GET['error'] == 'has_trips') echo 'Bu firmaya ait seferler var! Önce seferleri silin.';
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Yeni Firma Ekle -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Yeni Firma Ekle</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Firma Adı</label>
                            <input type="text" name="company_name" class="form-control"
                                   placeholder="Örn: Metro Turizm" required maxlength="100">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" name="add_company" class="btn btn-primary w-100">
                                Firma Ekle
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Firma Listesi -->
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Kayıtlı Firmalar (<?= wafReflect(count($companies)) ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if(count($companies) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-secondary">
                                    <tr>
                                        <th>Firma Adı</th>
                                        <th>Yönetici</th>
                                        <th>Sefer Sayısı</th>
                                        <th>Kayıt Tarihi</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($companies as $company): ?>
                                    <tr>
                                        <td><strong><?= wafReflect($company['name']) ?></strong></td>
                                        <td>
                                            <?php if($company['admin_name']): ?>
                                                <span class="badge bg-success"><?= wafReflect($company['admin_name']) ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Atanmamış</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= wafReflect($company['trip_count']) ?> sefer</span>
                                        </td>
                                        <td><?= wafReflect(date('d.m.Y', strtotime($company['created_at']))) ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if($company['trip_count'] == 0): ?>
                                                    <a href="companies.php?delete=<?= wafReflect($company['id']) ?>"
                                                       class="btn btn-outline-danger"
                                                       onclick="return confirm('Bu firmayı silmek istediğinize emin misiniz?')">
                                                        Sil
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-outline-secondary" disabled>
                                                        Silinemez
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted py-5">Henüz kayıtlı firma bulunmuyor.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
