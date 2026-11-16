<?php
/**
 * Estavia Risk & Security Solutions Ltd — Database configuration
 *
 * !!! IMPORTANT (Truehost deployment) !!!
 * 1. In cPanel open "MySQL Database Wizard" and create:
 *      - Database : estavia_db
 *      - User     : estavia_user
 *      - Password : a STRONG password of your own
 * 2. Assign the user to the database with "ALL PRIVILEGES".
 * 3. Import database.sql through phpMyAdmin.
 * 4. Replace the values below with the credentials you created.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'estavia_db');
define('DB_USER', 'estavia_user');
define('DB_PASS', 'CHANGE_ME_strong_password');   // <-- REPLACE on Truehost (see README)
define('DB_CHARSET', 'utf8mb4');

// Local development override (safe: file is blocked by .htaccess in production).
// On Truehost just delete this block or leave it — it only triggers on port 8000.
if (php_sapi_name() === 'cli-server' || ($_SERVER['SERVER_PORT'] ?? null) === '8000') {
    define('DB_PASS_LOCAL', 'LocalTest#2026');
}

/** Company contact details used across the site. */
define('SITE_PHONE_1', '+254750856829');
define('SITE_PHONE_2', '+254729394652');
define('SITE_EMAIL', 'estaviarisksecurity@gmail.com');
define('SITE_ADDRESS', 'Ganjoni, Off Moi Avenue, Mombasa, Kenya');
