<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

require_once(__DIR__ . '/../../../db/database.php');
require_once(__DIR__ . '/../../../system/functions/functions.php');

session_start();

// Admin verification
$user_id = $_SESSION['user_id'] ?? null;
$user = $db->query("SELECT is_admin FROM users WHERE id = '" . (int)$user_id . "'")->fetch_assoc();
if (!$user_id || !$user || $user['is_admin'] != 1) {
    die("❌ Access Denied.");
}

// Sanitize and validate input
$max_pending = isset($_POST['max_pending_per_user']) ? max(0, (int)$_POST['max_pending_per_user']) : 3;
$auto_approve = isset($_POST['auto_approve_tasks']) ? (int)$_POST['auto_approve_tasks'] : 0;
$reset_time = isset($_POST['reset_approved_jobs']) ? max(0, (int)$_POST['reset_approved_jobs']) : 1440;

// Store values in settings_task_addon
$settings = [
    'max_pending_per_user' => $max_pending,
    'auto_approve_tasks' => $auto_approve,
    'reset_approved_jobs' => $reset_time
];

foreach ($settings as $name => $value) {
    $name = $db->real_escape_string($name);
    $value = $db->real_escape_string($value);

    $exists = $db->query("SELECT id FROM settings_task_addon WHERE name = '$name' LIMIT 1");
    if ($exists && $exists->num_rows > 0) {
        $db->query("UPDATE settings_task_addon SET value = '$value' WHERE name = '$name'");
    } else {
        $db->query("INSERT INTO settings_task_addon (name, value) VALUES ('$name', '$value')");
    }
}

// Return success to iframe
echo "<script>
    window.parent.postMessage({ type: 'addon_save_success', message: '✅ Task settings saved successfully!' }, '*');
</script>";
exit;
