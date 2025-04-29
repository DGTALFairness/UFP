<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

// /addons/task_sim/admin/manage_task.php
require_once(__DIR__ . '/../../../db/database.php');
require_once(__DIR__ . '/../../../system/functions/functions.php');

if (session_status() === PHP_SESSION_NONE) session_start();

$user_id = $_SESSION['user_id'] ?? null;

// Admin check
$user = $db->query("SELECT is_admin FROM users WHERE id = '" . (int)$user_id . "'")->fetch_assoc();
if (!$user_id || !$user || $user['is_admin'] != 1) {
    die("❌ Access Denied.");
}

// Incoming vars
$action        = $_POST['action'] ?? '';
$submission_id = (int)($_POST['submission_id'] ?? 0);
$task_id       = $_POST['task_id'] ?? '';
$target_user   = (int)($_POST['user_id'] ?? 0);

// Approve or Reject submission
if (in_array($action, ['approve', 'reject']) && $submission_id > 0) {

    if ($action === 'approve') {
        // Get data from pending
        $pending = $db->query("SELECT * FROM task_addon_pending WHERE id = '$submission_id' LIMIT 1");
        if ($pending && $pending->num_rows > 0) {
            $row = $pending->fetch_assoc();

            $now = time();
            $submitted_at = (int)$row['submitted_at'];
            
            // Check if the round is still active before approving
            $round_id = (int)$row['round_id'];
            $round = $db->query("SELECT closed FROM rounds WHERE round_id = '$round_id' LIMIT 1")->fetch_assoc();
            
            if (!$round || (int)$round['closed'] === 1) {
                echo "<script>parent.postMessage({ type: 'addon_save_success', message: '⚠️ Cannot approve task — round has already ended.' }, '*');</script>";
                exit;
            }

            // Insert into task_addon_done — always insert a new row
            $stmt = $db->prepare("
                INSERT INTO task_addon_done (task_id, round_id, user_id, submission_data, submitted_at, approved_at, approved_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param(
                "iiisiii",
                $row['task_id'],
                $row['round_id'],
                $row['user_id'],
                $row['submission_data'],
                $row['submitted_at'],
                $now,
                $user_id
            );

            $stmt->execute();
            $stmt->close();

            // Delete from pending
            $db->query("DELETE FROM task_addon_pending WHERE id = '$submission_id' LIMIT 1");

           // Add ticket
                $roundQ = $db->query("SELECT round_id FROM rounds WHERE closed = 0 ORDER BY round_id DESC LIMIT 1");
                if ($roundQ && $roundQ->num_rows > 0) {
                    $round_id = (int)$roundQ->fetch_assoc()['round_id'];
                
                    $insert = $db->prepare("INSERT INTO current_round_tickets (round_id, user_id, date) VALUES (?, ?, ?)");
                    $insert->bind_param("iii", $round_id, $row['user_id'], $now);
                    $insert->execute();
                    $insert->close();
                    
                    // Increment total_tasks in rounds
                    $db->query("UPDATE rounds SET total_tasks = total_tasks + 1 WHERE round_id = '$round_id'");

                    
                }
                
                echo "<script>
                  parent.postMessage({
                    type: 'addon_save_success',
                    message: '✅ Submission approved and ticket awarded.',
                    tab: 'pending'
                  }, '*');
                </script>";
                exit;
           
        } else {
            echo "<script>parent.postMessage({ type: 'addon_save_success', message: '❌ Could not find submission.' }, '*');</script>";
            exit;
        }
    }

    if ($action === 'reject') {
        $db->query("DELETE FROM task_addon_pending WHERE id = '$submission_id' LIMIT 1");
        echo "<script>parent.postMessage({ type: 'addon_save_success', message: '❌ Submission rejected and removed.' }, '*');</script>";
        exit;
    }
}

// Task toggle / delete
if (in_array($action, ['toggle', 'delete']) && !empty($task_id)) {
    if ($action === 'toggle') {
        $res = $db->query("SELECT is_active FROM task_addon_jobs WHERE task_id = '$task_id' LIMIT 1")->fetch_assoc();
        if ($res) {
            $newStatus = $res['is_active'] ? 0 : 1;
            $db->query("UPDATE task_addon_jobs SET is_active = '$newStatus' WHERE task_id = '$task_id'");
            $msg = $newStatus ? '✅ Task activated.' : '🔒 Task deactivated.';
            echo "<script>parent.postMessage({ type: 'addon_save_success', message: '$msg' }, '*');</script>";
            exit;
        }
    }

    if ($action === 'delete') {
        $db->query("DELETE FROM task_addon_jobs WHERE task_id = '$task_id'");
        $db->query("DELETE FROM task_addon_pending WHERE task_id = '$task_id'");
        echo "<script>parent.postMessage({ type: 'addon_save_success', message: '🗑 Task deleted successfully.' }, '*');</script>";
        exit;
    }
}

// Fallback
echo "<script>parent.postMessage({ type: 'addon_save_success', message: '⚠️ No action taken.' }, '*');</script>";
exit;
