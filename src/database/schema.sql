-- NoTicket Platform Database Schema
-- SQLite Database

PRAGMA foreign_keys = ON;

-- Drop tables if exists (development amaçlı)
DROP TABLE IF EXISTS Blocked_Seats;
DROP TABLE IF EXISTS Tickets;
DROP TABLE IF EXISTS Trips;
DROP TABLE IF EXISTS Coupons;
DROP TABLE IF EXISTS Bus_Company;
DROP TABLE IF EXISTS User_Coupons;
DROP TABLE IF EXISTS User;

-- User Tablosu oluşturuldu
CREATE TABLE User (
    id TEXT PRIMARY KEY,
    full_name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    role TEXT NOT NULL CHECK(role IN ('user', 'company', 'admin')),
    password TEXT NOT NULL, -- Argon2 Hash
    company_id TEXT DEFAULT NULL,
    balance INTEGER DEFAULT 800,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES Bus_Company(id) ON DELETE SET NULL
);

-- Bus_Company Tablosu oluşturuldu
CREATE TABLE Bus_Company (
    id TEXT PRIMARY KEY,
    name TEXT UNIQUE NOT NULL,
    logo_path TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Trips Tablosu oluşturuldu
CREATE TABLE Trips (
    id TEXT PRIMARY KEY,
    company_id TEXT NOT NULL,
    destination_city TEXT NOT NULL,
    actual_time DATETIME NOT NULL,
    departure_time DATETIME NOT NULL,
    departure_city TEXT NOT NULL,
    price REAL NOT NULL CHECK(price > 0),
    capacity INTEGER NOT NULL CHECK(capacity > 0),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES Bus_Company(id) ON DELETE CASCADE
);

-- Coupons Tablosu oluşturuldu
CREATE TABLE Coupons (
    id TEXT PRIMARY KEY,
    code TEXT UNIQUE NOT NULL,
    discount REAL NOT NULL CHECK(discount >= 0 AND discount <= 100),
    company_id TEXT DEFAULT NULL,
    usage_time INTEGER NOT NULL DEFAULT 0,
    expire_date DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES Bus_Company(id) ON DELETE CASCADE
);

-- User_Coupons Table (Kullanıcı kupon kullanımı ilişkisi)
CREATE TABLE User_Coupons (
    id TEXT PRIMARY KEY,
    coupon_id INTEGER NOT NULL,
    user_id TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES Coupons(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES User(id) ON DELETE CASCADE
);

-- Tickets Tablosu oluşturuldu
CREATE TABLE Tickets (
    id TEXT PRIMARY KEY,
    trip_id TEXT NOT NULL,
    user_id TEXT NOT NULL,
    seat_number INTEGER NOT NULL,
    status TEXT NOT NULL CHECK(status IN ('reserved', 'paid', 'cancelled')),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES Trips(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES User(id) ON DELETE CASCADE,
    UNIQUE(trip_id, seat_number)
);


-- Blocked_Seats Table (Geçici olarak bloke edilmiş koltuklar)
CREATE TABLE Blocked_Seats (
    id TEXT PRIMARY KEY,
    ticket_id TEXT NOT NULL,
    seat_number INTEGER UNIQUE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES Tickets(id) ON DELETE CASCADE
);

-- Indexes (Performance için)
CREATE INDEX idx_user_email ON User(email);
CREATE INDEX idx_user_role ON User(role);
CREATE INDEX idx_trips_company ON Trips(company_id);
CREATE INDEX idx_trips_departure_time ON Trips(departure_time);
CREATE INDEX idx_tickets_user ON Tickets(user_id);
CREATE INDEX idx_tickets_trip ON Tickets(trip_id);
CREATE INDEX idx_tickets_status ON Tickets(status);
CREATE INDEX idx_coupons_code ON Coupons(code);
CREATE INDEX idx_blocked_seats_trip ON Blocked_Seats(ticket_id);
