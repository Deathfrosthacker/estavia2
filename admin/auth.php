<?php
/** Shared guard: every admin page starts with this. */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

secure_session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
