<?php
/**
 * @file adminPanel/login.php
 * @brief Admin Giriş Sayfası
 *
 * Sistem yöneticilerinin giriş yapabileceği form sayfası.
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 */

require_once '../session_helper.php';
require_once '../889b1769-5f97-4b94-ac58-69877e948de7/SecurityModule.php';
startSession();

// Zaten giriş yapmış admin varsa dashboard'a yönlendir
if (isLoggedIn() && hasRole('admin')) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎫 NoTicket - Admin Girişi</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">

    <style>
        body {
            background: linear-gradient(135deg, #0d4288ff, #1e4067ff);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-panel {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 25px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        .login-panel h2 {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-link {
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    
    <div class="login-panel">
        <h2>Admin Giriş Paneli</h2>
        <form action="checkAdmin.php" method="POST">
            <div class="mb-3">
                <input type="text" name="email" class="form-control" placeholder="Email adresi" required>
            </div>
            <div class="mb-3">
                <input type="password" name="password" class="form-control" placeholder="Şifre" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Giriş</button>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger mt-3"><?= wafReflect($_SESSION['error'], ENT_QUOTES | ENT_HTML5, 'UTF-8', false) ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success mt-3"><?= wafReflect($_SESSION['success'], ENT_QUOTES | ENT_HTML5, 'UTF-8', false) ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <div class="mt-4" style="color:slateblue;">
                <p>Email: systemadmin@noticket.com<br>Şifre: company-123</p>
                <hr>
            </div>
        </form>
    </div>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>