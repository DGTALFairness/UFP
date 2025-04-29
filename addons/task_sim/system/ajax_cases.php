<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

require_once(__DIR__ . '/functions/functions.php'); // ✅ Task addon helpers

if (!isset($action)) return;
global $handled;

if ($action === 'submit_task_entry') {
    $handled = true;
    handleTaskSubmission();
}

function handleTaskSubmission() {
    global $db, $user_id;

    if (!$user_id) {
        task_addon_safe_json_response([
            "success" => false,
            "message" => "❌ You must be logged in to submit a task."
        ]);
    }

    // Load task addon settings
    $settings = [];
    $settingRow = $db->query("SELECT name, value FROM settings_task_addon");
    while ($s = $settingRow->fetch_assoc()) {
        $settings[$s['name']] = $s['value'];
    }

    $task_id = (int) ($_POST['task_id'] ?? 0);
    $submission_text = trim($_POST['submission_text'] ?? '');
    $submission_date = time();

    if (!$task_id || $submission_text === '') {
        task_addon_safe_json_response([
            "success" => false,
            "message" => "❌ Missing task or submission."
        ]);
    }

    // Validate task existence
    $task = $db->query("SELECT * FROM task_addon_jobs WHERE task_id = '$task_id' LIMIT 1");
    if (!$task || $task->num_rows === 0) {
        task_addon_safe_json_response([
            "success" => false,
            "message" => "❌ Task not found."
        ]);
    }

    // Handle reset cooldown check
    $reset_minutes = (int) ($settings['reset_approved_jobs'] ?? 1440);
    $reset_seconds = $reset_minutes * 60;
    $now = time();

    $alreadyDone = $db->query("SELECT approved_at FROM task_addon_done WHERE task_id = '$task_id' AND user_id = '$user_id' LIMIT 1");
    if ($alreadyDone && $alreadyDone->num_rows > 0) {
        $approved_at = (int) $alreadyDone->fetch_assoc()['approved_at'];
        if (($approved_at + $reset_seconds) > $now) {
            task_addon_safe_json_response([
                "success" => false,
                "message" => "⏳ You have already completed this task. Please wait for reset if allowed."
            ]);
        } else {
            // Task expired — allow again
            //$db->query("DELETE FROM task_addon_done WHERE task_id = '$task_id' AND user_id = '$user_id'");
        }
    }

    // Enforce max pending
    $max_pending = (int) ($settings['max_pending_per_user'] ?? 3);
    $auto_approve = (int) ($settings['auto_approve_tasks'] ?? 0);

    $pendingCount = $db->query("SELECT COUNT(*) AS total FROM task_addon_pending WHERE user_id = '$user_id'")
                       ->fetch_assoc()['total'] ?? 0;

    if ($pendingCount >= $max_pending) {
        task_addon_safe_json_response([
            "success" => false,
            "message" => "❌ You have too many pending tasks. Please wait for approval before submitting more."
        ]);
    }

    // Make sure there's an active round
    $round_check = $db->query("SELECT round_id FROM rounds WHERE closed = 0 ORDER BY round_id DESC LIMIT 1");
    if (!$round_check || $round_check->num_rows === 0) {
        task_addon_safe_json_response([
            "success" => false,
            "message" => "🚫 Cannot submit tasks — no active round is currently running."
        ]);
    }
    $round_id = (int)$round_check->fetch_assoc()['round_id'];

    // Insert into pending queue
    $stmt = $db->prepare("INSERT INTO task_addon_pending (task_id, round_id, user_id, submission_data, submitted_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisi", $task_id, $round_id, $user_id, $submission_text, $submission_date);
    $stmt->execute();
    $stmt->close();

    // Handle auto-approval if enabled
    if ($auto_approve === 1) {
        // Insert a new record into task_addon_done — don't overwrite
       $stmt = $db->prepare("INSERT INTO task_addon_done (task_id, round_id, user_id, submission_data, approved_at, approved_by)
                      SELECT ?, ?, ?, ?, UNIX_TIMESTAMP(), ? FROM DUAL
                      WHERE NOT EXISTS (SELECT 1 FROM task_addon_done WHERE task_id = ? AND user_id = ? LIMIT 1)");

        $stmt->bind_param("iiisiii", $task_id, $round_id, $user_id, $submission_text, $user_id, $task_id, $user_id);

        $stmt->execute();
        $stmt->close();

        // Remove from pending
        $db->query("DELETE FROM task_addon_pending WHERE task_id = '$task_id' AND user_id = '$user_id'");

        // Add ticket to current round
        $now = time();
        $stmt = $db->prepare("INSERT INTO current_round_tickets (round_id, user_id, date) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $round_id, $user_id, $now);
        $stmt->execute();
        $stmt->close();
        
        // Update total_tasks for this round
        $db->query("UPDATE rounds SET total_tasks = total_tasks + 1 WHERE round_id = '$round_id'");


        task_addon_safe_json_response([
            "success" => true,
            "message" => "✅ Task submitted and automatically approved. You’ve earned 1 ticket!"
        ]);
    }

    // Manual review fallback
    task_addon_safe_json_response([
        "success" => true,
        "message" => "✅ Task submitted successfully and is awaiting approval."
    ]);
}
