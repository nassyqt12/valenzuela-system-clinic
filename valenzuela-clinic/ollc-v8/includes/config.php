<?php
// ============================================================
//  includes/config.php — Database Config & Session Bootstrap
//  OLLC School Clinic · Appointment System
//
//  FIXES APPLIED:
//  1. Removed circular include_once that caused fatal errors
//  2. Switched from PDO to mysqli (always available in XAMPP)
//  3. Fixed verifyPassword() — now actually checks stored hash
//  4. Removed broken DEMO_MODE that let anyone log in as anyone
//  5. Added proper CORS headers with credentials support
// ============================================================

// ── Database Credentials ──────────────────────────────────────
// Change these to match your XAMPP/WAMP MySQL setup
define('DB_HOST', 'localhost');
define('DB_NAME', 'valenzuela_clinic');
define('DB_USER', 'root');
define('DB_PASS', '');          // Default XAMPP = blank password
define('DB_PORT', 3306);

// ── Session Setup ─────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// ── CORS Headers (required for credentials: 'include' in JS) ──
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── mysqli Connection (replaces broken PDO) ───────────────────
function getDB(): mysqli {
    static $conn = null;
    if ($conn !== null) return $conn;

    // Check extension is available
    if (!extension_loaded('mysqli')) {
        http_response_code(500);
        die(json_encode([
            'success' => false,
            'error'   => 'mysqli extension not enabled. Open php.ini, uncomment "extension=mysqli", then restart Apache.'
        ]));
    }

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

    if ($conn->connect_error) {
        $msg = $conn->connect_error;
        // Give helpful messages for common errors
        if (str_contains($msg, 'Connection refused') || str_contains($msg, "Can't connect")) {
            $msg = 'Cannot connect to MySQL. Make sure MySQL is running in XAMPP/WAMP Control Panel.';
        } elseif (str_contains($msg, 'Access denied')) {
            $msg = 'Wrong DB username or password. Edit DB_USER and DB_PASS in includes/config.php.';
        } elseif (str_contains($msg, 'Unknown database')) {
            $msg = 'Database "' . DB_NAME . '" not found. Import ollc_clinic.sql into phpMyAdmin first.';
        }
        http_response_code(500);
        die(json_encode(['success' => false, 'error' => $msg]));
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}

// ── Helpers: query wrappers ───────────────────────────────────
/**
 * Run a SELECT and return all rows as assoc arrays.
 * Usage: dbAll($db, "SELECT * FROM users WHERE role=?", 's', [$role])
 */
function dbAll(mysqli $db, string $sql, string $types = '', array $params = []): array {
    $stmt = $db->prepare($sql);
    if (!$stmt) return [];
    if ($types && $params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Run a SELECT and return one row.
 */
function dbRow(mysqli $db, string $sql, string $types = '', array $params = []): ?array {
    $stmt = $db->prepare($sql);
    if (!$stmt) return null;
    if ($types && $params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

/**
 * Run INSERT/UPDATE/DELETE. Returns true on success.
 */
function dbExec(mysqli $db, string $sql, string $types = '', array $params = []): bool {
    $stmt = $db->prepare($sql);
    if (!$stmt) return false;
    if ($types && $params) $stmt->bind_param($types, ...$params);
    return $stmt->execute();
}

/**
 * Run a SELECT and return a single scalar value.
 */
function dbVal(mysqli $db, string $sql, string $types = '', array $params = []): mixed {
    $stmt = $db->prepare($sql);
    if (!$stmt) return null;
    if ($types && $params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_row();
    return $row ? $row[0] : null;
}

// ── JSON Response Helper ──────────────────────────────────────
function jsonOut(array $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// ── Password Hashing ──────────────────────────────────────────
function hashPw(string $plain): string {
    return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password — always checks the stored bcrypt hash.
 * FIX: Original code ignored $stored and compared against a
 *      hardcoded DEMO constant, so newly registered users
 *      could never log in. This always checks the real hash.
 */
function verifyPw(string $input, string $stored): bool {
    // Bcrypt check (production + setup.php accounts)
    if (password_verify($input, $stored)) return true;
    // Legacy fallback for plain-text seeds imported from old SQL
    if ($input === $stored) return true;
    return false;
}

// ── Auth Guards ───────────────────────────────────────────────
function requireLogin(): array {
    if (empty($_SESSION['user'])) {
        jsonOut(['success' => false, 'error' => 'Not authenticated'], 401);
    }
    return $_SESSION['user'];
}

function requireAdmin(): array {
    $user = requireLogin();
    if (!in_array($user['role'], ['admin', 'staff'])) {
        jsonOut(['success' => false, 'error' => 'Access denied'], 403);
    }
    return $user;
}
