<?php
/**
 * @file search.php
 * @brief Sefer Arama Sonuçları Sayfası
 *
 * Kullanıcıların şehir ve tarih bazlı sefer araması yapabileceği sayfa.
 * GET parametreleri ile arama yapar ve sonuçları listeler.
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 *
 * @parameters
 *   - from: Kalkış şehri (whitelist validation)
 *   - to: Varış şehri (whitelist validation)
 *   - date: Tarih (Y-m-d format, regex + DateTime validation)
 *
 * @security
 *   - wafPass() ile input validation
 *   - City whitelist kontrolü (81 il listesi)
 *   - Date format validation (regex + DateTime)
 *   - Prepared statements
 *   - wafReflect() ile output encoding
 */

require_once 'session_helper.php';
require_once 'dbconnect.php';
require_once '889b1769-5f97-4b94-ac58-69877e948de7/SecurityModule.php';
startSession();

// Kullanıcı girişliyse bakiyeyi güncelle
if(isset($_SESSION['user_id'])) {
    $balance_stmt = $db->prepare("SELECT balance FROM User WHERE id = ?");
    $balance_stmt->execute([$_SESSION['user_id']]);
    $balance = $balance_stmt->fetchColumn();
    if($balance !== false) {
        $_SESSION['balance'] = $balance;
    }
}

$cities_in_2025 = [
    'Adana',
    'Adıyaman',
    'Afyonkarahisar',
    'Ağrı',
    'Aksaray',
    'Amasya',
    'Ankara',
    'Antalya',
    'Ardahan',
    'Artvin',
    'Aydın',
    'Balıkesir',
    'Bartın',
    'Batman',
    'Bayburt',
    'Bilecik',
    'Bingöl',
    'Bitlis',
    'Bolu',
    'Burdur',
    'Bursa',
    'Çanakkale',
    'Çankırı',
    'Çorum',
    'Denizli',
    'Diyarbakır',
    'Düzce',
    'Edirne',
    'Elazığ',
    'Erzincan',
    'Erzurum',
    'Eskişehir',
    'Gaziantep',
    'Giresun',
    'Gümüşhane',
    'Hakkari',
    'Hatay',
    'Iğdır',
    'Isparta',
    'İstanbul',
    'İzmir',
    'Kahramanmaraş',
    'Karabük',
    'Karaman',
    'Kars',
    'Kastamonu',
    'Kayseri',
    'Kilis',
    'Kırıkkale',
    'Kırklareli',
    'Kırşehir',
    'Kocaeli',
    'Konya',
    'Kütahya',
    'Malatya',
    'Manisa',
    'Mardin',
    'Mersin',
    'Muğla',
    'Muş',
    'Nevşehir',
    'Niğde',
    'Ordu',
    'Osmaniye',
    'Rize',
    'Sakarya',
    'Samsun',
    'Şanlıurfa',
    'Siirt',
    'Sinop',
    'Sivas',
    'Şırnak',
    'Tekirdağ',
    'Tokat',
    'Trabzon',
    'Tunceli',
    'Uşak',
    'Van',
    'Yalova',
    'Yozgat',
    'Zonguldak'
];

// Form verilerini al ve validasyon yap
$from = wafPass(isset($_GET['from']) ? trim($_GET['from']) : '');
$to = wafPass(isset($_GET['to']) ? trim($_GET['to']) : '');
$date = wafPass(isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d'));

// Boş parametre kontrolü
if(empty($from) || empty($to)) {
    header('Location: index.php');
    exit;
}

// Şehirlerin geçerli olup olmadığını kontrol et
if (!in_array($from, $cities_in_2025) || !in_array($to, $cities_in_2025)) {
    // İsteğe bağlı: Hata mesajı göstermek veya loglamak için kullanabilirsiniz
    // Örnek: error_log("Geçersiz şehir: from=$from, to=$to");
    $error = "Geçersiz şehir adı.";
    header('Location: index.php');
    exit;
}

// Regex + DateTime combo
if (!empty($date)) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $date = date('Y-m-d');
        $error = "Geçersiz tarih formatı.";
    } else {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if (!$d || $d->format('Y-m-d') !== $date) {
            $date = date('Y-m-d');
            $error = "Geçersiz tarih.";
        }
    }
} else {
    $date = date('Y-m-d');
}

// Date is already validated and safe from wafPass above

// Aynı şehir kontrolü
if($from === $to) {
    $error = "Kalkış ve varış şehri aynı olamaz.";
    $trips = [];
} else {
    // Seferleri ara
    $stmt = $db->prepare("
        SELECT 
            t.id,
            t.departure_city,
            t.destination_city,
            t.departure_time,
            t.actual_time as boarding_time,
            t.price,
            t.capacity,
            bc.name as company_name,
            bc.id as company_id,
            (SELECT COUNT(*) FROM Tickets WHERE trip_id = t.id) as sold_tickets
        FROM Trips t
        JOIN Bus_Company bc ON t.company_id = bc.id
        WHERE t.departure_city = ? 
        AND t.destination_city = ?
        AND date(t.departure_time) = ?
        AND datetime(t.departure_time) > datetime('now')
        ORDER BY t.departure_time ASC
    ");
    
    $stmt->execute([$from, $to, $date]);
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sefer Sonuçları - NoTicket</title>

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
      text-decoration: none;
    }

    .header-buttons {
      display: flex;
      gap: 10px;
    }

    /* Container */
    .container {
      max-width: 1200px;
      margin: 30px auto;
      padding: 0 20px;
    }

    /* Search Info */
    .search-info {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 12px;
      padding: 20px 30px;
      margin-bottom: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    }

    .search-details {
      display: flex;
      gap: 30px;
      align-items: center;
    }

    .search-item {
      display: flex;
      flex-direction: column;
    }

    .search-item label {
      font-size: 12px;
      color: #666;
      margin-bottom: 5px;
    }

    .search-item span {
      font-size: 16px;
      font-weight: 600;
      color: #333;
    }

    /* Buttons */
    .btn {
      padding: 10px 24px;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: 0.2s;
      text-decoration: none;
      display: inline-block;
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
    }

    .btn-secondary:hover {
      background: rgba(94, 107, 192, 0.2);
    }

    .btn-success {
      background: #43A047;
      color: white;
    }

    .btn-success:hover {
      background: #388E3C;
    }

    /* Results Section */
    .results-section {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    }

    .results-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 2px solid #f0f0f0;
    }

    .results-count {
      font-size: 18px;
      font-weight: 600;
      color: #333;
    }

    /* Trip Card */
    .trip-card {
      background: white;
      border: 1px solid #e0e0e0;
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 15px;
      transition: 0.2s;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .trip-card:hover {
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      transform: translateX(4px);
    }

    .trip-left {
      flex: 1;
    }

    .trip-company {
      font-size: 16px;
      font-weight: 600;
      color: #333;
      margin-bottom: 12px;
    }

    .trip-route {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 10px;
    }

    .trip-city {
      font-size: 18px;
      font-weight: 600;
      color: #1E88E5;
    }

    .trip-arrow {
      color: #999;
      font-size: 20px;
    }

    .trip-meta {
      display: flex;
      gap: 20px;
      color: #666;
      font-size: 14px;
    }

    .trip-right {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 10px;
    }

    .trip-price {
      font-size: 28px;
      font-weight: 700;
      color: #1E88E5;
    }

    .trip-availability {
      font-size: 13px;
      color: #666;
    }

    .available {
      color: #43A047;
      font-weight: 600;
    }

    .limited {
      color: #FB8C00;
      font-weight: 600;
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
    }

    .empty-state h3 {
      font-size: 24px;
      color: #666;
      margin-bottom: 15px;
    }

    .empty-state p {
      color: #999;
      margin-bottom: 25px;
    }

    /* Error Alert */
    .alert {
      background: #ffebee;
      border-left: 4px solid #f44336;
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 8px;
      color: #c62828;
    }

    @media (max-width: 768px) {
      .search-details {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
      }

      .trip-card {
        flex-direction: column;
        align-items: flex-start;
      }

      .trip-right {
        width: 100%;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #f0f0f0;
      }
    }
  </style>
</head>
<body>

  <header>
    <div class="header-content">
      <a href="index.php" class="logo">🎫 NoTicket</a>
      <div class="header-buttons">
        <?php if(isset($_SESSION['user_id'])): ?>
          <a href="dashboard.php" class="btn btn-secondary">Profilim</a>
          <a href="logout.php" class="btn btn-primary">Çıkış</a>
        <?php else: ?>
          <a href="login.php" class="btn btn-secondary">Giriş Yap</a>
          <a href="register.php" class="btn btn-primary">Kayıt Ol</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <div class="container">
    
    <!-- Arama Bilgisi -->
    <div class="search-info">
      <div class="search-details">
        <div class="search-item">
          <label>Nereden</label>
          <span><?= wafReflect($from) ?></span>
        </div>
        <div class="search-item">
          <label>Nereye</label>
          <span><?= wafReflect($to) ?></span>
        </div>
        <div class="search-item">
          <label>Tarih</label>
          <span><?= wafReflect(date('d.m.Y', strtotime($date))) ?></span>
        </div>
      </div>
      <a href="index.php" class="btn btn-secondary">Yeni Arama</a>
    </div>

    <!-- Sonuçlar -->
    <div class="results-section">

      <?php if(isset($error)): ?>
        <div class="alert"><?= wafReflect($error) ?></div>
      <?php endif; ?>

      <?php if(count($trips) > 0): ?>
        <div class="results-header">
          <div class="results-count">
            <?= wafReflect(count($trips)) ?> sefer bulundu
          </div>
        </div>

        <?php foreach($trips as $trip):
          $available_seats = $trip['capacity'] - $trip['sold_tickets'];
          $availability_class = $available_seats > 10 ? 'available' : 'limited';
        ?>
          <div class="trip-card">
            <div class="trip-left">
              <div class="trip-company">
                🚌 <?= wafReflect($trip['company_name']) ?>
              </div>
              <div class="trip-route">
                <div class="trip-city"><?= wafReflect($trip['departure_city']) ?></div>
                <div class="trip-arrow">→</div>
                <div class="trip-city"><?= wafReflect($trip['destination_city']) ?></div>
              </div>
              <div class="trip-meta">
                <span>🕐 Kalkış: <?= wafReflect(date('H:i', strtotime($trip['departure_time']))) ?></span>
                <span>🚏 Gerçek Saat: <?= wafReflect(date('H:i', strtotime($trip['boarding_time']))) ?></span>
                <span>💺 Kapasite: <?= wafReflect($trip['capacity']) ?> kişi</span>
              </div>
            </div>
            <div class="trip-right">
              <div class="trip-price"><?= wafReflect(number_format($trip['price'], 0)) ?> ₺</div>
              <div class="trip-availability <?= $availability_class ?>">
                <?= wafReflect($available_seats) ?> koltuk mevcut
              </div>
              <?php if(isset($_SESSION['user_id'])): ?>
                <a href="booking.php?trip_id=<?= wafReflect($trip['id']) ?>" class="btn btn-success">
                  Bilet Al
                </a>
              <?php else: ?>
                <a href="login.php?redirect=booking.php?trip_id=<?= wafReflect($trip['id']) ?>"
                   class="btn btn-success">
                  Giriş Yaparak Al
                </a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>

      <?php else: ?>
        <div class="empty-state">
          <h3>😔 Üzgünüz, sefer bulunamadı</h3>
          <p>
            <?= wafReflect($from) ?> - <?= wafReflect($to) ?>
            güzergahında <?= wafReflect(date('d.m.Y', strtotime($date))) ?> tarihinde sefer bulunmamaktadır.
          </p>
          <a href="index.php" class="btn btn-primary">Yeni Arama Yap</a>
        </div>
      <?php endif; ?>
    </div>

  </div>

</body>
</html>