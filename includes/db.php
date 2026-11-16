<?php
/**
 * PDO database connection — shared by all pages.
 * Throws PDOException on failure; pages should catch and degrade gracefully.
 */
require_once __DIR__ . '/../config/config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // fail loudly in dev
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,                    // REAL prepared statements
    ];
    // Local dev override (see config.php). Production always uses DB_PASS.
    $pass = defined('DB_PASS_LOCAL') ? DB_PASS_LOCAL : DB_PASS;
    $pdo = new PDO($dsn, DB_USER, $pass, $options);
    return $pdo;
}
