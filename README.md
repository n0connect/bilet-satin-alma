# 🎫 NoTicket - Otobüs Bilet Rezervasyon Sistemi

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Security](https://img.shields.io/badge/security-WAF%20Protected-brightgreen.svg)](src/889b1769-5f97-4b94-ac58-69877e948de7/SecurityModule.php)

## 📋 Proje Genel Bakış
**NoTicket**, modern güvenlik standartlarına sahip, PHP tabanlı profesyonel otobüs bilet rezervasyon sistemidir. Docker konteyner desteği ile kolay kurulum ve dağıtım sağlar.

**Geliştirici:** @n0connect
**Versiyon:** 1.0.0
**Durum:** Production Ready ✅

## 🛠️ Teknoloji Yığını
- **Backend:** PHP 8.0+ / PDO
- **Veritabanı:** SQLite 3
- **Web Server:** Nginx + PHP-FPM
- **Konteynerizasyon:** Docker Compose
- **Security:** Custom WAF Module (SecurityModule.php)

## 🔒 Güvenlik Özellikleri

### WAF (Web Application Firewall)
- ✅ **Custom SecurityModule.php** - Özel geliştirilen WAF modülü
- ✅ **Whitelist tabanlı validasyon** - Sadece güvenli karakterlere izin
- ✅ **Multi-layer decoding** - Encoding bypass saldırılarını tespit eder
- ✅ **Always-blocked patterns** - SQL Injection, XSS, Command Injection koruması
- ✅ **Selective mode system** - Email, password, text için özel modlar

### OWASP Top 10 Koruması
| Açık | Durum | Yöntem |
|------|-------|--------|
| SQL Injection | ✅ | Prepared Statements + Input Validation |
| XSS | ✅ | HTML Encoding + wafReflect() |
| CSRF | ⚠️ | SameSite Cookies (Token önerilir) |
| Broken Auth | ✅ | Timing attack prevention + bcrypt |
| Security Misconfiguration | ✅ | Secure session settings |
| Sensitive Data Exposure | ✅ | Password hashing + UUID |
| XML External Entities | N/A | XML kullanılmıyor |
| Broken Access Control | ✅ | Rol bazlı yetkilendirme |
| Insecure Deserialization | N/A | Serialization kullanılmıyor |
| Using Components with Known Vulnerabilities | ✅ | Güncel PHP 8.0+ |

### Ek Güvenlik
- ✅ **Session güvenliği:** UUID tabanlı + timeout + regeneration
- ✅ **UUID validation:** Tüm ID parametreleri UUID formatında
- ✅ **Timing attack prevention:** Login endpoint'inde usleep ile koruma
- ✅ **Rate limiting ready:** Altyapı hazır (implementasyon önerilir)
- ✅ **Threat logging:** Otomatik `/logs/waf_threats.log` kaydı

## Sistem Mimarisi

### Ana Modüller
- **Auth System:** Kullanıcı kimlik doğrulama (`auth.php`, `session_helper.php`)
- **Booking Engine:** Bilet rezervasyon sistemi (`booking.php`)
- **Payment System:** Kupon doğrulama ve ödeme işlemleri
- **Admin Panel:** Sistem yönetimi (`adminPanel/`)
- **Company Panel:** Şirket yönetimi (`companyPanel/`)

### Güvenlik Katmanı
- **SecurityModule.php:** Merkezi güvenlik kontrolü
- **Otomatik threat logging:** `/logs/waf_threats.log`
- **Real-time blocking:** 403 error sayfası ile anında engelleme

## ✨ Ana Özellikler

### Kullanıcı Özellikleri
- 🔍 **Gelişmiş Sefer Arama** - Şehir ve tarih bazlı filtreleme
- 💺 **Interaktif Koltuk Seçimi** - Görsel otobüs haritası
- 🎟️ **Kupon Sistemi** - JSON API ile gerçek zamanlı doğrulama
- 💰 **Bakiye Yönetimi** - Kullanıcı cüzdanı sistemi
- 🚫 **Bilet İptali** - Kalkışa 1 saat kalana kadar iptal
- 📄 **PDF Bilet İndirme** - Dijital bilet görüntüleme

### Admin Paneli
- 👥 **Kullanıcı Yönetimi** - Tam CRUD operasyonları
- 🏢 **Şirket Yönetimi** - Otobüs firmaları kontrolü
- 🎫 **Kupon Yönetimi** - Global kuponlar oluşturma
- 📊 **Dashboard** - Sistem istatistikleri

### Company Paneli
- 🚌 **Sefer Yönetimi** - CRUD operasyonları
- 🎟️ **Bilet Takibi** - Satılan biletler görüntüleme
- 👨‍👩‍👧‍👦 **Yolcu Listesi** - Sefere göre yolcu bilgileri
- 🎫 **Firma Kuponları** - Firmaya özel kuponlar
- 📈 **İstatistikler** - Satış ve doluluk oranları

### Teknik Özellikler
- ⚡ **Multi-tenant Mimari** - Firma bazlı ayrım
- 🔐 **Rol Bazlı Erişim** - user, company, admin rolleri
- 🎨 **Responsive Design** - Mobil uyumlu Glassmorphism UI
- 🔄 **Transaction Support** - Atomik veritabanı işlemleri
- 📱 **AJAX Support** - Kupon doğrulama API'si

## 🚀 Kurulum

### Gereksinimler
- Docker & Docker Compose
- veya
- PHP 8.0+
- SQLite3 extension
- PDO extension

### Docker ile Kurulum (Önerilen)
```bash
# Repository'yi klonla
git clone https://github.com/n0connect/bilet-satin-alma.git
cd NoTicket-PHP

# Docker build
docker-compose build

# Docker konteynerlerini başlat
docker-compose up -d

# Uygulamaya eriş
http://localhost:8080
```

### Manuel Kurulum
```bash
# Repository'yi klonla
git clone https://github.com/n0connect/NoTicket-PHP.git
cd NoTicket-PHP/src

# PHP built-in server ile çalıştır
php -S localhost:8080

# Veya Nginx/Apache ile kurulum yap
```

## 👤 Test Hesapları

### Normal Kullanıcı
- **Email:** tilki@test.com
- **Şifre:** user-123

### Normal Kullanıcı #2
- **Email:** dogubey@test.com
- **Şifre:** user-123

### Company Admin
- **Panel:** `/companyPanel/login.php`
- **Email:** [Veritabanından kontrol edin]

### System Admin
- **Panel:** `/adminPanel/login.php`
- **Email:** [Veritabanından kontrol edin]

## Dizin Yapısı
```
src/
├── adminPanel/          # Admin yönetim paneli
├── companyPanel/        # Şirket yönetim paneli  
├── 889b1769-*/         # SecurityModule ve güvenlik dosyaları
├── database/           # Veritabanı dosyaları
├── css/js/static/      # Frontend assets
└── *.php              # Ana uygulama dosyaları
```

## ⚠️ Güvenlik Notları

### Production Deployment Checklist
- [ ] **HTTPS Aktif Et** - `session_helper.php` içinde `'secure' => true`
- [ ] **CSRF Token Ekle** - POST formlarına token sistemi önerilir
- [ ] **Rate Limiting** - Login ve register endpoint'lerine ekle
- [ ] **Veritabanı Backup** - Otomatik yedekleme sistemi kur
- [ ] **Log Monitoring** - `logs/waf_threats.log` dosyasını izle
- [ ] **Error Reporting Kapat** - `php.ini` içinde `display_errors = Off`
- [ ] **File Permissions** - Dosya izinlerini 644, klasörleri 755 yap
- [ ] **Remove Test Accounts** - Test kullanıcılarını sil

### Mevcut Güvenlik
- ✅ Tüm user input SecurityModule ile validate edilir
- ✅ SQL injection koruması aktif (Prepared statements)
- ✅ XSS koruması aktif (HTML encoding)
- ✅ Session hijacking koruması mevcut
- ✅ Timing attack koruması (login endpoint)
- ✅ Password hashing (bcrypt)
- ✅ UUID kullanımı (predictable ID yok)

## Veritabanı Şeması
- **User:** Kullanıcı bilgileri ve bakiye
- **Trips:** Sefer bilgileri ve kapasite
- **Tickets:** Bilet rezervasyonları
- **Bus_Company:** Otobüs şirket bilgileri
- **Coupons:** İndirim kuponları

## 🌐 API Endpoint'leri

### Public Endpoints
| Endpoint | Method | Açıklama |
|----------|--------|----------|
| `/index.php` | GET | Ana sayfa - Sefer listesi |
| `/search.php` | GET | Sefer arama (from, to, date) |
| `/trip_detail.php?id={uuid}` | GET | Sefer detayı |
| `/login.php` | GET/POST | Kullanıcı girişi |
| `/register.php` | GET | Kayıt formu |
| `/addUser.php` | POST | Yeni kullanıcı kaydı |

### Authenticated Endpoints
| Endpoint | Method | Açıklama | Rol |
|----------|--------|----------|-----|
| `/dashboard.php` | GET | Kullanıcı paneli | user |
| `/booking.php?trip_id={uuid}` | GET/POST | Bilet satın alma | user |
| `/ticket_view.php?id={uuid}` | GET | Bilet PDF görüntüleme | user |
| `/check_coupon.php` | POST (JSON) | Kupon doğrulama API | user |

### Admin Panel
| Endpoint | Method | Açıklama |
|----------|--------|----------|
| `/adminPanel/login.php` | GET/POST | Admin girişi |
| `/adminPanel/dashboard.php` | GET | Admin dashboard |
| `/adminPanel/companies.php` | GET/POST | Firma yönetimi |
| `/adminPanel/company_admins.php` | GET/POST | Firma admin yönetimi |
| `/adminPanel/coupons.php` | GET/POST | Global kupon yönetimi |

### Company Panel
| Endpoint | Method | Açıklama |
|----------|--------|----------|
| `/companyPanel/login.php` | GET/POST | Firma girişi |
| `/companyPanel/dashboard.php` | GET | Firma dashboard |
| `/companyPanel/trips.php` | GET/POST | Sefer CRUD |
| `/companyPanel/tickets.php` | GET | Satılan biletler |
| `/companyPanel/passengers.php` | GET | Yolcu listesi |
| `/companyPanel/coupons.php` | GET/POST | Firma kuponları |

## 📁 Proje Yapısı

```
NoTicket-PHP/
├── src/
│   ├── 889b1769-5f97-4b94-ac58-69877e948de7/    # Security Layer
│   │   ├── SecurityModule.php                    # WAF + Validation
│   │   ├── 239fcbd0-c512-4694-aa09-36d87260396c.php  # 403 Page
│   │   └── logs/                                 # Threat logs
│   │
│   ├── adminPanel/                               # Admin Interface (8 files)
│   │   ├── auth.php                              # Admin authentication
│   │   ├── dashboard.php                         # Admin dashboard
│   │   ├── companies.php                         # Company management
│   │   ├── company_admins.php                    # Company admin management
│   │   ├── coupons.php                           # Global coupon management
│   │   └── ...
│   │
│   ├── companyPanel/                             # Company Interface (10 files)
│   │   ├── auth.php                              # Company authentication
│   │   ├── dashboard.php                         # Company dashboard
│   │   ├── trips.php                             # Trip CRUD
│   │   ├── tickets.php                           # Ticket management
│   │   ├── passengers.php                        # Passenger list
│   │   ├── coupons.php                           # Company coupons
│   │   └── ...
│   │
│   ├── database/                                 # Database
│   │   └── noticket.db                           # SQLite database
│   │
│   ├── css/, js/, static/                        # Frontend Assets
│   │
│   └── *.php                                     # Core Application Files (14 files)
│       ├── index.php                             # Homepage
│       ├── login.php / register.php              # Authentication
│       ├── dashboard.php                         # User dashboard
│       ├── search.php                            # Trip search
│       ├── booking.php                           # Ticket booking
│       ├── check_coupon.php                      # JSON API
│       ├── trip_detail.php                       # Trip details
│       ├── ticket_view.php                       # PDF ticket view
│       ├── auth.php / session_helper.php         # Auth helpers
│       ├── dbconnect.php                         # Database connection
│       └── coupon_validator.php                  # Coupon validation logic
│
├── docker-compose.yml                            # Docker configuration
├── Dockerfile                                    # Docker image
└── README.md                                     # This file
```

## 🗃️ Veritabanı Şeması

### User
| Alan | Tip | Açıklama |
|------|-----|----------|
| id | TEXT (UUID) | Primary Key |
| full_name | TEXT | Ad Soyad |
| email | TEXT (UNIQUE) | Email adresi |
| password | TEXT | Bcrypt hash |
| role | TEXT | 'user', 'company', 'admin' |
| balance | REAL | Kullanıcı bakiyesi (₺) |
| created_at | DATETIME | Kayıt tarihi |

### Bus_Company
| Alan | Tip | Açıklama |
|------|-----|----------|
| id | TEXT (UUID) | Primary Key |
| name | TEXT | Şirket adı |
| logo_path | TEXT | Logo URL |
| created_at | DATETIME | Oluşturulma tarihi |

### Trips
| Alan | Tip | Açıklama |
|------|-----|----------|
| id | TEXT (UUID) | Primary Key |
| company_id | TEXT (FK) | Şirket ID |
| departure_city | TEXT | Kalkış şehri |
| destination_city | TEXT | Varış şehri |
| departure_time | DATETIME | Kalkış zamanı |
| actual_time | DATETIME | Gerçek biniş zamanı |
| price | REAL | Bilet fiyatı (₺) |
| capacity | INTEGER | Toplam koltuk |
| created_at | DATETIME | Oluşturulma tarihi |

### Tickets
| Alan | Tip | Açıklama |
|------|-----|----------|
| id | TEXT (UUID) | Primary Key |
| trip_id | TEXT (FK) | Sefer ID |
| user_id | TEXT (FK) | Kullanıcı ID |
| seat_number | INTEGER | Koltuk numarası |
| status | TEXT | 'paid', 'cancelled', 'reserved' |
| created_at | DATETIME | Satın alma tarihi |

### Coupons
| Alan | Tip | Açıklama |
|------|-----|----------|
| id | TEXT (UUID) | Primary Key |
| code | TEXT (UNIQUE) | Kupon kodu |
| discount | REAL | İndirim miktarı (₺) |
| usage_time | INTEGER | Max kullanım sayısı |
| expire_date | DATE | Son kullanma tarihi |
| company_id | TEXT (FK) NULL | Firma ID (NULL = global) |
| created_at | DATETIME | Oluşturulma tarihi |

### User_Coupons
| Alan | Tip | Açıklama |
|------|-----|----------|
| user_id | TEXT (FK) | Kullanıcı ID |
| coupon_id | TEXT (FK) | Kupon ID |
| used_at | DATETIME | Kullanım tarihi |

## 🤝 Katkıda Bulunma
Pull request'ler memnuniyetle karşılanır. Büyük değişiklikler için lütfen önce bir issue açın.

## 📝 Lisans
Bu proje MIT lisansı altında lisanslanmıştır.

## 👨‍💻 Geliştirici
**@n0connect**

## 🔗 Bağlantılar
- GitHub: [https://github.com/n0connect](https://github.com/n0connect)
- Demo: [Yakında]
- Dokümantasyon: [Bu README]

---

**⚡ Son Güncelleme:** 2025-10-23
**📦 Versiyon:** 1.0.0
**✅ Durum:** Production Ready