# 🎫 NoTicket - Bus Ticket Reservation System

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Security](https://img.shields.io/badge/security-WAF%20Protected-brightgreen.svg)](src/889b1769-5f97-4b94-ac58-69877e948de7/SecurityModule.php)

## 📋 Project Overview
**NoTicket** is a professional PHP-based bus ticket reservation system with modern security standards. It provides easy installation and deployment with Docker container support.

**Developer:** @n0connect
**Version:** 1.0.0
**Status:** Experimental Only 

## 🛠️ Technology Stack
- **Backend:** PHP 8.0+ / PDO
- **Database:** SQLite 3
- **Web Server:** Nginx + PHP-FPM
- **Containerization:** Docker Compose
- **Security:** Custom sWAF Module (SecurityModule.php) + Regex Control

## 🔒 Security Features

### sWAF (Secure Web Application Firewall)
- ✅ **Custom SecurityModule.php** - Custom developed sWAF module
- ✅ **Whitelist-based validation** - Only allows safe characters (Regex)
- ✅ **Multi-layer decoding** - Detects encoding bypass attacks
- ✅ **Always-blocked patterns** - SQL Injection, XSS, Command Injection protection (Blacklist + Whitelist)
- ✅ **Selective mode system** - Special modes for email, password, text

### OWASP Top 10 Protection
| Vulnerability | Status | Method |
|------|-------|--------|
| SQL Injection | ✅ | Prepared Statements + Input Validation |
| XSS | ✅ | HTML Encoding + wafReflect() |
| CSRF | N/A | SameSite Cookies not used |
| Broken Auth | ✅ | Timing attack prevention + bcrypt |
| Security Misconfiguration | ✅ | Secure session settings |
| Sensitive Data Exposure | ✅ | Password hashing + UUID |
| XML External Entities | N/A | XML not used |
| Broken Access Control | ✅ | Role-based authorization |
| Insecure Deserialization | N/A | Serialization not used |
| Using Components with Known Vulnerabilities | ✅ | Up-to-date PHP 8.0+ |

### Additional Security
- ✅ **Session security:** UUID-based + timeout + regeneration
- ✅ **UUID validation:** All ID parameters in UUID format
- ✅ **Timing attack prevention:** Protection with usleep on login endpoint
- ✅ **Threat logging:** Automatic `/logs/waf_threats.log` recording
- ❌ **Rate limiting ready:** Not added

## System Architecture

### Main Modules
- **Auth System:** User authentication (`auth.php`, `session_helper.php`)
- **Booking Engine:** Ticket reservation system (`booking.php`)
- **Payment System:** Coupon validation and payment processing
- **Admin Panel:** System management (`adminPanel/`)
- **Company Panel:** Company management (`companyPanel/`)

### Security Layer
- **SecurityModule.php:** Central security control
- **Automatic threat logging:** `/logs/waf_threats.log`
- **Real-time blocking:** Instant blocking with 403 error page

## ✨ Main Features

### User Features
- 🔍 **Advanced Trip Search** - City and date-based filtering
- 💺 **Interactive Seat Selection** - Visual bus map
- 🎟️ **Coupon System** - Real-time validation with JSON API
- 💰 **Balance Management** - User wallet system
- 🚫 **Ticket Cancellation** - Cancel up to 1 hour before departure
- 📄 **PDF Ticket Download** - Digital ticket viewing

### Admin Panel
- 👥 **User Management** - Full CRUD operations
- 🏢 **Company Management** - Bus company control
- 🎫 **Coupon Management** - Global coupon creation
- 📊 **Dashboard** - System statistics

### Company Panel
- 🚌 **Trip Management** - CRUD operations
- 🎟️ **Ticket Tracking** - View sold tickets
- 👨‍👩‍👧‍👦 **Passenger List** - Passenger information by trip
- 🎫 **Company Coupons** - Company-specific coupons
- 📈 **Statistics** - Sales and occupancy rates

### Technical Features
- ⚡ **Multi-tenant Architecture** - Company-based separation
- 🔐 **Role-Based Access** - user, company, admin roles
- 🎨 **Responsive Design** - Mobile-friendly Glassmorphism UI
- 🔄 **Transaction Support** - Atomic database operations
- 📱 **AJAX Support** - Coupon validation API

## 🚀 Installation

### Requirements
- Docker & Docker Compose
- or
- PHP 8.0+
- SQLite3 extension
- PDO extension

### Installation with Docker (Recommended)
```bash
# Clone repository
git clone https://github.com/n0connect/NoTicket.git
cd NoTicket-PHP

# Docker build
docker-compose build

# Start Docker containers
docker-compose up -d

# Access application
http://localhost:8080
```

## Directory Structure
```
src/
├── adminPanel/          # Admin management panel
├── companyPanel/        # Company management panel  
├── 889b1769-*/         # SecurityModule and security files
├── database/           # Database files
├── css/js/static/      # Frontend assets
└── *.php              # Main application files
```

## 🌐 API Endpoints

### Public Endpoints
| Endpoint | Method | Description |
|----------|--------|----------|
| `/index.php` | GET | Homepage - Trip list |
| `/search.php` | GET | Trip search (from, to, date) |
| `/trip_detail.php?id={uuid}` | GET | Trip details |
| `/login.php` | GET/POST | User login |
| `/register.php` | GET | Registration form |
| `/addUser.php` | POST | New user registration |

### Authenticated Endpoints
| Endpoint | Method | Description | Role |
|----------|--------|----------|-----|
| `/dashboard.php` | GET | User panel | user |
| `/booking.php?trip_id={uuid}` | GET/POST | Ticket purchase | user |
| `/ticket_view.php?id={uuid}` | GET | Ticket PDF view | user |
| `/check_coupon.php` | POST (JSON) | Coupon validation API | user |

### Admin Panel
| Endpoint | Method | Description |
|----------|--------|----------|
| `/adminPanel/login.php` | GET/POST | Admin login |
| `/adminPanel/dashboard.php` | GET | Admin dashboard |
| `/adminPanel/companies.php` | GET/POST | Company management |
| `/adminPanel/company_admins.php` | GET/POST | Company admin management |
| `/adminPanel/coupons.php` | GET/POST | Global coupon management |

### Company Panel
| Endpoint | Method | Description |
|----------|--------|----------|
| `/companyPanel/login.php` | GET/POST | Company login |
| `/companyPanel/dashboard.php` | GET | Company dashboard |
| `/companyPanel/trips.php` | GET/POST | Trip CRUD |
| `/companyPanel/tickets.php` | GET | Sold tickets |
| `/companyPanel/passengers.php` | GET | Passenger list |
| `/companyPanel/coupons.php` | GET/POST | Company coupons |

## 📁 Project Structure

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

## 🗃️ Database Schema

### User
| Field | Type | Description |
|------|-----|----------|
| id | TEXT (UUID) | Primary Key |
| full_name | TEXT | Full Name |
| email | TEXT (UNIQUE) | Email address |
| password | TEXT | Bcrypt hash |
| role | TEXT | 'user', 'company', 'admin' |
| balance | REAL | User balance (₺) |
| created_at | DATETIME | Registration date |

### Bus_Company
| Field | Type | Description |
|------|-----|----------|
| id | TEXT (UUID) | Primary Key |
| name | TEXT | Company name |
| logo_path | TEXT | Logo URL |
| created_at | DATETIME | Creation date |

### Trips
| Field | Type | Description |
|------|-----|----------|
| id | TEXT (UUID) | Primary Key |
| company_id | TEXT (FK) | Company ID |
| departure_city | TEXT | Departure city |
| destination_city | TEXT | Destination city |
| departure_time | DATETIME | Departure time |
| actual_time | DATETIME | Actual boarding time |
| price | REAL | Ticket price (₺) |
| capacity | INTEGER | Total seats |
| created_at | DATETIME | Creation date |

### Tickets
| Field | Type | Description |
|------|-----|----------|
| id | TEXT (UUID) | Primary Key |
| trip_id | TEXT (FK) | Trip ID |
| user_id | TEXT (FK) | User ID |
| seat_number | INTEGER | Seat number |
| status | TEXT | 'paid', 'cancelled', 'reserved' |
| created_at | DATETIME | Purchase date |

### Coupons
| Field | Type | Description |
|------|-----|----------|
| id | TEXT (UUID) | Primary Key |
| code | TEXT (UNIQUE) | Coupon code |
| discount | REAL | Discount amount (₺) |
| usage_time | INTEGER | Max usage count |
| expire_date | DATE | Expiration date |
| company_id | TEXT (FK) NULL | Company ID (NULL = global) |
| created_at | DATETIME | Creation date |

### User_Coupons
| Field | Type | Description |
|------|-----|----------|
| user_id | TEXT (FK) | User ID |
| coupon_id | TEXT (FK) | Coupon ID |
| used_at | DATETIME | Usage date |

## 👨‍💻 Developer
**@n0connect**

---

## 📝 License
This project is licensed under the MIT License.
