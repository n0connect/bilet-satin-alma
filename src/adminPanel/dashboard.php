<?php
/**
 * @file adminPanel/dashboard.php
 * @brief Admin Dashboard - Sistem İstatistikleri
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 */

// adminPanel/dashboard.php
require_once 'auth.php'; // Admin auth kontrolü
require_once '../dbconnect.php';
require_once '../889b1769-5f97-4b94-ac58-69877e948de7/SecurityModule.php';

// İstatistikler
$systemStats = getSystemStats();
$recentUsers = getRecentUsers(10);
$companies = getAllCompanies();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>🎫 NoTicket - Admin Paneli</title>
  <link href="../css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex">

  <!-- Sol Navbar -->
  <nav class="navbar navbar-expand-lg bg-body-tertiary flex-column align-items-stretch p-3 border-end" style="min-height:100vh; width:260px;">
    <a class="navbar-brand mb-4 text-center fw-bold text-primary" href="dashboard.php">🎫 NoTicket<br>Admin Panel</a>
    <ul class="navbar-nav flex-column">
      <li class="nav-item"><a class="nav-link active" href="dashboard.php">📊 Dashboard</a></li>
      <li class="nav-item"><a class="nav-link" href="companies.php">🚌 Firmalar</a></li>
      <li class="nav-item"><a class="nav-link" href="company_admins.php">👥 Firma Adminleri</a></li>
      <li class="nav-item"><a class="nav-link" href="coupons.php">🎫 Kuponlar</a></li>
      <li class="nav-item mt-auto"><a class="nav-link text-danger" href="logout.php">🚪 Çıkış</a></li>
    </ul>
  </nav>

  <!-- Ana İçerik -->
  <main class="flex-fill p-4">
    <div class="container-fluid">

      <div class="card mb-4">
        <div class="card-body">
          <h5 class="card-title mb-3">Hoşgeldin, <?= wafReflect($_SESSION['full_name']) ?></h5>
          <p><strong>Email:</strong> <?= wafReflect($_SESSION['email']) ?></p>
          <p><strong>Giriş Zamanı:</strong> <?= wafReflect($_SESSION['login_datetime']) ?></p>
          <p><strong>Online Süresi:</strong> <?= wafReflect(getLoginDuration()) ?></p>
        </div>
      </div>

      <h4 class="mb-3">📈 Sistem İstatistikleri</h4>
      <div class="row g-3 mb-4">
        <?php 
        $stats = [
          'Toplam Kullanıcı' => wafReflect($systemStats['total_users']),
          'Firma Yöneticisi' => wafReflect($systemStats['total_companies']),
          'Sistem Admini' => wafReflect($systemStats['total_admins']),
          'Otobüs Firması' => wafReflect($systemStats['total_bus_companies']),
          'Toplam Sefer' => wafReflect($systemStats['total_trips']),
          'Satılan Bilet' => wafReflect($systemStats['total_sold_tickets']),
          'Toplam Ciro' => wafReflect((number_format($systemStats['total_revenue'] ?? 0, 2) . ' TL'))
        ];
        foreach ($stats as $label => $value): ?>
        <div class="col-sm-6 col-md-4 col-lg-3">
          <div class="card text-center border-0 shadow-sm">
            <div class="card-body">
              <h5 class="card-title fw-bold"><?= wafReflect($value) ?></h5>
              <p class="card-text text-muted"><?= wafReflect($label) ?></p>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <h4 class="mb-3">🚌 Kayıtlı Firmalar</h4>
      <?php if (count($companies) > 0): ?>
        <div class="table-responsive mb-4">
          <table class="table table-striped table-hover align-middle">
            <thead class="table-secondary">
              <tr>
                <th>Firma Adı</th>
                <th>Yönetici</th>
                <th>Sefer Sayısı</th>
                <th>Kayıt Tarihi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($companies as $company): ?>
              <tr>
                <td><?= wafReflect($company['name']) ?></td>
                <td><?= wafReflect($company['admin_name'] ?? 'Atanmamış') ?></td>
                <td><?= $company['trip_count'] ?></td>
                <td><?= wafReflect($company['created_at']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="alert alert-info">Kayıtlı firma bulunmamaktadır.</div>
      <?php endif; ?>

      <h4 class="mb-3">👥 Son Kayıt Olan Kullanıcılar</h4>
      <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
          <thead class="table-secondary">
            <tr>
              <th>Ad Soyad</th>
              <th>Email</th>
              <th>Rol</th>
              <th>Bakiye</th>
              <th>Kayıt Tarihi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($recentUsers as $user): ?>
            <tr>
              <td><?= wafReflect($user['full_name']) ?></td>
              <td><?= wafReflect($user['email']) ?></td>
              <td>
                <?php 
                  $badgeClass = match($user['role']) {
                    'admin' => 'danger',
                    'company' => 'primary',
                    'user' => 'success',
                    default => 'secondary'
                  };
                ?>
                <span class="badge bg-<?= wafReflect($badgeClass) ?>"><?= wafReflect($user['role']) ?></span>
              </td>
              <td><?= $user['balance'] ?> TL</td>
              <td><?= wafReflect($user['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    </div>
  </main>

  <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
