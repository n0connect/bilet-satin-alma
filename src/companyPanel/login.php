<?php
/**
 * @file companyPanel/login.php
 * @brief Firma Giriş Sayfası
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
if (isLoggedIn() && hasRole('company')) {
    header('Location: /companyPanel/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>🎫 NoTicket - Firma Girişi</title>
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
        .login-link {
            text-align: center;
            margin-top: 15px;
        }
    </style>

</head>
<body>
<div class="login-panel">
    <h2>Firma Giriş Paneli</h2>

    <form action="checkCompany.php" method="POST">
      <div class="mb-3">
        <input type="text" name="email" class="form-control" placeholder="Email adresi" required>
      </div>
      <div class="mb-3">
        <input type="password" name="password" class="form-control" placeholder="Şifre" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Giriş</button>
      
            <div class="login-link">
                <p>Kullanıcı Girişi <a href="../login.php">Giriş Yap</a></p>
            </div>
      
      <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= wafReflect($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>

      <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= wafReflect($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
      <?php endif; ?>
      <div class="mt-4">
        <p style="color:slateblue;">Email: stardiyarbakirturizm@noticket.com</p>
        <p style="color:slateblue;">Email: kamilkocturizm@noticket.com</p>
        <p style="color:slateblue;">Email: ispartapetrolturizm@noticket.com</p>
        <p style="color:slateblue;">Email: metroturizm@noticket.com</p>  
        <hr>
        <p style="color:slateblue;">Password: company-123</p>

        <hr>
      </div>
    </form>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>