<?php
/**
 * Quote request endpoint.
 * POST only. Validates + sanitises input, verifies CSRF, stores with a
 * parameterised prepared statement (SQL-injection safe).
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// --- 1. Only POST is allowed (blocks GET/HEAD tampering) ---
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

// --- 2. CSRF check (blocks cross-site request forgery) ---
if (!csrf_verify()) {
    json_response(['ok' => false, 'error' => 'Security token mismatch. Refresh the page and try again.'], 403);
}

// --- 3. Honeypot: invisible "website" field bots fill in (spam filter) ---
if (!empty($_POST['website'])) {
    json_response(['ok' => true]); // pretend success, store nothing
}

// --- 4. Collect + sanitise fields ---
$name    = clean((string) ($_POST['name'] ?? ''));
$phone   = clean((string) ($_POST['phone'] ?? ''));
$email   = clean((string) ($_POST['email'] ?? ''));
$service = clean((string) ($_POST['service'] ?? ''));
$message = clean((string) ($_POST['message'] ?? ''));

// --- 5. Server-side validation (never trust the browser) ---
$errors = [];
if ($name === '' || str_len($name) < 2 || str_len($name) > 100) {
    $errors[] = 'Please enter your name (2–100 characters).';
}
if ($phone === '' || !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
    $errors[] = 'Please enter a valid phone number.';
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}
if ($service === '' || str_len($service) > 120) {
    $errors[] = 'Please select a service.';
}
if (str_len($message) > 2000) {
    $errors[] = 'Message is too long (max 2000 characters).';
}
if ($errors) {
    json_response(['ok' => false, 'error' => implode(' ', $errors)], 422);
}

// --- 6. Insert with a prepared statement (SQL-injection safe) ---
try {
    $stmt = db()->prepare(
        'INSERT INTO quote_requests (name, phone, email, service, message, ip_address)
         VALUES (:name, :phone, :email, :service, :message, :ip)'
    );
    $stmt->execute([
        ':name'    => $name,
        ':phone'   => $phone,
        ':email'   => $email !== '' ? $email : null,
        ':service' => $service,
        ':message' => $message !== '' ? $message : null,
        ':ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
} catch (PDOException $ex) {
    error_log('Quote insert failed: ' . $ex->getMessage());
    json_response(['ok' => false, 'error' => 'Could not save your request. Please call us instead.'], 500);
}

// --- 7. Optional: email notification to the company ---
// Uncomment on Truehost after configuring mail:
// @mail(SITE_EMAIL, 'New quote request — ' . $name, "Name: $name\nPhone: $phone\n...");

json_response(['ok' => true, 'message' => 'Thank you! Your request has been received. Our team will contact you shortly.']);
