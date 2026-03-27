<?php
// ============================================================
//  api/notifications.php — Student Notifications
//  OLLC School Clinic · Appointment System
// ============================================================

require_once __DIR__ . '/../includes/config.php';

$user   = requireLogin();
$action = $_GET['action'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$db     = getDB();

switch ($action) {

    case 'list':
        $data = dbAll($db,
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50',
            'i', [$user['id']]
        );
        jsonOut(['success' => true, 'data' => $data]);
        break;

    case 'unread_count':
        $count = (int)dbVal($db,
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0',
            'i', [$user['id']]
        );
        jsonOut(['success' => true, 'count' => $count]);
        break;

    case 'mark_read':
        dbExec($db, 'UPDATE notifications SET is_read = 1 WHERE user_id = ?', 'i', [$user['id']]);
        jsonOut(['success' => true]);
        break;

    case 'delete_all':
        dbExec($db, 'DELETE FROM notifications WHERE user_id = ?', 'i', [$user['id']]);
        jsonOut(['success' => true]);
        break;

    default:
        jsonOut(['success' => false, 'error' => 'Unknown action'], 400);
}
