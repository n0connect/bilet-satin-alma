<?php
/**
 * @file ticket_view.php
 * @brief Bilet Görüntüleme Sayfası (PDF View)
 *
 * Satın alınan biletin detaylarını görüntüler. PDF formatında indirilebilir
 * bilet bilgilerini gösterir.
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 *
 * @parameters
 *   - id: Ticket UUID (GET)
 *
 * @security
 *   - auth.php ile giriş kontrolü
 *   - SecurityModule::validateUUID() ile ID validation
 *   - User ownership kontrolü (sadece kendi biletini görebilir)
 *   - wafReflect() ile XSS koruması
 */

require_once 'auth.php';
require_once 'dbconnect.php';
require_once '889b1769-5f97-4b94-ac58-69877e948de7/SecurityModule.php';

$ticket_id = $_GET['id'] ?? null;

if(!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    die();
}

// Ticket ID validasyonu (UUID format)
if (!$ticket_id || !SecurityModule::validateUUID($ticket_id)) {
    SecurityModule::blockRequest('Invalid ticket_id format (expected UUID)', $ticket_id);
}

// Bilet bilgilerini çek
$stmt = $db->prepare("
    SELECT 
        tk.id,
        tk.seat_number,
        tk.status,
        tk.created_at,
        t.departure_city,
        t.destination_city,
        t.departure_time,
        t.actual_time,
        t.price,
        t.capacity,
        bc.name as company_name,
        u.full_name,
        u.email
    FROM Tickets tk
    JOIN Trips t ON tk.trip_id = t.id
    JOIN Bus_Company bc ON t.company_id = bc.id
    JOIN User u ON tk.user_id = u.id
    WHERE tk.id = ? AND tk.user_id = ?
");
$stmt->execute([$ticket_id, $_SESSION['user_id']]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$ticket) {
    header('Location: dashboard.php?status=ticket_end');
    exit;
}

// QR kod için basit veri (base64 encode edilmiş, güvenli)
$qr_data = base64_encode($ticket_id);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bilet - <?= wafReflect(substr($ticket_id, 0, 8)) ?></title>
  
  <style>
    @media print {
      .no-print {
        display: none !important;
      }
      body {
        background: white !important;
      }
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      background: #f5f5f5;
      padding: 20px;
    }

    .container {
      max-width: 800px;
      margin: 0 auto;
    }

    .ticket {
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      overflow: hidden;
      margin-bottom: 20px;
    }

    .ticket-header {
      background: linear-gradient(135deg, #1E88E5, #1565C0);
      color: white;
      padding: 30px;
      position: relative;
    }

    .ticket-header::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 0;
      right: 0;
      height: 20px;
      background: white;
      border-radius: 50% 50% 0 0 / 100% 100% 0 0;
    }

    .logo {
      font-size: 32px;
      font-weight: 700;
      margin-bottom: 10px;
    }

    .company-name {
      font-size: 18px;
      opacity: 0.9;
    }

    .ticket-body {
      padding: 40px 30px;
    }

    .route-section {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 40px;
      padding-bottom: 30px;
      border-bottom: 2px dashed #E0E0E0;
    }

    .city {
      text-align: center;
      flex: 1;
    }

    .city-name {
      font-size: 28px;
      font-weight: 700;
      color: #1E88E5;
      margin-bottom: 5px;
    }

    .city-label {
      font-size: 13px;
      color: #999;
      text-transform: uppercase;
    }

    .arrow {
      font-size: 40px;
      color: #BDBDBD;
      margin: 0 20px;
    }

    .details-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 25px;
      margin-bottom: 30px;
    }

    .detail-item {
      display: flex;
      flex-direction: column;
    }

    .detail-label {
      font-size: 12px;
      color: #999;
      text-transform: uppercase;
      margin-bottom: 5px;
      letter-spacing: 0.5px;
    }

    .detail-value {
      font-size: 18px;
      font-weight: 600;
      color: #333;
    }

    .seat-highlight {
      background: linear-gradient(135deg, #1E88E5, #1565C0);
      color: white;
      padding: 20px;
      border-radius: 12px;
      text-align: center;
      margin-bottom: 30px;
    }

    .seat-number {
      font-size: 48px;
      font-weight: 700;
      margin-bottom: 5px;
    }

    .seat-label {
      font-size: 14px;
      opacity: 0.9;
    }

    .passenger-section {
      background: rgba(30, 136, 229, 0.05);
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 30px;
    }

    .qr-section {
      text-align: center;
      padding: 30px;
      background: #FAFAFA;
      border-radius: 8px;
    }

    .qr-code {
      background: white;
      padding: 20px;
      border-radius: 8px;
      display: inline-block;
      border: 2px dashed #E0E0E0;
    }

    .ticket-id {
      font-family: 'Courier New', monospace;
      font-size: 12px;
      color: #999;
      margin-top: 15px;
    }

    .status-badge {
      display: inline-block;
      padding: 8px 20px;
      border-radius: 20px;
      font-size: 14px;
      font-weight: 600;
      margin-top: 10px;
    }

    .status-active {
      background: #E8F5E9;
      color: #2E7D32;
    }

    .status-cancelled {
      background: #FFEBEE;
      color: #C62828;
    }

    .actions {
      display: flex;
      gap: 10px;
      justify-content: center;
      margin-top: 20px;
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

    @media (max-width: 600px) {
      .details-grid {
        grid-template-columns: 1fr;
      }
      
      .route-section {
        flex-direction: column;
        gap: 20px;
      }
      
      .arrow {
        transform: rotate(90deg);
        margin: 0;
      }
    }
  </style>
</head>
<body>

  <div class="container">
    <div class="ticket">
      <div class="ticket-header">
        <div class="logo">🎫 NoTicket</div>
        <div class="company-name"><?= wafReflect($ticket['company_name']) ?></div>
      </div>

      <div class="ticket-body">
        <div class="route-section">
          <div class="city">
            <div class="city-name"><?= wafReflect($ticket['departure_city']) ?></div>
            <div class="city-label">Kalkış</div>
          </div>
          <div class="arrow">→</div>
          <div class="city">
            <div class="city-name"><?= wafReflect($ticket['destination_city']) ?></div>
            <div class="city-label">Varış</div>
          </div>
        </div>

        <div class="seat-highlight">
          <div class="seat-number"><?= wafReflect($ticket['seat_number']) ?></div>
          <div class="seat-label">KOLTUK NUMARASI</div>
        </div>

        <div class="passenger-section">
          <div class="detail-item">
            <div class="detail-label">👤 Yolcu Adı</div>
            <div class="detail-value"><?= wafReflect($ticket['full_name']) ?></div>
          </div>
        </div>

        <div class="details-grid">
          <div class="detail-item">
            <div class="detail-label">🕐 Kalkış Zamanı</div>
            <div class="detail-value"><?= wafReflect(date('d.m.Y H:i', strtotime($ticket['departure_time']))) ?></div>
          </div>
          <div class="detail-item">
            <div class="detail-label">🚏 Biniş Zamanı</div>
            <div class="detail-value"><?= wafReflect(date('d.m.Y H:i', strtotime($ticket['actual_time']))) ?></div>
          </div>
          <div class="detail-item">
            <div class="detail-label">💰 Ücret</div>
            <div class="detail-value"><?= wafReflect(number_format($ticket['price'], 2)) ?> ₺</div>
          </div>
          <div class="detail-item">
            <div class="detail-label">📅 Satın Alma</div>
            <div class="detail-value"><?= wafReflect(date('d.m.Y H:i', strtotime($ticket['created_at']))) ?></div>
          </div>
        </div>

        <div class="qr-section">
          <div class="qr-code">
            <svg width="150" height="150" style="background: white;">
              <rect width="150" height="150" fill="white"/>
              <text x="75" y="75" text-anchor="middle" font-size="12" fill="#666">
                QR CODE
              </text>
              <text x="75" y="95" text-anchor="middle" font-size="10" fill="#999">
                <?= wafReflect(substr($ticket_id, 0, 8)) ?>
              </text>
            </svg>
          </div>
          <div class="ticket-id">Bilet ID: <?= wafReflect($ticket_id) ?></div>
          <div>
            <span class="status-badge status-<?= wafReflect($ticket['status']) ?>">
              <?= $ticket['status'] == 'active' ? '✓ Aktif' : '✗ İptal Edildi' ?>
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="actions no-print">
      <button onclick="window.print()" class="btn btn-primary">🖨️ Yazdır / PDF Kaydet</button>
      <a href="dashboard.php" class="btn btn-secondary">← Panele Dön</a>
    </div>
  </div>

</body>
</html>