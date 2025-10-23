<?php
/**
 * @file coupon_validator.php
 * @brief Kupon Doğrulama ve Uygulama Sistemi
 *
 * Kupon kodlarının geçerliliğini kontrol eden, indirim hesaplayan ve
 * kupon kullanımını kaydeden yardımcı fonksiyonlar.
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 *
 * @functions
 *   - validateCoupon($db, $coupon_code, $user_id, $original_price, $company_id): Kupon doğrulama
 *   - applyCoupon($db, $coupon_id, $user_id): Kupon kullanımını kaydet
 *   - sanitizeCouponCode($code): Kupon kodu temizleme
 *
 * @security
 *   - SecurityModule::pass() ile input validation
 *   - Regex format kontrolü (A-Z0-9, 4-20 chars)
 *   - Company_id bazlı yetkilendirme (firma kuponları)
 *   - Kullanım limiti kontrolü
 *   - Expire date kontrolü
 *   - User ownership kontrolü (bir kullanıcı bir kez kullanabilir)
 *   - Prepared statements
 */

require_once __DIR__ . '/889b1769-5f97-4b94-ac58-69877e948de7/SecurityModule.php';

function validateCoupon($db, $coupon_code, $user_id, $original_price, $company_id = null) {
    // Input sanitization with WAF
    $coupon_code = strtoupper(trim($coupon_code));
    $coupon_code = SecurityModule::pass($coupon_code, SecurityModule::MODE_PASSTHROUGH);
    
    // Kupon kodu formatı kontrolü (sadece harf ve rakam, 4-20 karakter)
    if (!preg_match('/^[A-Z0-9]{4,20}$/', $coupon_code)) {
        return [
            'valid' => false,
            'error' => 'Geçersiz kupon formatı!',
            'discount' => 0
        ];
    }
    
    // Kupon bilgilerini al - SADECE belirtilen firmaya ait kuponlar (veya global kuponlar)
    // CRITICAL FIX: company_id kontrolü eklendi - başka firmaların kuponları kullanılamaz!
    if ($company_id !== null) {
        $stmt = $db->prepare("
            SELECT
                c.id,
                c.code,
                c.discount,
                c.usage_time,
                c.expire_date,
                c.company_id,
                (SELECT COUNT(*) FROM User_Coupons WHERE coupon_id = c.id) as total_uses
            FROM Coupons c
            WHERE c.code = ? AND (c.company_id = ? OR c.company_id IS NULL)
        ");
        $stmt->execute([$coupon_code, $company_id]);
    } else {
        // Eğer company_id verilmediyse (eski davranış için fallback)
        $stmt = $db->prepare("
            SELECT
                c.id,
                c.code,
                c.discount,
                c.usage_time,
                c.expire_date,
                c.company_id,
                (SELECT COUNT(*) FROM User_Coupons WHERE coupon_id = c.id) as total_uses
            FROM Coupons c
            WHERE c.code = ?
        ");
        $stmt->execute([$coupon_code]);
    }
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Kupon bulunamadı
    if (!$coupon) {
        return [
            'valid' => false,
            'error' => 'Kupon bulunamadı!',
            'discount' => 0
        ];
    }
    
    // Kullanım limiti kontrolü
    if ($coupon['total_uses'] >= $coupon['usage_time']) {
        return [
            'valid' => false,
            'error' => 'Kupon kullanım limiti dolmuş!',
            'discount' => 0
        ];
    }
    
    // Geçerlilik tarihi kontrolü
    if (strtotime($coupon['expire_date']) < time()) {
        return [
            'valid' => false,
            'error' => 'Kuponun süresi dolmuş!',
            'discount' => 0
        ];
    }
    
    // Kullanıcı daha önce bu kuponu kullanmış mı?
    $user_check = $db->prepare("
        SELECT COUNT(*) FROM User_Coupons 
        WHERE user_id = ? AND coupon_id = ?
    ");
    $user_check->execute([$user_id, $coupon['id']]);
    $already_used = $user_check->fetchColumn();
    
    if ($already_used > 0) {
        return [
            'valid' => false,
            'error' => 'Bu kuponu daha önce kullandınız!',
            'discount' => 0
        ];
    }
    
    // İndirim miktarını hesapla
    $discount_amount = min($coupon['discount'], $original_price);
    $final_price = max($original_price - $discount_amount, 0);
    
    return [
        'valid' => true,
        'coupon_id' => $coupon['id'],
        'discount' => $discount_amount,
        'final_price' => $final_price,
        'message' => "✓ Kupon uygulandı! {$discount_amount} ₺ indirim"
    ];
}

function applyCoupon($db, $coupon_id, $user_id) {
    // Kuponu kullanıldı olarak işaretle
    try {
        $stmt = $db->prepare("
            INSERT INTO User_Coupons (user_id, coupon_id, used_at)
            VALUES (?, ?, datetime('now'))
        ");
        $stmt->execute([$user_id, $coupon_id]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function sanitizeCouponCode($code) {
    // Sadece büyük harf ve rakam
    return strtoupper(preg_replace('/[^A-Z0-9]/', '', $code));
}
?>