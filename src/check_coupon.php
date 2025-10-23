<?php
/**
 * @file check_coupon.php
 * @brief Kupon Doğrulama JSON API
 *
 * AJAX çağrısı ile kupon kodunun geçerliliğini kontrol eden API endpoint.
 * JSON response döner.
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 *
 * @api
 *   Method: POST
 *   Content-Type: application/json
 *   Parameters:
 *     - coupon_code: Kupon kodu (string)
 *     - price: Bilet fiyatı (float)
 *     - trip_id: Sefer UUID (string)
 *
 * @response
 *   {
 *     "valid": boolean,
 *     "discount": float,
 *     "final_price": float,
 *     "message": string,
 *     "error": string
 *   }
 *
 * @security
 *   - SecurityModule::pass() ile input validation
 *   - SecurityModule::validateFloat() ile fiyat kontrolü
 *   - SecurityModule::validateUUID() ile trip_id kontrolü
 *   - Company_id bazlı yetkilendirme
 *   - JSON injection koruması (validated data)
 */

require_once 'auth.php';
require_once 'dbconnect.php';
require_once 'coupon_validator.php';

header('Content-Type: application/json');

// POST kontrolü
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['valid' => false, 'error' => 'Geçersiz istek']);
    exit;
}

$coupon_code = SecurityModule::pass($_POST['coupon_code'] ?? '', SecurityModule::MODE_PASSTHROUGH);
$price_input = SecurityModule::pass($_POST['price'] ?? '0', SecurityModule::MODE_PASSTHROUGH);
$price = SecurityModule::validateFloat($price_input, 0.01, 999999) ? (float)$price_input : 0;

// CRITICAL FIX: trip_id alarak o seferin company_id'sini çek
$trip_id_input = SecurityModule::pass($_POST['trip_id'] ?? '', SecurityModule::MODE_PASSTHROUGH);

// Input validation
if(empty($coupon_code) || $price <= 0 || empty($trip_id_input)) {
    echo json_encode(['valid' => false, 'error' => 'Geçersiz parametreler']);
    exit;
}

// Trip ID validasyonu
if (!SecurityModule::validateUUID($trip_id_input)) {
    echo json_encode(['valid' => false, 'error' => 'Geçersiz sefer ID']);
    exit;
}

// Seferin company_id'sini öğren
$trip_stmt = $db->prepare("SELECT company_id FROM Trips WHERE id = ?");
$trip_stmt->execute([$trip_id_input]);
$trip_company_id = $trip_stmt->fetchColumn();

if ($trip_company_id === false) {
    echo json_encode(['valid' => false, 'error' => 'Sefer bulunamadı']);
    exit;
}

// Kupon doğrula - SADECE bu firmaya ait veya global kuponlar kabul edilir
$result = validateCoupon($db, $coupon_code, $_SESSION['user_id'], $price, $trip_company_id);

echo json_encode($result);
?>