<?php
/**
 * @file companyPanel/logout.php
 * @brief Firma Çıkış İşlemi
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 */

// companyPanel/logout.php ve adminPanel/logout.php (ikisi de aynı)
require_once '../session_helper.php';

secureLogout();
header('Location: login.php');
exit;
?>