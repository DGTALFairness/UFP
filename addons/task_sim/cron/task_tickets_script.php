<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

// Include database connection & functions
require_once(__DIR__ . '/../../../db/database.php');
require_once(__DIR__ . '/../../../system/functions/functions.php');

// Exit if this addon is disabled
$addon_check = $db->query("SELECT is_active FROM active_addons WHERE addon_name = 'task_sim' LIMIT 1");
if (!$addon_check || (int) $addon_check->fetch_assoc()['is_active'] !== 1) {
    exit;
}

// Get current time and active round
$current_time = time();
$active_round = $db->query("SELECT round_id FROM rounds WHERE closed = 0 ORDER BY round_id DESC LIMIT 1")->fetch_assoc();
if (!$active_round) exit;
$round_id = (int)$active_round['round_id'];

// Load task settings
$settings = [];
$res = $db->query("SELECT name, value FROM settings_task_addon");
while ($row = $res->fetch_assoc()) {
    $settings[$row['name']] = $row['value'];
}

$max_pending = (int)($settings['max_pending_per_user'] ?? 3);
$reset_seconds = (int)($settings['reset_approved_jobs'] ?? 1440) * 60;

// Fetch up to 5 random TestUsers
$bots = $db->query("SELECT id FROM users WHERE username LIKE 'TestUser%' ORDER BY RAND() LIMIT 5");
if (!$bots || $bots->num_rows === 0) exit;

while ($bot = $bots->fetch_assoc()) {
    $user_id = (int)$bot['id'];

    // Skip if user has too many pending
    $pending = $db->query("SELECT COUNT(*) AS total FROM task_addon_pending WHERE user_id = '$user_id'")->fetch_assoc();
    if ($pending['total'] >= $max_pending) continue;

    // Find one random task
    $task_res = $db->query("SELECT * FROM task_addon_jobs WHERE is_active = 1 ORDER BY RAND() LIMIT 1");
    if (!$task_res || $task_res->num_rows === 0) continue;

    $task = $task_res->fetch_assoc();
    $task_id = (int)$task['task_id'];

    // Check cooldown
    $done = $db->query("SELECT approved_at FROM task_addon_done WHERE task_id = '$task_id' AND user_id = '$user_id' LIMIT 1");
    if ($done && $done->num_rows > 0) {
        $approved_at = (int)$done->fetch_assoc()['approved_at'];
        if ($approved_at + $reset_seconds > $current_time) continue;
        $db->query("DELETE FROM task_addon_done WHERE task_id = '$task_id' AND user_id = '$user_id'");
    }

    // Remove old pending
    $db->query("DELETE FROM task_addon_pending WHERE task_id = '$task_id' AND user_id = '$user_id'");

    // Generate random submission text
    $submission_text = "BotSubmission_" . substr(md5(uniqid()), 0, 8);

    // Insert into done (forced auto-approval)
    $stmt = $db->prepare("
        INSERT INTO task_addon_done (task_id, user_id, submission_data, submitted_at, approved_at, approved_by)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iisiii", $task_id, $user_id, $submission_text, $current_time, $current_time, $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Update total_tasks for this round
    $db->query("UPDATE rounds SET total_tasks = total_tasks + 1 WHERE round_id = '$round_id'");


    // Add ticket to round
    $stmt = $db->prepare("INSERT INTO current_round_tickets (round_id, user_id, date) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $round_id, $user_id, $current_time);
    $stmt->execute();
    $stmt->close();
}


// ✅ Move expired pending tasks to task_addon_missed
echo "⏳ Checking for expired pending task submissions...\n";

// Load reset window from settings
$reset_setting = $db->query("SELECT value FROM settings_task_addon WHERE name = 'reset_approved_jobs' LIMIT 1");
$reset_minutes = $reset_setting && $reset_setting->num_rows > 0 ? (int)$reset_setting->fetch_assoc()['value'] : 1440;
$reset_seconds = $reset_minutes * 60;
$now = time();

// Select all pending tasks
$pending = $db->query("SELECT * FROM task_addon_pending");
$expired_count = 0;

while ($row = $pending->fetch_assoc()) {
    $task_id    = (int)$row['task_id'];
    $user_id    = (int)$row['user_id'];
    $submitted  = (int)$row['submitted_at'];
    $round_id   = (int)$row['round_id'];
    $id         = (int)$row['id'];

    // Check if round is closed or expired
    $round = $db->query("SELECT closed, expected_end_date FROM rounds WHERE round_id = '$round_id' LIMIT 1");
    $isExpired = true;

    if ($round && $round->num_rows > 0) {
        $r = $round->fetch_assoc();
        $isExpired = ((int)$r['closed'] === 1 || $now > (int)$r['expected_end_date']);
    }

    if ($isExpired) {
        // Move to missed table
        $stmt = $db->prepare("INSERT INTO task_addon_missed (task_id, round_id, user_id, submission_data, submitted_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisi", $task_id, $round_id, $user_id, $row['submission_data'], $submitted);
        $stmt->execute();
        $stmt->close();

        // Delete from pending
        $db->query("DELETE FROM task_addon_pending WHERE id = '$id' LIMIT 1");

        $expired_count++;
    }
}

echo "✅ Moved $expired_count expired pending tasks to task_addon_missed.\n";


?>
