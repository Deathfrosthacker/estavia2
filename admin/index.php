<?php
/** Admin dashboard: view and manage quote requests. */
require_once __DIR__ . '/auth.php';

$action = $_POST['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== '') {
    if (!csrf_verify()) {
        die('Security token mismatch.');
    }
    $id = (int) ($_POST['id'] ?? 0);
    $pdo = db();
    if ($action === 'set_status') {
        $allowed = ['new', 'contacted', 'quoted', 'closed'];
        $status = $_POST['status'] ?? '';
        if ($id > 0 && in_array($status, $allowed, true)) {
            $stmt = $pdo->prepare('UPDATE quote_requests SET status = :s WHERE id = :id');
            $stmt->execute([':s' => $status, ':id' => $id]);
        }
    } elseif ($action === 'delete' && $id > 0) {
        $stmt = $pdo->prepare('DELETE FROM quote_requests WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
    header('Location: index.php' . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
    exit;
}

// Stats
$pdo = db();
$stats = [
    'total'    => (int) $pdo->query('SELECT COUNT(*) FROM quote_requests')->fetchColumn(),
    'new'      => (int) $pdo->query("SELECT COUNT(*) FROM quote_requests WHERE status = 'new'")->fetchColumn(),
    'this_week'=> (int) $pdo->query('SELECT COUNT(*) FROM quote_requests WHERE created_at > (NOW() - INTERVAL 7 DAY)')->fetchColumn(),
];

// Filter
$filter = $_GET['status'] ?? '';
$allowedFilters = ['new', 'contacted', 'quoted', 'closed'];
if (in_array($filter, $allowedFilters, true)) {
    $stmt = $pdo->prepare('SELECT * FROM quote_requests WHERE status = :s ORDER BY created_at DESC');
    $stmt->execute([':s' => $filter]);
    $rows = $stmt->fetchAll();
} else {
    $rows = $pdo->query('SELECT * FROM quote_requests ORDER BY created_at DESC')->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <title>Dashboard — Estavia Admin</title>
  <link rel="icon" type="image/png" href="../assets/logo-transparent.png" />
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Segoe UI',Arial,sans-serif;background:#0a1628;color:#e8e6df;padding:24px}
    header{display:flex;flex-wrap:wrap;gap:16px;align-items:center;justify-content:space-between;margin-bottom:24px}
    h1{font-size:22px;letter-spacing:1px}
    h1 span{color:#c9a227}
    .btn{display:inline-block;padding:9px 16px;border-radius:8px;background:#1f3a5f;color:#e8e6df;
         text-decoration:none;font-size:14px;border:0;cursor:pointer}
    .btn:hover{background:#2a4a75}
    .btn-danger{background:#4a1d1d}
    .btn-danger:hover{background:#7a2e2e}
    .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:24px}
    .stat{background:#12233d;border:1px solid #1f3a5f;border-radius:10px;padding:18px}
    .stat b{display:block;font-size:30px;color:#c9a227}
    .stat span{font-size:12px;letter-spacing:1px;text-transform:uppercase;color:#8fa3bd}
    .filters{margin-bottom:16px;display:flex;gap:8px;flex-wrap:wrap}
    .filters a{padding:7px 14px;border-radius:20px;background:#12233d;color:#8fa3bd;text-decoration:none;font-size:13px}
    .filters a.active{background:#c9a227;color:#0a1628;font-weight:700}
    table{width:100%;border-collapse:collapse;background:#12233d;border-radius:10px;overflow:hidden}
    th,td{padding:12px 14px;text-align:left;border-bottom:1px solid #1f3a5f;font-size:14px;vertical-align:top}
    th{background:#0d1c33;font-size:12px;letter-spacing:1px;text-transform:uppercase;color:#8fa3bd}
    .pill{display:inline-block;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
    .new{background:#1d3a4a;color:#7fd4f0}
    .contacted{background:#4a3a1d;color:#f0cf7f}
    .quoted{background:#1d4a2e;color:#8fe0a8}
    .closed{background:#3a3a3a;color:#aaa}
    select,input[type=hidden]{font-size:13px}
    select{padding:6px 8px;border-radius:6px;border:1px solid #2a4a75;background:#0d1c33;color:#e8e6df}
    .muted{color:#8fa3bd;font-size:12px}
    .msg{max-width:320px;white-space:pre-wrap}
    .wrap{overflow-x:auto}
  </style>
</head>
<body>
<header>
  <h1>ESTAVIA <span>ADMIN</span> — Quote Requests</h1>
  <div>
    <span class="muted">Signed in as <?= e($_SESSION['admin_name'] ?? 'Admin') ?> &nbsp;·&nbsp;</span>
    <a class="btn" href="../index.php">View Site</a>
    <a class="btn btn-danger" href="logout.php">Log Out</a>
  </div>
</header>

<div class="stats">
  <div class="stat"><b><?= $stats['total'] ?></b><span>Total Requests</span></div>
  <div class="stat"><b><?= $stats['new'] ?></b><span>New / Unread</span></div>
  <div class="stat"><b><?= $stats['this_week'] ?></b><span>This Week</span></div>
</div>

<div class="filters">
  <a href="index.php" class="<?= $filter === '' ? 'active' : '' ?>">All</a>
  <?php foreach ($allowedFilters as $f): ?>
    <a href="?status=<?= $f ?>" class="<?= $filter === $f ? 'active' : '' ?>"><?= ucfirst($f) ?></a>
  <?php endforeach; ?>
</div>

<div class="wrap">
<table>
  <thead>
    <tr><th>#</th><th>Client</th><th>Contact</th><th>Service</th><th>Message</th><th>Received</th><th>Status</th><th></th></tr>
  </thead>
  <tbody>
  <?php if (!$rows): ?>
    <tr><td colspan="8" class="muted" style="text-align:center;padding:30px">No quote requests yet.</td></tr>
  <?php endif; ?>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= (int) $r['id'] ?></td>
      <td><strong><?= e($r['name']) ?></strong><br><span class="muted"><?= e($r['ip_address'] ?? '') ?></span></td>
      <td><?= e($r['phone']) ?><?php if ($r['email']): ?><br><span class="muted"><?= e($r['email']) ?></span><?php endif; ?></td>
      <td><?= e($r['service']) ?></td>
      <td class="msg"><?= e($r['message'] ?? '—') ?></td>
      <td class="muted"><?= e($r['created_at']) ?></td>
      <td>
        <span class="pill <?= e($r['status']) ?>"><?= e($r['status']) ?></span>
        <form method="post" style="margin-top:6px">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="set_status" />
          <input type="hidden" name="id" value="<?= (int) $r['id'] ?>" />
          <select name="status" onchange="this.form.submit()">
            <?php foreach (['new','contacted','quoted','closed'] as $s): ?>
              <option value="<?= $s ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </td>
      <td>
        <form method="post" onsubmit="return confirm('Delete this request permanently?')">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete" />
          <input type="hidden" name="id" value="<?= (int) $r['id'] ?>" />
          <button class="btn btn-danger" type="submit">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
</body>
</html>
