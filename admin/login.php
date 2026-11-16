<?php
/**
 * Admin login — bcrypt password verification, rate limiting (5 tries / 15 min),
 * session regeneration on success (stops session fixation).
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

secure_session_start();

$error = '';
$locked = false;

if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Security token mismatch. Please try again.';
    } else {
        $username = clean((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (rate_limited($username)) {
            $locked = true;
            $error = 'Too many failed attempts. Try again in about 15 minutes.';
        } else {
            try {
                $stmt = db()->prepare('SELECT * FROM admin_users WHERE username = :u LIMIT 1');
                $stmt->execute([':u' => $username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    record_attempt($username, true);
                    // Prevent session fixation
                    session_regenerate_id(true);
                    $_SESSION['admin_id']   = (int) $user['id'];
                    $_SESSION['admin_name'] = $user['full_name'];

                    db()->prepare('UPDATE admin_users SET last_login = NOW() WHERE id = :id')
                        ->execute([':id' => $user['id']]);

                    header('Location: index.php');
                    exit;
                }
                record_attempt($username, false);
                $error = 'Invalid username or password.';
            } catch (PDOException $ex) {
                error_log('Login error: ' . $ex->getMessage());
                $error = 'Login is temporarily unavailable.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <title>Admin Login — Estavia</title>
  <link rel="icon" type="image/png" href="../assets/logo-transparent.png" />
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Segoe UI',Arial,sans-serif;background:#0a1628;color:#e8e6df;
         display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
    .card{background:#12233d;border:1px solid #1f3a5f;border-radius:12px;padding:36px;width:100%;max-width:380px;
          box-shadow:0 20px 60px rgba(0,0,0,.5)}
    img{width:64px;display:block;margin:0 auto 14px}
    h1{font-size:20px;text-align:center;letter-spacing:2px;color:#c9a227;margin-bottom:6px}
    p.sub{text-align:center;color:#8fa3bd;font-size:13px;margin-bottom:24px}
    label{display:block;font-size:12px;letter-spacing:1px;text-transform:uppercase;color:#8fa3bd;margin:14px 0 6px}
    input{width:100%;padding:11px 12px;border-radius:8px;border:1px solid #2a4a75;background:#0d1c33;color:#fff;font-size:15px}
    input:focus{outline:none;border-color:#c9a227}
    button{width:100%;margin-top:22px;padding:12px;border:0;border-radius:8px;background:#c9a227;color:#0a1628;
           font-weight:700;font-size:15px;letter-spacing:1px;cursor:pointer}
    button:hover{background:#dbb635}
    .err{background:#4a1d1d;border:1px solid #7a2e2e;color:#f0b5b5;padding:10px 12px;border-radius:8px;
          font-size:13px;margin-top:16px;text-align:center}
    a.back{display:block;text-align:center;margin-top:18px;color:#8fa3bd;font-size:12px;text-decoration:none}
  </style>
</head>
<body>
  <form class="card" method="post" autocomplete="off">
    <img src="../assets/logo-transparent.png" alt="Estavia logo" />
    <h1>ESTAVIA ADMIN</h1>
    <p class="sub">Risk &amp; Security Solutions Ltd</p>
    <?= csrf_field() ?>
    <label for="username">Username</label>
    <input id="username" name="username" required maxlength="50" autofocus />
    <label for="password">Password</label>
    <input id="password" type="password" name="password" required maxlength="100" />
    <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
    <button type="submit" <?= $locked ? 'disabled' : '' ?>>Sign In</button>
    <a class="back" href="../index.php">&larr; Back to website</a>
  </form>
</body>
</html>
