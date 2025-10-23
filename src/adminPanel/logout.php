<?php
/**
 * @file adminPanel/logout.php
 * @brief Admin Çıkış İşlemi
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 */

// adminPanel/logout.php
require_once '../session_helper.php';

secureLogout();
header('Location: login.php');
exit;
?>