<?php
// ============================================================
//  api/appointments.php — Appointments CRUD + Services List
//  Valenzuela Clinic System
// ============================================================

require_once __DIR__ . '/../includes/config.php';

$user   = requireLogin();
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$db     = getDB();

switch ($action) {

    // ── List Appointments ──────────────────────────────────
    case 'list':
        if (in_array($user['role'], ['admin', 'staff'])) {

            $where  = '1=1';
            $types  = '';
            $params = [];

            if (!empty($_GET['status'])) {
                $where   .= ' AND a.status = ?';
                $types   .= 's';
                $params[] = $_GET['status'];
            }
            if (!empty($_GET['date_filter'])) {
                if ($_GET['date_filter'] === 'today') {
                    $where .= ' AND a.apt_date = CURDATE()';
                } elseif ($_GET['date_filter'] === 'week') {
                    $where .= ' AND a.apt_date BETWEEN DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)'
                            . ' AND DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 6 DAY)';
                }
            }
            if (!empty($_GET['service_id'])) {
                $where   .= ' AND a.service_id = ?';
                $types   .= 'i';
                $params[] = (int)$_GET['service_id'];
            }
            if (!empty($_GET['q'])) {
                $q        = '%' . $_GET['q'] . '%';
                $where   .= ' AND (u.first_name LIKE ? OR u.last_name LIKE ? OR a.reason LIKE ? OR a.apt_code LIKE ? OR s.name LIKE ?)';
                $types   .= 'sssss';
                $params   = array_merge($params, [$q, $q, $q, $q, $q]);
            }

            $sql  = "SELECT a.*, u.first_name, u.last_name, u.patient_no AS patient_no,
                            u.phone, u.email,
                            s.name AS service_name, s.icon AS service_icon
                     FROM appointments a
                     JOIN users u ON u.id = a.patient_id
                     LEFT JOIN services s ON s.id = a.service_id
                     WHERE $where
                     ORDER BY a.created_at DESC";
            $stmt = $db->prepare($sql);
            if ($types) $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            jsonOut(['success' => true, 'data' => $data]);

        } else {
            // Patient: own appointments only
            $data = dbAll($db,
                'SELECT a.*, s.name AS service_name, s.icon AS service_icon
                 FROM appointments a
                 LEFT JOIN services s ON s.id = a.service_id
                 WHERE a.patient_id = ?
                 ORDER BY a.created_at DESC',
                'i', [$user['id']]
            );
            jsonOut(['success' => true, 'data' => $data]);
        }
        break;

    // ── List Available Services ────────────────────────────
    case 'services':
        $data = dbAll($db,
            'SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order',
        );
        jsonOut(['success' => true, 'data' => $data]);
        break;

    // ── Book Appointment ───────────────────────────────────
    case 'book':
        if ($user['role'] !== 'patient') {
            jsonOut(['success' => false, 'error' => 'Only patients can book appointments.']);
        }

        $serviceId = (int)($body['service_id'] ?? 0);
        $reason    = trim($body['reason'] ?? '');
        $notes     = trim($body['notes']   ?? '');
        $date      = trim($body['date']    ?? '');
        $time      = trim($body['time']    ?? '');

        if (!$date || !$time) {
            jsonOut(['success' => false, 'error' => 'Date and time are required.']);
        }

        // Validate service if provided
        if ($serviceId) {
            $svc = dbRow($db, 'SELECT id FROM services WHERE id = ? AND is_active = 1', 'i', [$serviceId]);
            if (!$svc) jsonOut(['success' => false, 'error' => 'Selected service is not available.']);
        }

        if ($date < date('Y-m-d')) {
            jsonOut(['success' => false, 'error' => 'Cannot book appointments in the past.']);
        }

        // Conflict check
        $conflict = dbRow($db,
            "SELECT id FROM appointments WHERE patient_id = ? AND apt_date = ? AND apt_time = ? AND status != 'Cancelled'",
            'iss', [$user['id'], $date, $time]
        );
        if ($conflict) {
            jsonOut(['success' => false, 'error' => 'You already have an appointment at this time.']);
        }

        // Capacity check
        $booked  = (int)dbVal($db,
            "SELECT COUNT(*) FROM appointments WHERE apt_date = ? AND apt_time = ? AND status != 'Cancelled'",
            'ss', [$date, $time]
        );
        $slotRow = dbRow($db, 'SELECT max_per_day FROM time_slots WHERE slot_time = ?', 's', [$time]);
        $max     = $slotRow ? (int)$slotRow['max_per_day'] : 3;

        if ($booked >= $max) {
            jsonOut(['success' => false, 'error' => 'This time slot is fully booked.']);
        }

        $code = 'APT-' . strtoupper(substr(uniqid(), -5));
        $ok   = dbExec($db,
            'INSERT INTO appointments (apt_code, patient_id, service_id, reason, notes, apt_date, apt_time, status, priority)
             VALUES (?, ?, ?, ?, ?, ?, ?, "Pending", "normal")',
            'siissss', [$code, $user['id'], $serviceId ?: null, $reason, $notes, $date, $time]
        );
        if (!$ok) jsonOut(['success' => false, 'error' => 'Booking failed. Please try again.']);

        $aptId = $db->insert_id;
        dbExec($db,
            'INSERT INTO audit_logs (action, performed_by, detail) VALUES ("BOOK", ?, ?)',
            'is', [$user['id'], $user['first_name'] . ' ' . $user['last_name'] . ' booked ' . $code]
        );

        jsonOut(['success' => true, 'apt_code' => $code, 'id' => $aptId]);
        break;

    // ── Update Status (admin/staff only) ───────────────────
    case 'update':
        requireAdmin();

        $id         = (int)($body['id']          ?? 0);
        $status     = $body['status']             ?? '';
        $priority   = $body['priority']           ?? '';
        $adminNotes = $body['admin_notes']        ?? '';
        $newDate    = $body['new_date']           ?? '';
        $newTime    = $body['new_time']           ?? '';

        if (!$id) jsonOut(['success' => false, 'error' => 'Missing appointment ID.']);

        $apt = dbRow($db, 'SELECT * FROM appointments WHERE id = ?', 'i', [$id]);
        if (!$apt) jsonOut(['success' => false, 'error' => 'Appointment not found.']);

        $sets   = [];
        $types  = '';
        $params = [];

        if ($status)            { $sets[] = 'status = ?';      $types .= 's'; $params[] = $status; }
        if ($priority)          { $sets[] = 'priority = ?';    $types .= 's'; $params[] = $priority; }
        if ($adminNotes !== '') { $sets[] = 'admin_notes = ?'; $types .= 's'; $params[] = $adminNotes; }
        if ($newDate)           { $sets[] = 'apt_date = ?';    $types .= 's'; $params[] = $newDate; }
        if ($newTime)           { $sets[] = 'apt_time = ?';    $types .= 's'; $params[] = $newTime; }
        $sets[]  = 'updated_by = ?'; $types .= 'i'; $params[] = $user['id'];

        if (count($sets) <= 1) jsonOut(['success' => false, 'error' => 'Nothing to update.']);

        $params[] = $id; $types .= 'i';
        $stmt = $db->prepare('UPDATE appointments SET ' . implode(', ', $sets) . ' WHERE id = ?');
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        // Notify patient
        $msg = '';
        if ($status === 'Approved') {
            $msg = 'Your appointment ' . $apt['apt_code'] . ' has been APPROVED. Please visit the clinic on ' .
                   date('M j, Y', strtotime($apt['apt_date'])) . ' at ' . $apt['apt_time'] . '.';
        } elseif ($status === 'Cancelled') {
            $msg = 'Your appointment ' . $apt['apt_code'] . ' has been CANCELLED by the clinic.';
        } elseif ($status === 'Rescheduled') {
            $d   = $newDate ?: $apt['apt_date'];
            $t   = $newTime ?: $apt['apt_time'];
            $msg = 'Your appointment ' . $apt['apt_code'] . ' has been RESCHEDULED to ' . date('M j, Y', strtotime($d)) . ' at ' . $t . '.';
        }
        if ($msg) {
            dbExec($db, 'INSERT INTO notifications (user_id, message) VALUES (?, ?)', 'is', [$apt['patient_id'], $msg]);
        }

        $label = strtoupper($status ?: 'UPDATE');
        dbExec($db,
            'INSERT INTO audit_logs (action, performed_by, detail) VALUES (?, ?, ?)',
            'sis', [$label, $user['id'], $user['first_name'] . ' ' . $user['last_name'] . ' ' . strtolower($label) . 'd ' . $apt['apt_code']]
        );

        jsonOut(['success' => true]);
        break;

    // ── Cancel (patient cancels own pending) ───────────────
    case 'cancel':
        $id  = (int)($body['id'] ?? 0);
        $apt = dbRow($db, 'SELECT * FROM appointments WHERE id = ? AND patient_id = ?', 'ii', [$id, $user['id']]);

        if (!$apt) jsonOut(['success' => false, 'error' => 'Appointment not found.']);
        if ($apt['status'] !== 'Pending') {
            jsonOut(['success' => false, 'error' => 'Only pending appointments can be cancelled.']);
        }

        dbExec($db, "UPDATE appointments SET status = 'Cancelled', updated_by = ? WHERE id = ?", 'ii', [$user['id'], $id]);
        dbExec($db, 'INSERT INTO audit_logs (action, performed_by, detail) VALUES ("CANCEL", ?, ?)', 'is', [$user['id'], 'Patient cancelled ' . $apt['apt_code']]);

        jsonOut(['success' => true]);
        break;

    // ── Slot Availability ──────────────────────────────────
    case 'slots':
        $date = $_GET['date'] ?? '';
        if (!$date) jsonOut(['success' => false, 'error' => 'Date required.']);

        $slots  = dbAll($db, 'SELECT slot_time, max_per_day FROM time_slots WHERE is_active = 1 ORDER BY sort_order');
        $rows   = dbAll($db,
            "SELECT apt_time, COUNT(*) AS cnt FROM appointments WHERE apt_date = ? AND status != 'Cancelled' GROUP BY apt_time",
            's', [$date]
        );
        $booked = [];
        foreach ($rows as $r) $booked[$r['apt_time']] = (int)$r['cnt'];

        $result = [];
        foreach ($slots as $s) {
            $b        = $booked[$s['slot_time']] ?? 0;
            $result[] = [
                'time'      => $s['slot_time'],
                'max'       => (int)$s['max_per_day'],
                'booked'    => $b,
                'available' => $b < (int)$s['max_per_day'],
            ];
        }

        jsonOut(['success' => true, 'slots' => $result]);
        break;

    // ── Stats ──────────────────────────────────────────────
    case 'stats':
        if (in_array($user['role'], ['admin', 'staff'])) {
            jsonOut([
                'success'  => true,
                'today'    => (int)dbVal($db, "SELECT COUNT(*) FROM appointments WHERE apt_date = CURDATE() AND status != 'Cancelled'"),
                'pending'  => (int)dbVal($db, "SELECT COUNT(*) FROM appointments WHERE status = 'Pending'"),
                'approved' => (int)dbVal($db, "SELECT COUNT(*) FROM appointments WHERE status = 'Approved'"),
                'urgent'   => (int)dbVal($db, "SELECT COUNT(*) FROM appointments WHERE priority = 'urgent' AND status != 'Cancelled'"),
            ]);
        } else {
            jsonOut([
                'success'   => true,
                'total'     => (int)dbVal($db, 'SELECT COUNT(*) FROM appointments WHERE patient_id = ?', 'i', [$user['id']]),
                'pending'   => (int)dbVal($db, "SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND status = 'Pending'",   'i', [$user['id']]),
                'approved'  => (int)dbVal($db, "SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND status = 'Approved'",  'i', [$user['id']]),
                'cancelled' => (int)dbVal($db, "SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND status = 'Cancelled'", 'i', [$user['id']]),
            ]);
        }
        break;

    default:
        jsonOut(['success' => false, 'error' => 'Unknown action'], 400);
}
