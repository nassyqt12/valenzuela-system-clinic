<?php
// ============================================================
//  api/admin.php  —  Admin: Patients, Staff, Slots, Reports
//  Valenzuela Clinic System
// ============================================================

require_once __DIR__ . '/../includes/config.php';

$user   = requireAdmin();
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$db     = getDB();

switch ($action) {

    // ── List Patients ──────────────────────────────────────
    case 'students':
        $q = trim($_GET['q'] ?? '');
        if ($q) {
            $like = '%' . $q . '%';
            $data = dbAll($db,
                "SELECT u.*, COUNT(a.id) AS apt_count
                 FROM users u
                 LEFT JOIN appointments a ON a.patient_id = u.id
                 WHERE u.role = 'patient'
                   AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.patient_no LIKE ? OR u.email LIKE ?)
                 GROUP BY u.id ORDER BY u.last_name",
                'ssss', [$like, $like, $like, $like]
            );
        } else {
            $data = dbAll($db,
                "SELECT u.*, COUNT(a.id) AS apt_count
                 FROM users u
                 LEFT JOIN appointments a ON a.patient_id = u.id
                 WHERE u.role = 'patient'
                 GROUP BY u.id ORDER BY u.last_name"
            );
        }
        jsonOut(['success' => true, 'data' => $data]);
        break;

    // ── Patient Appointment History ────────────────────────
    case 'student_history':
        $uid  = (int)($_GET['uid'] ?? 0);
        $data = dbAll($db,
            'SELECT a.*, s.name AS service_name
             FROM appointments a
             LEFT JOIN services s ON s.id = a.service_id
             WHERE a.patient_id = ?
             ORDER BY a.created_at DESC',
            'i', [$uid]
        );
        jsonOut(['success' => true, 'data' => $data]);
        break;

    // ── List Staff ─────────────────────────────────────────
    case 'staff_list':
        $data = dbAll($db,
            "SELECT u.id, u.first_name, u.last_name, u.email, u.role,
                    cs.position, cs.license_no, cs.is_active
             FROM users u
             LEFT JOIN clinic_staff cs ON cs.user_id = u.id
             WHERE u.role IN ('admin','staff')
             ORDER BY u.first_name"
        );
        jsonOut(['success' => true, 'data' => $data]);
        break;

    // ── Add Staff ──────────────────────────────────────────
    case 'add_staff':
        $firstName = trim($body['firstName'] ?? '');
        $lastName  = trim($body['lastName']  ?? '');
        $email     = trim($body['email']     ?? '');
        $password  = $body['password']       ?? '';
        $position  = trim($body['position']  ?? 'Clinic Nurse');
        $licenseNo = trim($body['licenseNo'] ?? '');
        $role      = in_array($body['role'] ?? '', ['admin', 'staff']) ? $body['role'] : 'staff';

        if (!$firstName || !$lastName || !$email || !$password) {
            jsonOut(['success' => false, 'error' => 'Please fill all required fields.']);
        }

        $chk = dbRow($db, 'SELECT id FROM users WHERE email = ?', 's', [$email]);
        if ($chk) jsonOut(['success' => false, 'error' => 'Email already exists.']);

        $hash = hashPw($password);
        $ok   = dbExec($db,
            'INSERT INTO users (email, password, role, first_name, last_name) VALUES (?, ?, ?, ?, ?)',
            'sssss', [$email, $hash, $role, $firstName, $lastName]
        );
        if (!$ok) jsonOut(['success' => false, 'error' => 'Failed to create account.']);

        $uid = $db->insert_id;
        dbExec($db,
            'INSERT INTO clinic_staff (user_id, position, license_no, is_active) VALUES (?, ?, ?, 1)',
            'iss', [$uid, $position, $licenseNo]
        );
        dbExec($db,
            'INSERT INTO audit_logs (action, performed_by, detail) VALUES ("ADD_STAFF", ?, ?)',
            'is', [$user['id'], 'Added staff: ' . $firstName . ' ' . $lastName . ' (' . $role . ')']
        );

        jsonOut(['success' => true, 'message' => 'Staff account created.']);
        break;

    // ── Toggle Staff Active ────────────────────────────────
    case 'toggle_staff':
        $uid      = (int)($body['uid']    ?? 0);
        $isActive = (int)($body['active'] ?? 0);
        dbExec($db, 'UPDATE clinic_staff SET is_active = ? WHERE user_id = ?', 'ii', [$isActive, $uid]);
        jsonOut(['success' => true]);
        break;

    // ── Delete Staff ───────────────────────────────────────
    case 'delete_staff':
        $uid = (int)($body['uid'] ?? 0);
        if ($uid === $user['id']) {
            jsonOut(['success' => false, 'error' => 'Cannot delete your own account.']);
        }
        dbExec($db, "DELETE FROM users WHERE id = ? AND role IN ('admin','staff')", 'i', [$uid]);
        jsonOut(['success' => true]);
        break;

    // ── List Services ──────────────────────────────────────
    case 'services_list':
        $data = dbAll($db, 'SELECT * FROM services ORDER BY sort_order');
        jsonOut(['success' => true, 'data' => $data]);
        break;

    // ── Add Service ────────────────────────────────────────
    case 'add_service':
        $name = trim($body['name'] ?? '');
        $desc = trim($body['description'] ?? '');
        $icon = trim($body['icon'] ?? 'fa-stethoscope');
        if (!$name) jsonOut(['success' => false, 'error' => 'Service name required.']);

        $maxOrder = (int)dbVal($db, 'SELECT MAX(sort_order) FROM services');
        dbExec($db,
            'INSERT INTO services (name, description, icon, sort_order) VALUES (?, ?, ?, ?)',
            'sssi', [$name, $desc, $icon, $maxOrder + 1]
        );
        jsonOut(['success' => true]);
        break;

    // ── Toggle Service Active ──────────────────────────────
    case 'toggle_service':
        $id       = (int)($body['id']     ?? 0);
        $isActive = (int)($body['active'] ?? 0);
        dbExec($db, 'UPDATE services SET is_active = ? WHERE id = ?', 'ii', [$isActive, $id]);
        jsonOut(['success' => true]);
        break;

    // ── List Time Slots ────────────────────────────────────
    case 'slots':
        $data = dbAll($db, 'SELECT * FROM time_slots ORDER BY sort_order');
        jsonOut(['success' => true, 'data' => $data]);
        break;

    // ── Add Time Slot ──────────────────────────────────────
    case 'add_slot':
        $time = trim($body['time'] ?? '');
        $max  = (int)($body['max'] ?? 3);
        if (!$time) jsonOut(['success' => false, 'error' => 'Time required.']);

        $chk = dbRow($db, 'SELECT id FROM time_slots WHERE slot_time = ?', 's', [$time]);
        if ($chk) jsonOut(['success' => false, 'error' => 'Slot already exists.']);

        $maxOrder = (int)dbVal($db, 'SELECT MAX(sort_order) FROM time_slots');
        dbExec($db,
            'INSERT INTO time_slots (slot_time, max_per_day, sort_order) VALUES (?, ?, ?)',
            'sii', [$time, $max, $maxOrder + 1]
        );
        jsonOut(['success' => true]);
        break;

    // ── Edit Slot Max ──────────────────────────────────────
    case 'edit_slot':
        $id  = (int)($body['id']  ?? 0);
        $max = (int)($body['max'] ?? 3);
        dbExec($db, 'UPDATE time_slots SET max_per_day = ? WHERE id = ?', 'ii', [$max, $id]);
        jsonOut(['success' => true]);
        break;

    // ── Delete Slot ────────────────────────────────────────
    case 'delete_slot':
        $id = (int)($body['id'] ?? 0);
        dbExec($db, 'DELETE FROM time_slots WHERE id = ?', 'i', [$id]);
        jsonOut(['success' => true]);
        break;

    // ── Audit Logs ─────────────────────────────────────────
    case 'audit':
        $data = dbAll($db,
            "SELECT al.*, CONCAT(u.first_name,' ',u.last_name) AS by_name
             FROM audit_logs al
             LEFT JOIN users u ON u.id = al.performed_by
             ORDER BY al.created_at DESC LIMIT 100"
        );
        jsonOut(['success' => true, 'data' => $data]);
        break;

    // ── Report Stats ───────────────────────────────────────
    case 'report_stats':
        $total     = (int)dbVal($db, 'SELECT COUNT(*) FROM appointments');
        $approved  = (int)dbVal($db, "SELECT COUNT(*) FROM appointments WHERE status='Approved'");
        $cancelled = (int)dbVal($db, "SELECT COUNT(*) FROM appointments WHERE status='Cancelled'");

        // Group by service name
        $by_service = dbAll($db,
            "SELECT COALESCE(s.name, 'No Service Selected') AS reason, COUNT(*) AS cnt
             FROM appointments a
             LEFT JOIN services s ON s.id = a.service_id
             GROUP BY a.service_id
             ORDER BY cnt DESC"
        );

        $trend = dbAll($db,
            "SELECT DATE_FORMAT(created_at,'%b %Y') AS month, COUNT(*) AS cnt
             FROM appointments
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
             GROUP BY DATE_FORMAT(created_at,'%Y-%m')
             ORDER BY created_at"
        );

        jsonOut([
            'success'   => true,
            'total'     => $total,
            'approved'  => $approved,
            'cancelled' => $cancelled,
            'by_reason' => $by_service,
            'reasons'   => $by_service,
            'trend'     => $trend,
        ]);
        break;

    // ── Week Chart Data ────────────────────────────────────
    case 'week_data':
        $rows = dbAll($db,
            "SELECT DAYNAME(apt_date) AS day_name, COUNT(*) AS cnt
             FROM appointments
             WHERE apt_date BETWEEN DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
                               AND DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 6 DAY)
               AND status != 'Cancelled'
             GROUP BY DAYOFWEEK(apt_date), DAYNAME(apt_date)
             ORDER BY DAYOFWEEK(apt_date)"
        );

        $days = ['Monday' => 0, 'Tuesday' => 0, 'Wednesday' => 0, 'Thursday' => 0, 'Friday' => 0, 'Saturday' => 0];
        foreach ($rows as $r) {
            if (array_key_exists($r['day_name'], $days)) {
                $days[$r['day_name']] = (int)$r['cnt'];
            }
        }

        jsonOut(['success' => true, 'labels' => array_keys($days), 'data' => array_values($days)]);
        break;

    // ── Services Stats for Dashboard ──────────────────────
    case 'service_stats':
        $data = dbAll($db,
            "SELECT s.name, s.icon, COUNT(a.id) AS visit_count
             FROM services s
             LEFT JOIN appointments a ON a.service_id = s.id AND a.status != 'Cancelled'
             WHERE s.is_active = 1
             GROUP BY s.id
             ORDER BY visit_count DESC"
        );
        jsonOut(['success' => true, 'data' => $data]);
        break;

    default:
        jsonOut(['success' => false, 'error' => 'Unknown action'], 400);
}
