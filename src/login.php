<?php
/**
 * @file login.php
 * @brief Kullanıcı Giriş Sayfası
 *
 * Normal kullanıcıların giriş yapabileceği form sayfası.
 * POST verilerini checkUser.php'ye gönderir.
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 *
 * @security
 *   - Session regeneration ile hijacking önleme
 *   - wafReflect() ile XSS koruması
 *   - Rol bazlı yönlendirme
 */

require_once 'session_helper.php';
require_once '889b1769-5f97-4b94-ac58-69877e948de7/SecurityModule.php';
startSession();

// Zaten giriş yapmış admin varsa dashboard'a yönlendir
if (isLoggedIn() && hasRole('user')) {
    header('Location: dashboard.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>🎫 NoTicket - Giriş</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
  <style>
    /* === 🎨 Genel Ayarlar === */
    body {
      background: linear-gradient(135deg, #5C6BC0, #1E88E5); /* mavi-mor tonları */
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Segoe UI', Roboto, sans-serif;
      margin: 0;
      overflow: hidden;
    }

    /* === 🔮 Blur (Glassmorphism) Panel === */
    .login-panel {
      background: rgba(255, 255, 255, 0.15);
      border-radius: 20px;
      box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      padding: 40px 35px;
      width: 100%;
      max-width: 420px;
      color: #fff;
      transition: all 0.3s ease;
    }

    .login-panel:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 40px rgba(31, 38, 135, 0.45);
    }

    .login-panel h2 {
      text-align: center;
      margin-bottom: 25px;
      font-weight: 600;
      letter-spacing: 0.5px;
      color: #ffffff;
    }

    /* === Form Elemanları === */
    .form-control {
      border-radius: 10px;
      border: none;
      padding: 12px;
      font-size: 15px;
      background: rgba(255, 255, 255, 0.25);
      color: #fff;
      transition: 0.2s;
    }

    .form-control::placeholder {
      color: rgba(255, 255, 255, 0.7);
    }

    .form-control:focus {
      outline: none;
      background: rgba(255, 255, 255, 0.35);
      box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.2);
    }

    /* === Buton === */
    .btn-primary {
      background: rgba(255, 255, 255, 0.25);
      border: none;
      color: #fff;
      font-weight: 600;
      transition: background 0.3s;
      border-radius: 10px;
      padding: 10px;
    }

    .btn-primary:hover {
      background: rgba(255, 255, 255, 0.4);
    }

    /* === Register Link & Alert === */
    .register-link {
      text-align: center;
      margin-top: 15px;
      color: #dcdcdc;
    }

    .register-link a {
      color: #ffffff;
      text-decoration: underline;
    }

    .alert {
      border-radius: 10px;
      font-size: 14px;
      margin-top: 15px;
    }
  </style>
    </style>
</head>
<body>
    <div class="login-panel">
        <h2>🎫 NoTicket - Giriş</h2>
        <form action="checkUser.php" method="POST">
            <div class="mb-3">
                <input type="text" name="email" class="form-control" placeholder="Email adresi" required>
            </div>
            <div class="mb-3">
                <input type="password" name="password" class="form-control" placeholder="Şifre" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Giriş</button>
            
            <div class="register-link">
                <p>Hesabın yok mu? <a href="register.php">Kayıt ol</a></p>
            </div>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger mt-3"><?= wafReflect($_SESSION['error']) ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success mt-3"><?= wafReflect($_SESSION['success']) ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <div class="mt-4" style="color:darkslategray">
                <p>Email: tilki@test.com<br>Şifre: user-123</p>
                <hr>
                <p>Email: dogubey@test.com<br>Şifre: user-123</p>
            </div>
        </form>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>

