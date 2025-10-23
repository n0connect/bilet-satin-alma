<?php
/**
 * @file dashboard.php
 * @brief Kullanıcı Dashboard Sayfası
 *
 * Giriş yapmış kullanıcıların biletlerini görüntüleyebileceği, iptal edebileceği
 * ve hesap bilgilerini görebileceği ana panel sayfası.
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 *
 * @features
 *   - Satın alınan biletleri listeleme
 *   - Bilet iptal işlemi (kalkışa 1 saat kalana kadar)
 *   - Bakiye gösterimi
 *   - Session bilgileri
 *
 * @security
 *   - auth.php ile giriş kontrolü
 *   - UUID validation (SecurityModule::validateUUID)
 *   - User ownership kontrolü
 *   - Transaction support (rollback)
 *   - wafReflect() ile XSS koruması
 */

require_once 'auth.php';
require_once 'dbconnect.php';
require_once '889b1769-5f97-4b94-ac58-69877e948de7/SecurityModule.php';

// Bilet iptal işlemi
if(isset($_POST['cancel_ticket'])) {
    $ticket_id = $_POST['ticket_id'] ?? null;

    // Ticket ID validasyonu (UUID format)
    if (!$ticket_id || !SecurityModule::validateUUID($ticket_id)) {
        SecurityModule::blockRequest('Invalid ticket_id format (expected UUID)', $ticket_id);
    }
    
    // Bilet bilgilerini al ve doğrula
    $ticket_stmt = $db->prepare("
        SELECT tk.id, tk.user_id, t.departure_time, t.price
        FROM Tickets tk
        JOIN Trips t ON tk.trip_id = t.id
        WHERE tk.id = ? AND tk.user_id = ? AND tk.status = 'paid'
    ");
    $ticket_stmt->execute([$ticket_id, $_SESSION['user_id']]);
    $ticket = $ticket_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket || $ticket['user_id'] !== $_SESSION['user_id']) {
        SecurityModule::blockRequest('Unauthorized ticket access attempt', $ticket_id);
    }
    
    if(!$ticket) {
        header('Location: dashboard.php?error=ticket_not_found');
        exit;
    }
    
    // Kalkışa 1 saatten fazla var mı kontrol et
    $departure_timestamp = strtotime($ticket['departure_time']);
    $current_timestamp = time();
    $time_diff_hours = ($departure_timestamp - $current_timestamp) / 3600;
    
    if($time_diff_hours <= 1) {
        header('Location: dashboard.php?error=too_late');
        exit;
    }
    
    // Transaction başlat
    $db->beginTransaction();
    
    try {
        // İptal et
        $cancel_stmt = $db->prepare("UPDATE Tickets SET status = 'cancelled' WHERE id = ?");
        $cancel_stmt->execute([$ticket_id]);
        
        // Para iade et
        $refund_stmt = $db->prepare("UPDATE User SET balance = balance + ? WHERE id = ?");
        $refund_stmt->execute([$ticket['price'], $_SESSION['user_id']]);
        
        // Commit
        $db->commit();
        
        // Session bakiyesini güncelle
        $_SESSION['balance'] += $ticket['price'];
        
        header('Location: dashboard.php?success=cancelled');
        exit;
    } catch (Exception $e) {
        // Rollback
        $db->rollBack();
        header('Location: dashboard.php?error=cancel_failed');
        exit;
    }
}

// Kullanıcının satın aldığı biletleri çek
$tickets_stmt = $db->prepare("
    SELECT 
        tk.id,
        tk.seat_number,
        tk.created_at as purchase_date,
        tk.status,
        t.departure_city,
        t.destination_city,
        t.departure_time,
        t.actual_time,
        t.price,
        t.id as trip_id,
        bc.name as company_name
    FROM Tickets tk
    JOIN Trips t ON tk.trip_id = t.id
    JOIN Bus_Company bc ON t.company_id = bc.id
    WHERE tk.user_id = ?
    ORDER BY tk.created_at DESC
    LIMIT 10
");
$tickets_stmt->execute([$_SESSION['user_id']]);
$my_tickets = $tickets_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>🎫 NoTicket - Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <style>
    /* === 🌈 Genel Stil & Arka Plan === */
    body {
      background: linear-gradient(135deg, #5C6BC0, #1E88E5);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      font-family: 'Segoe UI', Roboto, sans-serif;
      margin: 0;
      color: #fff;
      padding: 20px;
    }

    /* === 🔮 Dashboard Panel === */
    .dashboard {
      background: rgba(255, 255, 255, 0.15);
      border-radius: 20px;
      box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      padding: 40px 50px;
      max-width: 800px;
      width: 100%;
      transition: all 0.3s ease;
    }

    h1 {
      margin-bottom: 20px;
      font-size: 26px;
      font-weight: 600;
      letter-spacing: 0.5px;
      text-align: center;
    }

    .info {
      background: rgba(255, 255, 255, 0.15);
      border-radius: 12px;
      padding: 20px;
      margin-top: 20px;
      font-size: 15px;
    }

    .info p {
      margin: 10px 0;
      color: rgba(255, 255, 255, 0.9);
    }

    /* === Biletler Bölümü === */
    .tickets-section {
      margin-top: 30px;
    }

    .tickets-section h2 {
      font-size: 20px;
      margin-bottom: 15px;
      text-align: center;
    }

    .ticket-card {
      background: rgba(255, 255, 255, 0.2);
      border-radius: 12px;
      padding: 15px;
      margin-bottom: 12px;
      backdrop-filter: blur(5px);
    }

    .ticket-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
      padding-bottom: 10px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }

    .ticket-route {
      font-size: 16px;
      font-weight: 600;
    }

    .ticket-price {
      font-size: 18px;
      font-weight: 700;
    }

    .ticket-details {
      font-size: 14px;
      color: rgba(255, 255, 255, 0.85);
      line-height: 1.6;
    }

    .ticket-actions {
      margin-top: 12px;
      padding-top: 12px;
      border-top: 1px solid rgba(255, 255, 255, 0.2);
      display: flex;
      gap: 8px;
    }

    .ticket-actions .btn {
      padding: 6px 15px;
      font-size: 13px;
      margin: 0;
    }

    .btn-cancel {
      background: rgba(244, 67, 54, 0.3);
    }

    .btn-cancel:hover {
      background: rgba(244, 67, 54, 0.5);
    }

    .btn-download {
      background: rgba(76, 175, 80, 0.3);
    }

    .btn-download:hover {
      background: rgba(76, 175, 80, 0.5);
    }

    .empty-tickets {
      text-align: center;
      padding: 30px;
      color: rgba(255, 255, 255, 0.7);
    }

    /* === Butonlar === */
    .btn {
      display: inline-block;
      background: rgba(255, 255, 255, 0.25);
      border: none;
      border-radius: 10px;
      color: #fff;
      font-weight: 600;
      font-size: 15px;
      padding: 10px 25px;
      margin: 10px 5px;
      cursor: pointer;
      transition: 0.3s;
      text-decoration: none;
    }

    .btn:hover {
      background: rgba(255, 255, 255, 0.4);
      transform: translateY(-2px);
    }

    .btn-container {
      text-align: center;
      margin-top: 25px;
    }

    /* === Footer === */
    footer {
      margin-top: 30px;
      text-align: center;
      font-size: 13px;
      color: rgba(255, 255, 255, 0.7);
    }

    @media (max-width: 600px) {
      .dashboard {
        padding: 30px 25px;
      }
      .ticket-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
      }
    }
  </style>
</head>

<body>

  <div class="dashboard">
    <h1>Hoşgeldin, <?= wafReflect($_SESSION['full_name']) ?>!</h1>

    <?php if(isset($_GET['success'])): ?>
      <?php $success = SecurityModule::pass($_GET['success'], SecurityModule::MODE_PASSTHROUGH); ?>
      <div style="background: rgba(76, 175, 80, 0.2); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        <?php if($success === 'cancelled'): ?>
          ✅ Biletiniz başarıyla iptal edildi ve para iadeniz yapıldı!
        <?php elseif($success === 'purchased'): ?>
          ✅ Biletiniz başarıyla satın alındı!
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if(isset($_GET['error'])): ?>
      <?php $error = SecurityModule::pass($_GET['error'], SecurityModule::MODE_PASSTHROUGH); ?>
      <?php if($error === 'too_late'): ?>
        <div style="background: rgba(244, 67, 54, 0.2); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
          ❌ Sefer kalkışına 1 saatten az kaldığı için iptal edilemiyor!
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <div class="info">
      <p><strong>Email:</strong> <?= wafReflect($_SESSION['email']) ?></p>
      <p><strong>Bakiye:</strong> <?= wafReflect(number_format($_SESSION['balance'], 2)) ?> TL</p>
      <p><strong>Rol:</strong> <?= wafReflect($_SESSION['role']) ?></p>
      <p><strong>Giriş Zamanı:</strong> <?= wafReflect($_SESSION['login_datetime']) ?></p>
      <p><strong>Online Süresi:</strong> <?= wafReflect(getLoginDuration()) ?></p>
    </div>

    <!-- Biletlerim Bölümü -->
    <div class="tickets-section">
      <h2>🎟️ Satın Aldığım Biletler</h2>
      
      <?php if(count($my_tickets) > 0): ?>
        <?php foreach($my_tickets as $ticket):
          // İptal edilebilir mi kontrol et (1 saatten fazla var mı?)
          $departure_timestamp = strtotime($ticket['departure_time']);
          $current_timestamp = time();
          $time_diff = ($departure_timestamp - $current_timestamp) / 3600;
          $can_cancel = ($time_diff > 1 && $ticket['status'] == 'paid');
        ?>
          <div class="ticket-card">
            <div class="ticket-header">
              <div class="ticket-route">
                <?= wafReflect($ticket['departure_city']) ?> →
                <?= wafReflect($ticket['destination_city']) ?>
              </div>
              <div class="ticket-price"><?= wafReflect(number_format($ticket['price'], 0)) ?> ₺</div>
            </div>
            <div class="ticket-details">
              <div>🚌 <strong>Firma:</strong> <?= wafReflect($ticket['company_name']) ?></div>
              <div>💺 <strong>Koltuk:</strong> <?= wafReflect($ticket['seat_number']) ?></div>
              <div>🕐 <strong>Kalkış:</strong> <?= wafReflect(date('d.m.Y H:i', strtotime($ticket['departure_time']))) ?></div>
              <div>🚏 <strong>Biniş:</strong> <?= wafReflect(date('d.m.Y H:i', strtotime($ticket['actual_time']))) ?></div>
              <div>📅 <strong>Satın Alma:</strong> <?= wafReflect(date('d.m.Y H:i', strtotime($ticket['purchase_date']))) ?></div>
              <div>📊 <strong>Durum:</strong>
                <span style="<?= $ticket['status'] == 'paid' ? 'color: #4CAF50;' : 'color: #9E9E9E;' ?>">
                  <?= $ticket['status'] == 'paid' ? '✓ Ödendi' : '✗ İptal' ?>
                </span>
              </div>
            </div>
            <div class="ticket-actions">
              <a href="ticket_view.php?id=<?= wafReflect($ticket['id']) ?>" class="btn btn-download" target="_blank">
                📄 PDF İndir
              </a>
              <a href="trip_detail.php?id=<?= wafReflect($ticket['trip_id']) ?>" class="btn btn-secondary">
                ℹ️ Sefer Detayı
              </a>
              <?php if($can_cancel): ?>
                <form method="POST" style="display:inline;"
                      onsubmit="return confirm('Bu bileti iptal etmek istediğinize emin misiniz? Para iadesi yapılacaktır.')">
                  <input type="hidden" name="ticket_id" value="<?= wafReflect($ticket['id']) ?>">
                  <button type="submit" name="cancel_ticket" class="btn btn-cancel">
                    🚫 İptal Et
                  </button>
                </form>
              <?php elseif($ticket['status'] == 'paid' && $time_diff <= 1): ?>
                <span style="font-size: 12px; color: rgba(255,255,255,0.6);">
                  ⏰ İptal süresi doldu
                </span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-tickets">
          <p>Henüz bilet satın almadınız.</p>
          <p>Hemen bir sefer arayın ve yolculuğunuzu planlayın!</p>
        </div>
      <?php endif; ?>
    </div>

    <div class="btn-container">
      <a href="index.php" class="btn">🏠 Ana Sayfa</a>
      <a href="logout.php" class="btn">🚪 Çıkış Yap</a>
    </div>
  </div>

  <footer>
    © <?= wafReflect(date("Y")) ?> NoTicket | Akıllı Bilet Platformu
  </footer>

</body>
</html>