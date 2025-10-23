<?php
/**
 * @file logout.php
 * @brief Kullanıcı Çıkış İşleyicisi
 *
 * Session ve cookie temizleme ile güvenli çıkış yapar.
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 *
 * @security
 *   - Session destroy
 *   - Cookie temizleme
 *   - Ana sayfaya yönlendirme
 */

require_once 'session_helper.php';
secureLogout();
header('Location: login.php?status=true');
exit;
?>