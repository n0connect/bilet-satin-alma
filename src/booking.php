<?php
/**
 * @file booking.php
 * @brief Bilet Satın Alma Sayfası
 *
 * Kullanıcıların koltuk seçerek bilet satın alabileceği sayfa.
 * Interaktif otobüs haritası ve bakiye kontrolü içerir.
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 *
 * @features
 *   - Interaktif koltuk seçimi
 *   - Bakiye kontrolü
 *   - Transaction support
 *   - UUID ticket generation
 *
 * @security
 *   - auth.php ile giriş kontrolü
 *   - SecurityModule::validateUUID() ile trip_id validation
 *   - Koltuk doluluk kontrolü
 *   - Bakiye yeterlilik kontrolü
 *   - Transaction rollback desteği
 *   - wafReflect() ile XSS koruması
 */

require_once 'auth.php';
require_once 'dbconnect.php';
require_once 'coupon_validator.php';
require_once '889b1769-5f97-4b94-ac58-69877e948de7/SecurityModule.php';

// Güncel bakiyeyi çek
$balance_stmt = $db->prepare("SELECT balance FROM User WHERE id = ?");
$balance_stmt->execute([$_SESSION['user_id']]);
$current_balance = $balance_stmt->fetchColumn();
if($current_balance !== false) {
    $_SESSION['balance'] = $current_balance;
}

$trip_id = $_GET['trip_id'] ?? null;

// Trip ID validasyonu (UUID format)
if (!$trip_id || !SecurityModule::validateUUID($trip_id)) {
    SecurityModule::blockRequest('Invalid trip_id format (expected UUID)', $trip_id);
}

// Sefer bilgilerini çek
$trip_stmt = $db->prepare("
    SELECT t.*, bc.name as company_name
    FROM Trips t
    JOIN Bus_Company bc ON t.company_id = bc.id
    WHERE t.id = ? AND datetime(t.departure_time) > datetime('now')
");
$trip_stmt->execute([$trip_id]);
$trip = $trip_stmt->fetch(PDO::FETCH_ASSOC);

if(!$trip) {
    header('Location: index.php=status=false');
    exit;
}

// Dolu koltukları çek
$seats_stmt = $db->prepare("
    SELECT seat_number
    FROM Tickets
    WHERE trip_id = ? AND status IN ('paid', 'reserved')
");
$seats_stmt->execute([$trip_id]);
$occupied_seats = $seats_stmt->fetchAll(PDO::FETCH_COLUMN);

// Mevcut koltuk sayısı
$available_count = $trip['capacity'] - count($occupied_seats);

// Bilet satın alma işlemi
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['purchase'])) {
    $selected_seat = (int)$_POST['seat_number'];
    
    // Kontroller
    if(in_array($selected_seat, $occupied_seats)) {
        $error = "Bu koltuk daha önce satın alınmış!";
    } elseif($_SESSION['balance'] < $trip['price']) {
        $error = "Yetersiz bakiye! Bakiyeniz: " . number_format($_SESSION['balance'], 2) . " ₺";
    } else {
        // Transaction başlat
        $db->beginTransaction();
        
        try {
            // Bilet oluştur (32 karakterlik UUID - kriptografik güvenli)
            $ticket_id = bin2hex(random_bytes(16));
            $insert_stmt = $db->prepare("
                INSERT INTO Tickets (id, trip_id, user_id, seat_number, status)
                VALUES (?, ?, ?, ?, 'paid')
            ");
            $insert_stmt->execute([$ticket_id, $trip_id, $_SESSION['user_id'], $selected_seat]);
            
            // Bakiyeden düş
            $update_balance = $db->prepare("UPDATE User SET balance = balance - ? WHERE id = ?");
            $update_balance->execute([$trip['price'], $_SESSION['user_id']]);
            
            // Commit
            $db->commit();
            
            // Session'daki bakiyeyi güncelle
            $_SESSION['balance'] -= $trip['price'];
            
            header('Location: dashboard.php?success=purchased');
            exit;
        } catch (Exception $e) {
            // Rollback
            $db->rollBack();
            $error = "İşlem başarısız oldu! Lütfen tekrar deneyin.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bilet Satın Al - NoTicket</title>

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
      max-width: 900px;
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

    .trip-info {
      background: rgba(30, 136, 229, 0.1);
      border-left: 4px solid #1E88E5;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;
    }

    .trip-info p {
      margin: 8px 0;
      color: #555;
    }

    .seat-selection {
      margin: 30px 0;
    }

    .bus-container {
      max-width: 700px;
      margin: 20px auto;
      background: white;
      border-radius: 16px;
      padding: 30px 20px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    }

    .bus-layout {
      display: flex;
      flex-direction: column;
      gap: 15px;
      margin: 20px 0;
    }

    .bus-row {
      display: grid;
      grid-template-columns: 50px 50px 30px 50px 50px;
      gap: 10px;
      justify-content: center;
    }

    .bus-front {
      text-align: center;
      padding: 15px;
      background: linear-gradient(135deg, #607D8B, #455A64);
      color: white;
      border-radius: 12px 12px 0 0;
      font-weight: 600;
      font-size: 14px;
    }

    .bus-aisle {
      display: flex;
      align-items: center;
      justify-content: center;
      color: #BDBDBD;
      font-size: 20px;
    }

    .seat {
      aspect-ratio: 1;
      border: 2px solid #ddd;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      cursor: pointer;
      transition: 0.2s;
      background: white;
      position: relative;
      font-size: 14px;
    }

    .seat.available {
      background: #E8F5E9;
      border-color: #4CAF50;
      color: #2E7D32;
    }

    .seat.available:hover {
      background: #C8E6C9;
      transform: scale(1.05);
    }

    .seat.selected {
      background: #1E88E5;
      color: white;
      border-color: #1565C0;
      box-shadow: 0 4px 12px rgba(30, 136, 229, 0.4);
    }

    .seat.occupied-male {
      background: #E0E0E0;
      color: #666;
      border-color: #9E9E9E;
      cursor: not-allowed;
    }

    .seat.occupied-female {
      background: #E0E0E0;
      color: #666;
      border-color: #9E9E9E;
      cursor: not-allowed;
    }

    .seat.blocked {
      background: #E0E0E0;
      color: #666;
      border-color: #9E9E9E;
      cursor: not-allowed;
      opacity: 0.6;
    }

    .seat::before {
      content: '';
      position: absolute;
      top: -4px;
      left: 50%;
      transform: translateX(-50%);
      width: 60%;
      height: 3px;
      background: currentColor;
      border-radius: 2px;
      opacity: 0.5;
    }

    .legend {
      display: flex;
      justify-content: center;
      gap: 20px;
      margin: 20px 0;
      font-size: 13px;
      flex-wrap: wrap;
    }

    .legend-item {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .legend-box {
      width: 35px;
      height: 35px;
      border-radius: 6px;
      border: 2px solid;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 12px;
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

    .btn-primary:disabled {
      background: #BDBDBD;
      cursor: not-allowed;
    }

    .btn-secondary {
      background: #E0E0E0;
      color: #333;
    }

    .btn-secondary:hover {
      background: #BDBDBD;
    }

    .error {
      background: #FFEBEE;
      color: #C62828;
      padding: 15px;
      border-radius: 8px;
      border-left: 4px solid #F44336;
      margin-bottom: 20px;
    }

    .actions {
      display: flex;
      gap: 10px;
      justify-content: center;
      margin-top: 30px;
    }

    .balance-warning {
      background: #FFF9C4;
      padding: 12px;
      border-radius: 8px;
      text-align: center;
      margin-bottom: 20px;
      color: #F57F17;
    }
  </style>
</head>
<body>

  <div class="container">
    <div class="card">
      <h2>🎫 Bilet Satın Al</h2>

      <?php if(isset($error)): ?>
        <div class="error"><?= wafReflect($error) ?></div>
      <?php endif; ?>

      <?php if($_SESSION['balance'] < $trip['price']): ?>
        <div class="balance-warning">
          ⚠️ Yetersiz bakiye! Mevcut bakiyeniz: <?= wafReflect(number_format($_SESSION['balance'], 2)) ?> ₺
        </div>
      <?php endif; ?>

      <div class="trip-info">
        <h3><?= wafReflect($trip['departure_city']) ?> → <?= wafReflect($trip['destination_city']) ?></h3>
        <p><strong>🚌 Firma:</strong> <?= wafReflect($trip['company_name']) ?></p>
        <p><strong>🕐 Kalkış:</strong> <?= wafReflect(date('d.m.Y H:i', strtotime($trip['departure_time']))) ?></p>
        <p><strong>🚏 Biniş:</strong> <?= wafReflect(date('d.m.Y H:i', strtotime($trip['actual_time']))) ?></p>
        <p><strong>💰 Fiyat:</strong> <span style="font-size: 20px; color: #1E88E5; font-weight: bold;"><?= wafReflect(number_format($trip['price'], 0)) ?> ₺</span></p>
        <p><strong>💺 Müsait Koltuk:</strong> <?= wafReflect($available_count) ?> / <?= wafReflect($trip['capacity']) ?></p>
      </div>

      <?php if($available_count > 0): ?>
        <div class="seat-selection">
          <h3 style="text-align: center;">Koltuk Seçimi</h3>
          
          <div class="legend">
            <div class="legend-item">
              <div class="legend-box" style="background: #E8F5E9; border-color: #4CAF50; color: #2E7D32;">1</div>
              <span>Boş Koltuk</span>
            </div>
            <div class="legend-item">
              <div class="legend-box" style="background: #1E88E5; border-color: #1565C0; color: white;">2</div>
              <span>Seçili</span>
            </div>
            <div class="legend-item">
              <div class="legend-box" style="background: #E0E0E0; border-color: #9E9E9E; color: #666;">3</div>
              <span>Dolu</span>
            </div>
          </div>

          <form method="POST" id="bookingForm">
            <input type="hidden" name="seat_number" id="selectedSeat" value="">
            
            <div class="bus-container">
              <div class="bus-front">🚌 ŞOFÖR</div>
              
              <div class="bus-layout">
                <?php
                $seats_per_row = 4;
                $rows = ceil($trip['capacity'] / $seats_per_row);

                for($row = 0; $row < $rows; $row++):
                  $seat1 = ($row * $seats_per_row) + 1;
                  $seat2 = ($row * $seats_per_row) + 2;
                  $seat3 = ($row * $seats_per_row) + 3;
                  $seat4 = ($row * $seats_per_row) + 4;
                ?>
                  <div class="bus-row">
                    <?php
                    // Sol pencere
                    if($seat1 <= $trip['capacity']):
                      $is_occupied = in_array($seat1, $occupied_seats);
                      $seat_class = $is_occupied ? 'occupied-male' : 'available';
                    ?>
                      <div class="seat <?= wafReflect($seat_class) ?>"
                           data-seat="<?= wafReflect($seat1) ?>"
                           onclick="<?= !$is_occupied ? 'selectSeat(this)' : '' ?>">
                        <?= wafReflect($seat1) ?>
                      </div>
                    <?php else: ?>
                      <div></div>
                    <?php endif; ?>

                    <?php
                    // Sol koridor
                    if($seat2 <= $trip['capacity']):
                      $is_occupied = in_array($seat2, $occupied_seats);
                      $seat_class = $is_occupied ? 'occupied-male' : 'available';
                    ?>
                      <div class="seat <?= wafReflect($seat_class) ?>"
                           data-seat="<?= wafReflect($seat2) ?>"
                           onclick="<?= !$is_occupied ? 'selectSeat(this)' : '' ?>">
                        <?= wafReflect($seat2) ?>
                      </div>
                    <?php else: ?>
                      <div></div>
                    <?php endif; ?>

                    <!-- Koridor -->
                    <div class="bus-aisle">│</div>

                    <?php
                    // Sağ koridor
                    if($seat3 <= $trip['capacity']):
                      $is_occupied = in_array($seat3, $occupied_seats);
                      $seat_class = $is_occupied ? 'occupied-male' : 'available';
                    ?>
                      <div class="seat <?= wafReflect($seat_class) ?>"
                           data-seat="<?= wafReflect($seat3) ?>"
                           onclick="<?= !$is_occupied ? 'selectSeat(this)' : '' ?>">
                        <?= wafReflect($seat3) ?>
                      </div>
                    <?php else: ?>
                      <div></div>
                    <?php endif; ?>

                    <?php
                    // Sağ pencere
                    if($seat4 <= $trip['capacity']):
                      $is_occupied = in_array($seat4, $occupied_seats);
                      $seat_class = $is_occupied ? 'occupied-male' : 'available';
                    ?>
                      <div class="seat <?= wafReflect($seat_class) ?>"
                           data-seat="<?= wafReflect($seat4) ?>"
                           onclick="<?= !$is_occupied ? 'selectSeat(this)' : '' ?>">
                        <?= wafReflect($seat4) ?>
                      </div>
                    <?php else: ?>
                      <div></div>
                    <?php endif; ?>
                  </div>
                <?php endfor; ?>
              </div>
            </div>

            <div class="actions">
              <a href="index.php" class="btn btn-secondary">İptal</a>
              <button type="submit" name="purchase" class="btn btn-primary" id="purchaseBtn" disabled>
                Satın Al
              </button>
            </div>
          </form>
        </div>
      <?php else: ?>
        <div style="text-align: center; padding: 40px;">
          <p style="color: #666; font-size: 18px;">😔 Bu sefer için müsait koltuk kalmamıştır.</p>
          <a href="index.php" class="btn btn-primary" style="margin-top: 20px;">Ana Sayfaya Dön</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    let selectedSeatElement = null;

    function selectSeat(element) {
      const seatNumber = element.getAttribute('data-seat');
      
      // Önceki seçimi temizle
      if(selectedSeatElement) {
        selectedSeatElement.classList.remove('selected');
      }
      
      // Yeni seçimi işaretle
      element.classList.add('selected');
      selectedSeatElement = element;
      
      // Hidden input'u güncelle
      document.getElementById('selectedSeat').value = seatNumber;
      
      // Satın al butonunu aktif et
      document.getElementById('purchaseBtn').disabled = false;
    }
  </script>

</body>
</html>