<?php
/**
 * Simplified Secure 403 Page
 * Integrated with wafReflect() for safe dynamic reflection
 */

require_once __DIR__ . '/SecurityModule.php';

// Reflect all variables securely
$incident = SecurityModule::reflect($waf_incident_id ?? 'UNKNOWN');
$time     = SecurityModule::reflect($waf_timestamp ?? date('Y-m-d H:i:s'));
$ip       = SecurityModule::reflect($waf_ip ?? 'Unknown');
$reason   = SecurityModule::reflect($waf_threat_reason ?? 'Security policy violation');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>403 - Erişim Engellendi</title>
<style>
body {
  font-family: system-ui, sans-serif;
  background: #fafafa;
  color: #1f2937;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  margin: 0;
}
.wrapper {
  background: #fff;
  border-radius: 12px;
  max-width: 640px;
  width: 100%;
  padding: 2rem;
  box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}
.header {
  text-align: center;
  border-bottom: 3px solid #f87171;
  padding-bottom: 1rem;
  margin-bottom: 1.5rem;
}
.header .icon { font-size: 3rem; }
.header h1 { font-size: 1.5rem; margin: .5rem 0; color: #b91c1c; }
.alert {
  background: #fef2f2;
  border-left: 4px solid #f87171;
  padding: 1rem;
  border-radius: 6px;
  margin-bottom: 1.5rem;
}
.details {
  background: #f9fafb;
  border-radius: 8px;
  padding: 1rem;
  font-size: .9rem;
}
.details div {
  display: flex;
  justify-content: space-between;
  border-bottom: 1px solid #e5e7eb;
  padding: .5rem 0;
}
.details div:last-child { border-bottom: none; }
.details strong { color: #111827; }
.reason {
  margin-top: 1.5rem;
  background: #fff;
  border: 1px solid #fca5a5;
  border-radius: 8px;
  padding: 1rem;
  color: #7f1d1d;
  font-family: ui-monospace, monospace;
  word-break: break-word;
}
.footer {
  margin-top: 1.5rem;
  font-size: .85rem;
  color: #6b7280;
  text-align: center;
}
.footer a {
  color: #2563eb;
  text-decoration: none;
}
</style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      <div class="icon">🔒</div>
      <h1>Erişim Engellendi</h1>
      <p>Web Application Firewall</p>
    </div>

    <div class="alert">
      Sistemimiz, gönderdiğiniz istekte potansiyel bir güvenlik tehdidi tespit etti. 
    Bu nedenle, <strong>Web Application Firewall (WAF)</strong> tarafından otomatik olarak engellendiniz.
    </div>

    <div class="details">
      <div><strong>Olay ID:</strong> <span><?= SecurityModule::reflect($incident) ?></span></div>
      <div><strong>Zaman:</strong> <span><?= SecurityModule::reflect($time) ?></span></div>
      <div><strong>IP:</strong> <span><?= SecurityModule::reflect($ip) ?></span></div>
      <div><strong>Tehdit:</strong> <span style="color:#dc2626;font-weight:600;">Yüksek</span></div>
    </div>

    <div class="reason">
      🔍 <?= SecurityModule::reflect($reason) ?>
    </div>

    <div class="footer">
      Olay kimliği ve içeriği güvenlik için kaydedildi. Yardım için
      <a href="mailto:security@noticket.local">security@noticket.local</a> adresine başvurun.
    </div>
  </div>
</body>
</html>
