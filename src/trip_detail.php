<?php
/**
 * @file trip_detail.php
 * @brief Sefer Detay Sayfası
 *
 * Sefer bilgilerini, doluluk oranını ve koltuk müsaitliğini gösteren sayfa.
 * Giriş yapmış kullanıcılar bilet satın alabilir.
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 *
 * @parameters
 *   - id: Sefer UUID (GET)
 *
 * @features
 *   - Sefer bilgileri görüntüleme
 *   - Doluluk oranı hesaplama
 *   - Müsait koltuk sayısı
 *   - Progress bar visualization
 *
 * @security
 *   - SecurityModule::validateUUID() ile ID validation
 *   - wafReflect() ile XSS koruması
 *   - Prepared statements
 */

require_once 'session_helper.php';
require_once 'dbconnect.php';
require_once '889b1769-5f97-4b94-ac58-69877e948de7/SecurityModule.php';

startSession();

// Trip ID'yi GET'ten al ve validate et
$trip_id = $_GET['id'] ?? null;

// UUID validation (SecurityModule ile)
if (!$trip_id) {
    header('Location: index.php?status=false');
    exit;
}

// UUID format kontrolü (SecurityModule validateUUID kullanarak)
if (!SecurityModule::validateUUID($trip_id)) {
    // Geçersiz UUID format → 403
    SecurityModule::blockRequest('Invalid trip_id format (expected UUID)', $trip_id);
}

// Sefer bilgilerini çek (giriş gerektirmeyen)
$trip_stmt = $db->prepare("
    SELECT t.*, bc.name as company_name, bc.logo_path
    FROM Trips t
    JOIN Bus_Company bc ON t.company_id = bc.id
    WHERE t.id = ? AND datetime(t.departure_time) > datetime('now')
");
$trip_stmt->execute([$trip_id]);
$trip = $trip_stmt->fetch(PDO::FETCH_ASSOC);

if(!$trip) {
    header('Location: index.php?status=not_found');
    exit;
}

// Dolu koltuk sayısı
$sold_stmt = $db->prepare("SELECT COUNT(*) FROM Tickets WHERE trip_id = ? AND status IN ('paid', 'reserved')");
$sold_stmt->execute([$trip_id]);
$sold_count = $sold_stmt->fetchColumn();

$available_count = $trip['capacity'] - $sold_count;
$occupancy_rate = round(($sold_count / $trip['capacity']) * 100);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sefer Detayı - NoTicket</title>

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background: linear-gradient(135deg, #5C6BC0, #1E88E5);
      min-height: 100vh;
      font-family: 'Segoe UI', Roboto, sans-serif;
      padding: 20px;
    }

    .container {
      max-width: 800px;
      margin: 0 auto;
    }

    .card {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 16px;
      padding: 30px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.15);
      margin-bottom: 20px;
    }

    h2 {
      color: #333;
      margin-bottom: 20px;
    }

    .trip-header {
      background: linear-gradient(135deg, #1E88E5, #1565C0);
      color: white;
      padding: 25px;
      border-radius: 12px;
      margin-bottom: 25px;
    }

    .trip-route {
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 10px;
    }

    .trip-company {
      font-size: 16px;
      opacity: 0.9;
    }

    .info-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
      margin-bottom: 25px;
    }

    .info-item {
      background: rgba(30, 136, 229, 0.05);
      padding: 15px;
      border-radius: 8px;
      border-left: 3px solid #1E88E5;
    }

    .info-label {
      font-size: 13px;
      color: #666;
      margin-bottom: 5px;
    }

    .info-value {
      font-size: 18px;
      font-weight: 600;
      color: #333;
    }

    .stats {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 15px;
      margin-bottom: 25px;
    }

    .stat-box {
      text-align: center;
      padding: 20px;
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .stat-value {
      font-size: 32px;
      font-weight: 700;
      color: #1E88E5;
      margin-bottom: 5px;
    }

    .stat-label {
      font-size: 13px;
      color: #666;
    }

    .progress-bar {
      background: #E0E0E0;
      height: 30px;
      border-radius: 15px;
      overflow: hidden;
      margin: 20px 0;
    }

    .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, #43A047, #66BB6A);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 600;
      transition: width 0.3s;
    }

    .progress-fill.high {
      background: linear-gradient(90deg, #F44336, #E57373);
    }

    .btn {
      padding: 12px 30px;
      border: none;
      border-radius: 8px;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      transition: 0.2s;
      text-decoration: none;
      display: inline-block;
      text-align: center;
    }

    .btn-primary {
      background: #1E88E5;
      color: white;
    }

    .btn-primary:hover {
      background: #1565C0;
    }

    .btn-secondary {
      background: #E0E0E0;
      color: #333;
    }

    .btn-secondary:hover {
      background: #BDBDBD;
    }

    .actions {
      display: flex;
      gap: 10px;
      justify-content: center;
      margin-top: 25px;
    }

    @media (max-width: 600px) {
      .info-grid, .stats {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>

  <div class="container">
    <div class="card">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>🚌 Sefer Detayı</h2>
        <a href="<?= isset($_SESSION['user_id']) ? 'dashboard.php' : 'index.php' ?>" class="btn btn-secondary">
          ← Geri
        </a>
      </div>

      <div class="trip-header">
        <div class="trip-route">
          <?= wafReflect($trip['departure_city']) ?> → <?= wafReflect($trip['destination_city']) ?>
        </div>
        <div class="trip-company">
          🚌 <?= wafReflect($trip['company_name']) ?>
        </div>
      </div>

      <div class="info-grid">
        <div class="info-item">
          <div class="info-label">🕐 Kalkış Zamanı</div>
          <div class="info-value"><?= wafReflect(date('d.m.Y H:i', strtotime($trip['departure_time']))) ?></div>
        </div>
        <div class="info-item">
          <div class="info-label">🚏 Biniş Zamanı</div>
          <div class="info-value"><?= wafReflect(date('d.m.Y H:i', strtotime($trip['actual_time']))) ?></div>
        </div>
        <div class="info-item">
          <div class="info-label">💰 Bilet Fiyatı</div>
          <div class="info-value" style="color: #1E88E5;"><?= wafReflect(number_format($trip['price'], 0)) ?> ₺</div>
        </div>
        <div class="info-item">
          <div class="info-label">💺 Toplam Kapasite</div>
          <div class="info-value"><?= wafReflect($trip['capacity']) ?> kişi</div>
        </div>
      </div>

      <div class="stats">
        <div class="stat-box">
          <div class="stat-value" style="color: #43A047;"><?= wafReflect($available_count) ?></div>
          <div class="stat-label">Müsait Koltuk</div>
        </div>
        <div class="stat-box">
          <div class="stat-value" style="color: #F44336;"><?= wafReflect($sold_count) ?></div>
          <div class="stat-label">Satılan Bilet</div>
        </div>
        <div class="stat-box">
          <div class="stat-value" style="color: #1E88E5;"><?= wafReflect($occupancy_rate) ?>%</div>
          <div class="stat-label">Doluluk Oranı</div>
        </div>
      </div>

      <div style="background: rgba(30, 136, 229, 0.05); padding: 15px; border-radius: 8px;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
          <span style="font-weight: 600;">Doluluk Durumu</span>
          <span><?= wafReflect($sold_count) ?> / <?= wafReflect($trip['capacity']) ?></span>
        </div>
        <div class="progress-bar">
          <div class="progress-fill <?= $occupancy_rate > 80 ? 'high' : '' ?>"
               style="width: <?= wafReflect($occupancy_rate) ?>%">
            <?= wafReflect($occupancy_rate) ?>%
          </div>
        </div>
      </div>

      <div class="actions">
        <?php if(isset($_SESSION['user_id'])): ?>
          <?php if($available_count > 0 && strtotime($trip['departure_time']) > time()): ?>
            <a href="booking.php?trip_id=<?= wafReflect($trip['id']) ?>" class="btn btn-primary">
              🎫 Bilet Satın Al
            </a>
          <?php else: ?>
            <button class="btn btn-primary" disabled style="background: #BDBDBD; cursor: not-allowed;">
              <?= $available_count == 0 ? 'Koltuk Kalmadı' : 'Sefer Geçmiş' ?>
            </button>
          <?php endif; ?>
        <?php else: ?>
          <a href="login.php?redirect=booking.php?trip_id=<?= wafReflect($trip['id']) ?>" class="btn btn-primary">
            Giriş Yaparak Satın Al
          </a>
        <?php endif; ?>
        <a href="index.php" class="btn btn-secondary">Ana Sayfa</a>
      </div>
    </div>
  </div>

</body>
</html>