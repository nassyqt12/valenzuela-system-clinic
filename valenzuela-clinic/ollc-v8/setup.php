<?php
// ============================================================
//  setup.php — One-Click Installer / Password Upgrader
//  Valenzuela Clinic · Appointment System
//
//  PURPOSE:
//  The SQL file uses plain-text seed passwords so it can be
//  imported without running PHP. This script upgrades all
//  plain-text passwords to real bcrypt hashes in one click.
//
//  ⚠️  DELETE THIS FILE after running setup for security!
// ============================================================

// ── DB Config (edit if different from defaults) ───────────────
$host = 'localhost';
$name = 'valenzuela_clinic';
$user = 'root';
$pass = '';
$port = 3306;

$done    = [];
$errors  = [];
$success = false;

// ── Process form submission ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['host'] ?? 'localhost');
    $name = trim($_POST['name'] ?? 'valenzuela_clinic');
    $user = trim($_POST['user'] ?? 'root');
    $pass = $_POST['pass'] ?? '';
    $port = (int)($_POST['port'] ?? 3306);

    if (!extension_loaded('mysqli')) {
        $errors[] = '❌ mysqli extension is not enabled. Open php.ini, uncomment "extension=mysqli", and restart Apache.';
    } else {
        $conn = @new mysqli($host, $user, $pass, '', $port);

        if ($conn->connect_error) {
            $ce = $conn->connect_error;
            if (str_contains($ce, 'Connection refused') || str_contains($ce, "Can't connect")) {
                $errors[] = '❌ Cannot connect to MySQL. Is MySQL running in XAMPP/WAMP?';
            } elseif (str_contains($ce, 'Access denied')) {
                $errors[] = '❌ Wrong MySQL username or password.';
            } else {
                $errors[] = '❌ MySQL error: ' . $ce;
            }
        } else {
            $conn->set_charset('utf8mb4');

            // Create database if missing
            if (!$conn->query("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
                $errors[] = '❌ Could not create database: ' . $conn->error;
            } else {
                $conn->query("USE `$name`");
                $done[] = '✅ Database "' . $name . '" ready.';

                // Import SQL file
                $sqlFile = __DIR__ . '/valenzuela_clinic.sql';
                if (file_exists($sqlFile)) {
                    $sql = file_get_contents($sqlFile);
                    // Strip CREATE DATABASE / USE lines since we already handle that
                    $sql = preg_replace('/^\s*(CREATE DATABASE|USE)\b[^;]+;/mi', '', $sql);
                    // Execute statement by statement
                    $conn->multi_query($sql);
                    do { $conn->use_result(); } while ($conn->more_results() && $conn->next_result());
                    $done[] = '✅ SQL imported successfully.';
                } else {
                    $errors[] = '⚠️  valenzuela_clinic.sql not found — tables may already exist.';
                }

                // Upgrade plain-text passwords to bcrypt
                $upgraded = 0;
                $result   = $conn->query("SELECT id, password FROM users");
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        // Skip if already bcrypt (starts with $2y$)
                        if (str_starts_with($row['password'], '$2')) continue;

                        $hash = password_hash($row['password'], PASSWORD_BCRYPT, ['cost' => 12]);
                        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmt->bind_param('si', $hash, $row['id']);
                        $stmt->execute();
                        $upgraded++;
                    }
                }
                $done[] = '✅ Upgraded ' . $upgraded . ' password(s) to bcrypt.';

                // Update config.php with entered credentials
                $cfgPath = __DIR__ . '/includes/config.php';
                if (file_exists($cfgPath)) {
                    $cfg = file_get_contents($cfgPath);
                    $cfg = preg_replace("/define\('DB_HOST',\s*'[^']*'\)/", "define('DB_HOST', '$host')", $cfg);
                    $cfg = preg_replace("/define\('DB_NAME',\s*'[^']*'\)/", "define('DB_NAME', '$name')", $cfg);
                    $cfg = preg_replace("/define\('DB_USER',\s*'[^']*'\)/", "define('DB_USER', '$user')", $cfg);
                    $cfg = preg_replace("/define\('DB_PASS',\s*'[^']*'\)/", "define('DB_PASS', '$pass')", $cfg);
                    $cfg = preg_replace("/define\('DB_PORT',\s*\d+\)/",     "define('DB_PORT', $port)",   $cfg);
                    file_put_contents($cfgPath, $cfg);
                    $done[] = '✅ config.php updated with your database credentials.';
                }

                $success = empty($errors);
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
<title>OLLC Clinic — Setup</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', sans-serif; background: #eef3f9; color: #0f1e33; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
  .card { background: #fff; border-radius: 16px; box-shadow: 0 8px 40px rgba(0,0,0,.12); padding: 2.5rem; width: 100%; max-width: 480px; border-top: 5px solid #f0c030; }
  .logo { display: flex; align-items: center; gap: .9rem; margin-bottom: 1.8rem; }
  .logo img { width: 52px; height: 52px; border-radius: 50%; border: 2px solid #1a3a6b; }
  .logo-text { font-weight: 800; font-size: 1rem; color: #1a3a6b; }
  .logo-sub { font-size: .75rem; color: #4a6080; }
  h2 { font-size: 1.25rem; font-weight: 700; margin-bottom: .4rem; }
  p { font-size: .875rem; color: #4a6080; margin-bottom: 1.5rem; }
  label { display: block; font-size: .82rem; font-weight: 600; color: #4a6080; margin-bottom: .35rem; margin-top: .9rem; }
  input { width: 100%; padding: .65rem 1rem; border: 1.5px solid #c5d5e8; border-radius: 8px; font-size: .9rem; outline: none; transition: border .2s; }
  input:focus { border-color: #1a3a6b; }
  .btn { margin-top: 1.4rem; width: 100%; padding: .85rem; background: #1a3a6b; color: #fff; border: none; border-radius: 8px; font-size: .95rem; font-weight: 600; cursor: pointer; transition: background .2s; }
  .btn:hover { background: #2556a8; }
  .result { margin-top: 1.4rem; border-radius: 10px; padding: 1rem 1.2rem; }
  .result.ok  { background: #dce8f8; }
  .result.err { background: #fde8ea; }
  .result li  { font-size: .875rem; padding: .3rem 0; list-style: none; }
  .warn { background: #fff7e0; border: 1px solid #f0c030; border-radius: 8px; padding: .9rem 1rem; margin-top: 1.2rem; font-size: .82rem; color: #a16207; }
  .demo-box { background: #dce8f8; border-radius: 8px; padding: .9rem 1rem; margin-top: 1rem; font-size: .82rem; font-family: monospace; color: #1a3a6b; line-height: 1.7; }
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <img src="img/ollc_logo.png" alt="OLLC" />
    <div>
      <div class="logo-text">Valenzuela Clinic</div>
      <div class="logo-sub">System Setup & Installer</div>
    </div>
  </div>

  <?php if ($success): ?>
    <h2>🎉 Setup Complete!</h2>
    <div class="result ok">
      <ul>
        <?php foreach ($done as $d) echo '<li>' . htmlspecialchars($d) . '</li>'; ?>
      </ul>
    </div>
    <div class="demo-box">
      <strong>Demo Login Credentials:</strong><br>
      🎓 Student:  patient@clinic.com / <strong>password123</strong><br>
      Admin:   admin@clinic.com    / <strong>admin123</strong><br>
      Staff:   staff@clinic.com    / <strong>admin123</strong>
    </div>
    <div class="warn">
      ⚠️ <strong>Security:</strong> Delete <code>setup.php</code> from your server now!<br>
      Then open: <a href="index.php" style="color:#1a3a6b">→ Go to the System</a>
    </div>

  <?php else: ?>
    <h2>Database Setup</h2>
    <p>Enter your MySQL credentials. This will import the database structure and create all demo accounts with secure bcrypt passwords.</p>

    <?php if ($errors): ?>
    <div class="result err">
      <ul><?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul>
    </div>
    <?php endif; ?>

    <?php if ($done): ?>
    <div class="result ok">
      <ul><?php foreach ($done as $d) echo '<li>' . htmlspecialchars($d) . '</li>'; ?></ul>
    </div>
    <?php endif; ?>

    <form method="post">
      <label>MySQL Host</label>
      <input name="host" value="<?= htmlspecialchars($host) ?>" placeholder="localhost" />
      <label>Database Name</label>
      <input name="name" value="<?= htmlspecialchars($name) ?>" placeholder="valenzuela_clinic" />
      <label>MySQL Username</label>
      <input name="user" value="<?= htmlspecialchars($user) ?>" placeholder="root" />
      <label>MySQL Password <span style="font-weight:400">(blank for default XAMPP)</span></label>
      <input name="pass" type="password" value="<?= htmlspecialchars($pass) ?>" placeholder="(leave blank for XAMPP default)" />
      <label>Port</label>
      <input name="port" type="number" value="<?= (int)$port ?>" placeholder="3306" />
      <button class="btn" type="submit">⚡ Run Setup</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
