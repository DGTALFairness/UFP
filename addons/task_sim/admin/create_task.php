<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

// /addons/task_sim/admin/create_task.php

require_once(__DIR__ . '/../../../db/database.php');
require_once(__DIR__ . '/../../../system/functions/functions.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;

// Admin check
$user = $db->query("SELECT is_admin FROM users WHERE id = '" . (int)$user_id . "'")->fetch_assoc();
if (!$user_id || !$user || $user['is_admin'] != 1) {
    die("❌ Access Denied.");
}

// Collect & sanitize input
$title          = trim($_POST['title'] ?? '');
$description    = trim($_POST['description'] ?? '');
$input_type     = trim($_POST['input_type'] ?? 'text');
$input_name     = trim($_POST['input_name'] ?? '');
$instructions   = trim($_POST['instructions'] ?? '');
$example_format = trim($_POST['example_format'] ?? '');
$is_active      = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

if ($title === '' || $description === '' || $input_name === '') {
    echo "<script>parent.postMessage({ type: 'addon_save_success', message: '❌ Missing required fields.' }, '*');</script>";
    exit;
}

// Set creation time
$created_at = time();

// Prepare insert (no task_id, let AUTO_INCREMENT do its job)
$stmt = $db->prepare("
    INSERT INTO task_addon_jobs 
    (title, description, input_type, input_name, instructions, example_format, is_active, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    echo "<script>parent.postMessage({ type: 'addon_save_success', message: '❌ Prepare failed: " . $db->error . "' }, '*');</script>";
    exit;
}

$stmt->bind_param("ssssssii", $title, $description, $input_type, $input_name, $instructions, $example_format, $is_active, $created_at);
$success = $stmt->execute();
$stmt->close();

if ($success) {
    echo "<script>parent.postMessage({ type: 'addon_save_success', message: '✅ Task \"{$title}\" created successfully.' }, '*');</script>";
} else {
    echo "<script>parent.postMessage({ type: 'addon_save_success', message: '❌ Task creation failed: " . $db->error . "' }, '*');</script>";
}
exit;

