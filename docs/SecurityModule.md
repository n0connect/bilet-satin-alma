# SecurityModule.php - WAF Dokümantasyonu

## Genel Bakış
Pure WAF Module - Web Application Firewall ve gelişmiş validasyon sistemi. Whitelist tabanlı yaklaşım ile güvenlik sağlar.

## Ana Özellikler

### 1. Whitelist Tabanlı Koruma
- Sadece güvenli karakterlere izin verir
- Blacklist yerine whitelist yaklaşımı
- False positive oranını minimize eder

### 2. Çoklu Mod Desteği
```php
MODE_STRICT     // Sadece alfanumerik + Türkçe karakterler
MODE_EMAIL      // Email için @ ve özel karakterler
MODE_PASSWORD   // Şifre için güvenli semboller  
MODE_TEXT       // Normal metin için temel noktalama
MODE_PASSTHROUGH // Minimum engelleme
```

### 3. Otomatik Threat Detection
- SQL Injection pattern algılama
- XSS payload tespiti  
- Command Injection koruması
- Path Traversal engelleme
- Çoklu encoding bypass algılama

## Temel Kullanım

### Sanitize Mod (Varsayılan)
```php
$safe_input = SecurityModule::sanitize($_POST['data'], SecurityModule::MODE_TEXT);
$safe_email = SecurityModule::sanitize($_POST['email'], SecurityModule::MODE_EMAIL);
```

### Pass-through Mod
```php
$checked_input = SecurityModule::pass($_POST['data'], SecurityModule::MODE_PASSTHROUGH);
```

### Reflect Mod (Output için)
```php
echo SecurityModule::reflect($user_data);
```

## Validasyon Metodları

### İsim Validasyonu
```php
SecurityModule::validateName($name, $minLength = 2, $maxLength = 50)
```
- Türkçe karakter desteği
- Özel karakter kontrolü (', -, .)
- Uzunluk sınırları

### Email Validasyonu  
```php
SecurityModule::validateEmail($email)
```
- RFC 5322 uyumlu
- PHP filter_var kullanımı
- Uzunluk kontrolü (3-254 karakter)

### Sayısal Validasyon
```php
SecurityModule::validateInteger($value, $min = null, $max = null)
SecurityModule::validateFloat($value, $min = null, $max = null)
```

### UUID Validasyonu
```php
SecurityModule::validateUUID($uuid) // 32 karakter hex format
```

### DateTime Validasyonu
```php
SecurityModule::validateDateTime($datetime, $format = 'Y-m-d')
```

## Tehdit Engelleme

### Otomatik Engellenen Pattern'ler
- `UNION SELECT`, `INSERT INTO`, `DELETE FROM`
- `<script>`, `javascript:`, `on[event]=`
- `&&`, `||`, `;cat`, `|whoami`
- `../`, `..\\`, null bytes

### Blocking Mekanizması
```php
SecurityModule::blockRequest($threat, $original_input)
```
- 403 Forbidden response
- Otomatik log kaydı
- Professional error page
- Incident ID oluşturma

## Log Sistemi

### Threat Logging
- **Lokasyon:** `/logs/waf_threats.log`
- **Format:** JSON + metadata
- **İçerik:** IP, User-Agent, URI, threat türü, input sample

### Log Verisi
```json
{
    "timestamp": "ISO-8601",
    "ip": "client_ip", 
    "user_agent": "browser_info",
    "uri": "request_path",
    "method": "HTTP_method",
    "threat": "threat_description",
    "input_sample": "first_500_chars",
    "input_length": "total_length"
}
```

## Helper Fonksiyonlar

### Global Wrapper'lar
```php
secure($input, $mode)     // SecurityModule::sanitize() alias
waf($input, $mode)        // SecurityModule::sanitize() alias  
wafPass($input)           // Pass-through mode
wafReflect($input)        // Reflect mode
```

### Validation Helper'lar
```php
isValidName($name)
isValidEmail($email)
isValidInteger($value, $min, $max)
isValidFloat($value, $min, $max)
isValidUUID($uuid)
isValidDateTime($datetime, $format)
```

## Güvenlik Seviyeleri

### Katman 1: Pattern Detection
- SQL, XSS, Command Injection
- Path Traversal, Null Bytes
- Çoklu encoding bypass

### Katman 2: Multi-layer Decoding  
- URL decode (5 seviye)
- HTML entity decode
- Iterative decode detection

### Katman 3: Whitelist Validation
- Mode bazlı karakter filtreleme
- Regex pattern matching
- Türkçe karakter desteği

### Katman 4: Output Encoding
- HTMLspecialchars encoding
- ENT_QUOTES | ENT_HTML5
- UTF-8 charset zorlaması

## Performans Optimizasyonları
- Null/empty input bypass
- Array recursive processing
- Object toString handling
- Single-pass regex validation

## Eksik Güvenlik Önlemleri
- **CSRF koruması eklenmemiştir** - POST formları için token gerekli