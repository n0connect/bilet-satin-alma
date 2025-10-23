<?php
/**
 * @file index.php
 * @brief NoTicket Ana Sayfa - Sefer listeleme ve arama
 *
 * Ana sayfa: Kullanıcıların sefer arayabileceği ve öne çıkan seferleri
 * görebileceği giriş noktası. Session yönetimi ve kullanıcı bilgilerini
 * görüntüler.
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 *
 * @dependencies
 *   - session_helper.php (Session yönetimi)
 *   - dbconnect.php (Veritabanı bağlantısı)
 *   - SecurityModule.php (Güvenlik validasyonu)
 *
 * @security
 *   - UUID validation için SecurityModule::validateUUID()
 *   - Output encoding için wafReflect()
 *   - Prepared statements ile SQL injection koruması
 */

require_once 'session_helper.php';
require_once 'dbconnect.php';
require_once '889b1769-5f97-4b94-ac58-69877e948de7/SecurityModule.php';

startSession();

// Kullanıcı girişliyse bilgilerini al
$user_data = null;
if(isset($_SESSION['user_id'])) {
    // Session user_id'yi validate et (UUID formatında TEXT olmalı)
    // wafPass: session'dan alınan değer, SQL'de kullanılacak
    $user_id = $_SESSION['user_id'];

    // UUID format kontrolü
    if (!SecurityModule::validateUUID($user_id)) {
        // Geçersiz UUID varsa session'ı temizle
        session_destroy();
        header('Location: login.php?status=false');
        exit;
    }

    $user_stmt = $db->prepare("SELECT full_name, balance FROM User WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);

    // Session'daki bakiyeyi güncelle (DB'den gelen güvenli veri)
    if($user_data) {
        $_SESSION['balance'] = $user_data['balance'];
    }
}

// Rastgele seferler - her yenilemede farklı
$stmt = $db->prepare("
    SELECT 
        t.id,
        t.departure_city,
        t.destination_city,
        t.departure_time,
        t.price,
        t.capacity,
        bc.name as company_name
    FROM Trips t
    JOIN Bus_Company bc ON t.company_id = bc.id
    WHERE datetime(t.departure_time) > datetime('now')
    ORDER BY RANDOM()
    LIMIT 6
");
$stmt->execute();
$featured_trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Şehir listesi (sefer verilerinden)
$cities_stmt = $db->query("
    SELECT DISTINCT departure_city FROM Trips 
    UNION 
    SELECT DISTINCT destination_city FROM Trips
    ORDER BY departure_city ASC
");
$cities = $cities_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>🎫 NoTicket - Otobüs Bileti Ara</title>

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
      color: #333;
    }

    /* Header */
    header {
      background: rgba(255, 255, 255, 0.95);
      padding: 15px 0;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .header-content {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 20px;
    }

    .logo {
      font-size: 24px;
      font-weight: 700;
      color: #1E88E5;
    }

    .header-buttons {
      display: flex;
      gap: 10px;
    }

    /* Welcome Banner */
    .welcome-banner {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 12px;
      padding: 20px 30px;
      margin-bottom: 20px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.1);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .welcome-text h3 {
      margin: 0 0 5px 0;
      color: #333;
      font-size: 20px;
    }

    .welcome-text p {
      margin: 0;
      color: #666;
      font-size: 14px;
    }

    .welcome-balance {
      text-align: right;
    }

    .welcome-balance .label {
      font-size: 12px;
      color: #666;
      margin-bottom: 5px;
    }

    .welcome-balance .amount {
      font-size: 24px;
      font-weight: 700;
      color: #1E88E5;
    }

    /* Container */
    .container {
      max-width: 1200px;
      margin: 40px auto;
      padding: 0 20px;
    }

    /* Search Panel */
    .search-panel {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 16px;
      padding: 30px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.15);
      margin-bottom: 40px;
    }

    .search-panel h2 {
      margin-bottom: 20px;
      color: #333;
    }

    .search-form {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr auto;
      gap: 15px;
      align-items: end;
    }

    .form-group {
      display: flex;
      flex-direction: column;
    }

    .form-group label {
      margin-bottom: 5px;
      font-weight: 500;
      color: #555;
      font-size: 14px;
    }

    .form-group select,
    .form-group input {
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 15px;
      transition: 0.2s;
    }

    .form-group select:focus,
    .form-group input:focus {
      outline: none;
      border-color: #1E88E5;
    }

    /* Buttons */
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
      background: rgba(94, 107, 192, 0.1);
      color: #5C6BC0;
      border: 1px solid transparent;
    }

    .btn-secondary:hover {
      border-color: #5C6BC0;
    }

    /* Trips Section */
    .trips-section {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 16px;
      padding: 30px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    }

    .trips-section h3 {
      margin-bottom: 20px;
      color: #333;
    }

    .trips-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: 20px;
    }

    .trip-card {
      background: white;
      border: 1px solid #e0e0e0;
      border-radius: 12px;
      padding: 20px;
      transition: 0.2s;
      cursor: pointer;
      text-decoration: none !important;
      color: inherit !important;
      display: block;
    }

    .trip-card:hover {
      box-shadow: 0 6px 20px rgba(30, 136, 229, 0.2);
      transform: translateY(-4px);
      border-color: #1E88E5;
    }

    .trip-card:active {
      transform: translateY(-2px);
    }

    .trip-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
      padding-bottom: 15px;
      border-bottom: 1px solid #f0f0f0;
    }

    .trip-route {
      font-size: 18px;
      font-weight: 600;
      color: #333 !important;
    }

    .trip-price {
      font-size: 24px;
      font-weight: 700;
      color: #1E88E5 !important;
    }

    .trip-details {
      display: flex;
      flex-direction: column;
      gap: 8px;
      color: #666 !important;
      font-size: 14px;
    }

    .trip-detail-row {
      display: flex;
      justify-content: space-between;
      color: #666 !important;
    }

    .trip-detail-row span {
      color: #666 !important;
    }

    @media (max-width: 768px) {
      .search-form {
        grid-template-columns: 1fr;
      }

      .trips-grid {
        grid-template-columns: 1fr;
      }

      .welcome-banner {
        flex-direction: column;
        gap: 15px;
        text-align: center;
      }

      .welcome-balance {
        text-align: center;
      }
    }
  </style>
</head>
<body>

  <header>
    <div class="header-content">
      <div class="logo">🎫 NoTicket</div>
      <div class="header-buttons">
        <?php if(isset($_SESSION['user_id'])): ?>
          <?php
            // Rol bazlı dashboard yönlendirmesi
            $dashboard_url = 'dashboard.php'; // Varsayılan: normal kullanıcı
            if(isset($_SESSION['role'])) {
              if($_SESSION['role'] === 'company') {
                $dashboard_url = 'companyPanel/dashboard.php';
              } elseif($_SESSION['role'] === 'admin') {
                $dashboard_url = 'adminPanel/dashboard.php';
              }
            }
          ?>
          <a href="<?= SecurityModule::reflect($dashboard_url); ?>" class="btn btn-secondary">Profilim</a>
          <a href="logout.php" class="btn btn-primary">Çıkış</a>
        <?php else: ?>
          <a href="login.php" class="btn btn-secondary">Giriş Yap</a>
          <a href="register.php" class="btn btn-primary">Kayıt Ol</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <div class="container">
    
    <!-- Kullanıcı Karşılama Banner (sadece normal kullanıcı için) -->
    <?php if(isset($_SESSION['user_id']) && $user_data && (!isset($_SESSION['role']) || $_SESSION['role'] === 'user')): ?>
      <div class="welcome-banner">
        <div class="welcome-text">
          <h3>Merhaba, <?= wafReflect($user_data['full_name']) ?>! 👋</h3>
          <p>Yolculuğunuz için en uygun biletleri bulun</p>
        </div>
        <div class="welcome-balance">
          <div class="label">Bakiyeniz</div>
          <div class="amount"><?= wafReflect(number_format($user_data['balance'], 2)) ?> ₺</div>
        </div>
      </div>
    <?php endif; ?>
    
    <!-- Arama Paneli -->
    <div class="search-panel">
      <h2>Otobüs Bileti Ara</h2>
      <form class="search-form" action="search.php" method="GET">
        <div class="form-group">
          <label for="from">Nereden</label>
          <select id="from" name="from" required>
            <option value="">Şehir Seçin</option>
            <?php foreach($cities as $city): ?>
              <option value="<?= wafReflect($city) ?>"><?= wafReflect($city) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="to">Nereye</label>
          <select id="to" name="to" required>
            <option value="">Şehir Seçin</option>
            <?php foreach($cities as $city): ?>
              <option value="<?= wafReflect($city) ?>"><?= wafReflect($city) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="date">Tarih</label>
          <input type="date" id="date" name="date"
                 value="<?= wafReflect(date('Y-m-d')) ?>"
                 min="<?= wafReflect(date('Y-m-d')) ?>"
                 required>
        </div>

        <button type="submit" class="btn btn-primary">Ara</button>
      </form>
    </div>

    <!-- Popüler Seferler -->
    <div class="trips-section">
      <h3>Günün Öne Çıkan Seferleri</h3>
      
      <?php if(count($featured_trips) > 0): ?>
        <div class="trips-grid">
          <?php foreach($featured_trips as $trip): ?>
            <a href="trip_detail.php?id=<?= wafReflect($trip['id']) ?>" class="trip-card">
              <div class="trip-header">
                <div class="trip-route">
                  <?= wafReflect($trip['departure_city']) ?> →
                  <?= wafReflect($trip['destination_city']) ?>
                </div>
                <div class="trip-price"><?= wafReflect(number_format($trip['price'], 0)) ?> ₺</div>
              </div>
              <div class="trip-details">
                <div class="trip-detail-row">
                  <span>🚌 Firma:</span>
                  <span><?= wafReflect($trip['company_name']) ?></span>
                </div>
                <div class="trip-detail-row">
                  <span>🕐 Kalkış:</span>
                  <span><?= wafReflect(date('d.m.Y H:i', strtotime($trip['departure_time']))) ?></span>
                </div>
                <div class="trip-detail-row">
                  <span>💺 Kapasite:</span>
                  <span><?= wafReflect($trip['capacity']) ?> kişi</span>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p style="color: #666; text-align: center; padding: 40px;">
          Şu anda mevcut sefer bulunmamaktadır.
        </p>
      <?php endif; ?>
    </div>

  </div>

  <script>
    // Tarih dropdown'larını birleştir
    function updateDate() {
      const day = document.getElementById('day').value;
      const month = document.getElementById('month').value;
      const year = document.getElementById('year').value;
      document.getElementById('dateHidden').value = `${year}-${month}-${day}`;
    }
    
    // Sayfa yüklendiğinde ve her değişiklikte
    document.addEventListener('DOMContentLoaded', function() {
      updateDate();
      document.getElementById('day').addEventListener('change', updateDate);
      document.getElementById('month').addEventListener('change', updateDate);
      document.getElementById('year').addEventListener('change', updateDate);
    });
  </script>

</body>
</html>