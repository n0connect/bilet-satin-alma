<?php
/**
 * @file register.php
 * @brief Kullanıcı Kayıt Sayfası
 *
 * Yeni kullanıcı kaydı için form sayfası.
 * POST verilerini addUser.php'ye gönderir.
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 *
 * @security
 *   - Form verileri addUser.php'de validate edilir
 *   - wafReflect() ile XSS koruması
 */

require_once '889b1769-5f97-4b94-ac58-69877e948de7/SecurityModule.php';
session_start();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>🎫 NoTicket - Kayıt Ol</title>
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
    .register-panel {
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

    .register-panel:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 40px rgba(31, 38, 135, 0.45);
    }

    .register-panel h2 {
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
    .login-link {
      text-align: center;
      margin-top: 15px;
      color: #dcdcdc;
    }

    .login-link a {
      color: #ffffff;
      text-decoration: underline;
    }

    .alert {
      border-radius: 10px;
      font-size: 14px;
      margin-top: 15px;
    }
  </style>
</head>

<body>
  <div class="register-panel">
    <h2>🎫 NoTicket - Kayıt Ol</h2>

    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-danger"><?= wafReflect($_SESSION['error']) ?></div>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form action="addUser.php" method="POST">
      <div class="mb-3">
        <input type="text" name="full_name" class="form-control" placeholder="Ad Soyad" required>
      </div>
      <div class="mb-3">
        <input type="email" name="email" class="form-control" placeholder="Email adresi" required>
      </div>
      <div class="mb-3">
        <input type="password" name="password" class="form-control" placeholder="Şifre" required>
      </div>

      <button type="submit" class="btn btn-primary w-100">Kayıt Ol</button>
    </form>

    <div class="login-link">
      <p>Zaten hesabın var mı? <a href="login.php">Giriş yap</a></p>
    </div>
  </div>

  <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
