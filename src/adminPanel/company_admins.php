<?php
/**
 * @file adminPanel/company_admins.php
 * @brief Firma Yöneticileri Yönetimi
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 *
 * @security password_hash(), validateEmail(), UUID generation
 */

require_once 'auth.php';
require_once '../dbconnect.php';
require_once '../889b1769-5f97-4b94-ac58-69877e948de7/SecurityModule.php';

// Firma Admin ekleme
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_company_admin'])) {

    $full_name = SecurityModule::sanitize($_POST['full_name'] ?? '', SecurityModule::MODE_TEXT);
    $email = SecurityModule::pass($_POST['email'] ?? '', SecurityModule::MODE_EMAIL);
    $password = SecurityModule::pass($_POST['password'] ?? '', SecurityModule::MODE_PASSWORD);
    $company_id = SecurityModule::pass($_POST['company_id'] ?? '', SecurityModule::MODE_PASSTHROUGH);

    // Validations
    if(empty($full_name) || empty($email) || empty($password)) {
        header('Location: company_admins.php?error=empty_fields');
        exit;
    }

    if(!SecurityModule::validateEmail($email)) {
        header('Location: company_admins.php?error=invalid_email');
        exit;
    }

    if(strlen($password) < 6) {
        header('Location: company_admins.php?error=weak_password');
        exit;
    }

    // Email zaten kullanılıyor mu?
    $check_stmt = $db->prepare("SELECT COUNT(*) FROM User WHERE email = ?");
    $check_stmt->execute([$email]);
    if($check_stmt->fetchColumn() > 0) {
        header('Location: company_admins.php?error=email_exists');
        exit;
    }

    // Company ID validasyonu
    $company_check = $db->prepare("SELECT id FROM Bus_Company WHERE id = ?");
    $company_check->execute([$company_id]);
    if(!$company_check->fetch()) {
        header('Location: company_admins.php?error=invalid_company');
        exit;
    }

    $user_id = bin2hex(random_bytes(16)); // 32 karakterlik UUID - kriptografik güvenli
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare("
        INSERT INTO User (id, full_name, email, password, role, company_id, balance, created_at)
        VALUES (?, ?, ?, ?, 'company', ?, 0, datetime('now'))
    ");
    $stmt->execute([$user_id, $full_name, $email, $hashed_password, $company_id]);

    header('Location: company_admins.php?success=added');
    exit;
}

// Firma Admin silme
if(isset($_GET['delete'])) {
    $delete_id = SecurityModule::pass($_GET['delete'] ?? '', SecurityModule::MODE_PASSTHROUGH);

    // Sadece company rolündeki kullanıcıları silebiliriz
    $stmt = $db->prepare("DELETE FROM User WHERE id = ? AND role = 'company'");
    $stmt->execute([$delete_id]);

    header('Location: company_admins.php?success=deleted');
    exit;
}

// Tüm firmaları çek (dropdown için)
$companies = getAllCompanies();

// Tüm firma adminlerini çek
$admins_stmt = $db->prepare("
    SELECT
        u.id,
        u.full_name,
        u.email,
        u.created_at,
        u.company_id,
        bc.name as company_name
    FROM User u
    LEFT JOIN Bus_Company bc ON u.company_id = bc.id
    WHERE u.role = 'company'
    ORDER BY u.created_at DESC
");
$admins_stmt->execute();
$company_admins = $admins_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Firma Admin Yönetimi - Admin Paneli</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex">

    <!-- Sol Navbar -->
    <nav class="navbar navbar-expand-lg bg-body-tertiary flex-column align-items-stretch p-3 border-end" style="min-height:100vh; width:260px;">
        <a class="navbar-brand mb-4 text-center fw-bold text-primary" href="dashboard.php">🎫 NoTicket<br>Admin Panel</a>
        <ul class="navbar-nav flex-column">
            <li class="nav-item"><a class="nav-link" href="dashboard.php">📊 Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="companies.php">🚌 Firmalar</a></li>
            <li class="nav-item"><a class="nav-link active" href="company_admins.php">👥 Firma Adminleri</a></li>
            <li class="nav-item"><a class="nav-link" href="coupons.php">🎫 Kuponlar</a></li>
            <li class="nav-item mt-auto"><a class="nav-link text-danger" href="logout.php">🚪 Çıkış</a></li>
        </ul>
    </nav>

    <!-- Ana İçerik -->
    <main class="flex-fill p-4">
        <div class="container-fluid">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>👥 Firma Admin Yönetimi</h2>
                <a href="dashboard.php" class="btn btn-outline-secondary">← Dashboard</a>
            </div>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php
                        if($_GET['success'] == 'added') echo 'Firma admini başarıyla oluşturuldu!';
                        if($_GET['success'] == 'deleted') echo 'Firma admini başarıyla silindi!';
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php
                        if($_GET['error'] == 'empty_fields') echo 'Tüm alanları doldurun!';
                        if($_GET['error'] == 'invalid_email') echo 'Geçersiz email formatı!';
                        if($_GET['error'] == 'weak_password') echo 'Şifre en az 6 karakter olmalı!';
                        if($_GET['error'] == 'email_exists') echo 'Bu email zaten kullanılıyor!';
                        if($_GET['error'] == 'invalid_company') echo 'Geçersiz firma seçimi!';
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Yeni Firma Admin Ekle -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Yeni Firma Admin Oluştur</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Ad Soyad</label>
                            <input type="text" name="full_name" class="form-control"
                                   placeholder="Örn: Ahmet Yılmaz" required maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Firma</label>
                            <select name="company_id" class="form-select" required>
                                <option value="">Firma Seçin</option>
                                <?php foreach($companies as $company): ?>
                                    <option value="<?= wafReflect($company['id']) ?>">
                                        <?= wafReflect($company['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                   placeholder="ornek@firma.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Şifre</label>
                            <input type="password" name="password" class="form-control"
                                   placeholder="En az 6 karakter" required minlength="6">
                        </div>
                        <div class="col-12">
                            <button type="submit" name="add_company_admin" class="btn btn-primary">
                                Firma Admin Oluştur
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Firma Admin Listesi -->
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Firma Adminleri (<?= wafReflect(count($company_admins)) ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if(count($company_admins) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-secondary">
                                    <tr>
                                        <th>Ad Soyad</th>
                                        <th>Email</th>
                                        <th>Firma</th>
                                        <th>Kayıt Tarihi</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($company_admins as $admin): ?>
                                    <tr>
                                        <td><strong><?= wafReflect($admin['full_name']) ?></strong></td>
                                        <td><?= wafReflect($admin['email']) ?></td>
                                        <td>
                                            <?php if($admin['company_name']): ?>
                                                <span class="badge bg-primary"><?= wafReflect($admin['company_name']) ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Firma Atanmamış</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= wafReflect(date('d.m.Y H:i', strtotime($admin['created_at']))) ?></td>
                                        <td>
                                            <a href="company_admins.php?delete=<?= wafReflect($admin['id']) ?>"
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Bu firma adminini silmek istediğinize emin misiniz?')">
                                                Sil
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted py-5">Henüz firma admini bulunmuyor.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
