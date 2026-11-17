<?php
/**
 * Estavia Risk & Security Solutions Ltd â€” Database configuration
 *
 * !!! IMPORTANT (Truehost deployment) !!!
 * 1. In cPanel open "MySQL Database Wizard" and create a database and user.
 *    Use the full prefixed names assigned by your host.
 *      - Password : a STRONG password of your own
 * 2. Assign the user to the database with "ALL PRIVILEGES".
 * 3. Import database.sql through phpMyAdmin.
 * 4. Replace the values below with the credentials you created.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'estavia_db');
// These defaults work with a standard XAMPP installation. Replace them with
// your hosting database credentials before deploying to a live server.
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Apache and the PHP development server use the same credentials above.

/** Company contact details used across the site. */
define('SITE_PHONE_1', '+254750856829');
define('SITE_PHONE_2', '+254729394652');
define('SITE_EMAIL', 'info@estaviarisksecurity.co.ke');
define('SALES_EMAIL', 'sales@estaviarisksecurity.co.ke');
define('SITE_ADDRESS', 'Ganjoni, Off Moi Avenue, Mombasa, Kenya');
