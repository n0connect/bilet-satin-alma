<?php
/**
 * @file dbconnect.php
 * @brief Veritabanı Bağlantı Modülü
 *
 * SQLite veritabanı bağlantısını kurar ve PDO nesnesini oluşturur.
 * Foreign key desteğini aktif eder.
 *
 * @author n0connect
 * @version 1.0.0
 * @date 2025-10-23
 * @copyright MIT License
 *
 * @global PDO $db Veritabanı bağlantı nesnesi
 *
 * @config
 *   - Database: SQLite (database/noticket.db)
 *   - Error Mode: ERRMODE_EXCEPTION
 *   - Foreign Keys: ON
 */


try {
    $db = new PDO('sqlite:' . __DIR__ . '/database/noticket.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA foreign_keys = ON');
} catch(PDOException $e) {
    die("Database bağlantı hatası");
}
?>