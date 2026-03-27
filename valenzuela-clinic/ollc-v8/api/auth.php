<?php
// ============================================================
//  api/auth.php — Login, Register, Logout, Session Check
//  Valenzuela Clinic System
// ============================================================

require_once __DIR__ . '/../includes/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($action) {

    // ── Login ──────────────────────────────────────────────
    case 'login':
        $identifier = trim($body['identifier'] ?? '');
        $password   = $body['password']        ?? '';
        $mode       = $body['mode']            ?? 'patient';

        if (!$identifier || !$password) {
            jsonOut(['success' => false, 'error' => 'Please fill in all fields.']);
        }

        $db   = getDB();
        $user = dbRow($db,
            'SELECT * FROM users WHERE email = ? OR patient_no = ? LIMIT 1',
            'ss', [$identifier, $identifier]
        );

        if (!$user || !verifyPw($password, $user['password'])) {
            jsonOut(['success' => false, 'error' => 'Invalid credentials. Please try again.']);
        }

        if ($mode === 'admin' && !in_array($user['role'], ['admin', 'staff'])) {
            jsonOut(['success' => false, 'error' => 'Access denied. Clinic staff account required.']);
        }
        if ($mode === 'patient' && $user['role'] !== 'patient') {
            jsonOut(['success' => false, 'error' => 'Please use the Clinic Staff login.']);
        }

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id'         => (int)$user['id'],
            'email'      => $user['email'],
            'role'       => $user['role'],
            'first_name' => $user['first_name'],
            'last_name'  => $user['last_name'],
            'patient_no' => $user['patient_no'] ?? null,
            'phone'      => $user['phone']      ?? null,
        ];

        jsonOut(['success' => true, 'user' => $_SESSION['user']]);
        break;

    // ── Register (Patient) ─────────────────────────────────
    case 'register':
        $firstName = trim($body['firstName'] ?? '');
        $lastName  = trim($body['lastName']  ?? '');
        $email     = trim($body['email']     ?? '');
        $phone     = trim($body['phone']     ?? '');
        $password  = $body['password']       ?? '';

        if (!$firstName || !$lastName || !$email || !$password) {
            jsonOut(['success' => false, 'error' => 'Please fill in all required fields.']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonOut(['success' => false, 'error' => 'Invalid email format.']);
        }

        $db  = getDB();
        $chk = dbRow($db, 'SELECT id FROM users WHERE email = ?', 's', [$email]);
        if ($chk) jsonOut(['success' => false, 'error' => 'This email is already registered.']);

        // Auto-generate patient number
        $lastNo   = (int)dbVal($db, 'SELECT COUNT(*) FROM users WHERE role = "patient"');
        $patientNo = 'P-' . date('Y') . '-' . str_pad($lastNo + 1, 3, '0', STR_PAD_LEFT);

        $hash = hashPw($password);
        $ok   = dbExec($db,
            'INSERT INTO users (patient_no, email, password, role, first_name, last_name, phone) VALUES (?, ?, ?, "patient", ?, ?, ?)',
            'ssssss', [$patientNo, $email, $hash, $firstName, $lastName, $phone]
        );
        if (!$ok) jsonOut(['success' => false, 'error' => 'Registration failed. Please try again.']);

        jsonOut(['success' => true, 'message' => 'Account created! You can now log in.', 'patient_no' => $patientNo]);
        break;

    // ── Self-Register as Clinic Staff ──────────────────────
    case 'register_staff_self':
        $firstName = trim($body['firstName'] ?? '');
        $lastName  = trim($body['lastName']  ?? '');
        $email     = trim($body['email']     ?? '');
        $password  = $body['password']       ?? '';
        $position  = trim($body['position']  ?? 'Clinic Nurse');
        $licenseNo = trim($body['licenseNo'] ?? '');

        if (!$firstName || !$lastName || !$email || !$password) {
            jsonOut(['success' => false, 'error' => 'Please fill in all required fields.']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonOut(['success' => false, 'error' => 'Invalid email format.']);
        }
        if (strlen($password) < 6) {
            jsonOut(['success' => false, 'error' => 'Password must be at least 6 characters.']);
        }

        $db  = getDB();
        $chk = dbRow($db, 'SELECT id FROM users WHERE email = ?', 's', [$email]);
        if ($chk) jsonOut(['success' => false, 'error' => 'This email is already registered.']);

        $hash = hashPw($password);
        $ok   = dbExec($db,
            'INSERT INTO users (email, password, role, first_name, last_name) VALUES (?, ?, "staff", ?, ?)',
            'ssss', [$email, $hash, $firstName, $lastName]
        );
        if (!$ok) jsonOut(['success' => false, 'error' => 'Registration failed. Please try again.']);

        $uid = $db->insert_id;
        dbExec($db,
            'INSERT INTO clinic_staff (user_id, position, license_no, is_active) VALUES (?, ?, ?, 1)',
            'iss', [$uid, $position, $licenseNo]
        );
        dbExec($db,
            'INSERT INTO audit_logs (action, performed_by, detail) VALUES ("STAFF_REGISTER", ?, ?)',
            'is', [$uid, 'Self-registered: ' . $firstName . ' ' . $lastName . ' (' . $position . ')']
        );

        jsonOut(['success' => true, 'message' => 'Staff account created! You can now log in.']);
        break;

    // ── Logout ─────────────────────────────────────────────
    case 'logout':
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        jsonOut(['success' => true]);
        break;

    // ── Session Check ──────────────────────────────────────
    case 'me':
        if (!empty($_SESSION['user'])) {
            $db   = getDB();
            $user = dbRow($db, 'SELECT * FROM users WHERE id = ? LIMIT 1', 'i', [$_SESSION['user']['id']]);
            if ($user) {
                $_SESSION['user']['role']       = $user['role'];
                $_SESSION['user']['first_name'] = $user['first_name'];
                $_SESSION['user']['last_name']  = $user['last_name'];
            }
            jsonOut(['success' => true, 'user' => $_SESSION['user']]);
        }
        jsonOut(['success' => false, 'error' => 'Not logged in']);
        break;

    default:
        jsonOut(['success' => false, 'error' => 'Unknown action'], 400);
}
