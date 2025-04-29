<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

session_start();
require_once(__DIR__ . '/../../../db/database.php');

// Ensure the user is an admin
$user_id = $_SESSION['user_id'] ?? null;
$user_query = $db->query("SELECT is_admin FROM users WHERE id = '" . (int)$user_id . "'");
$user = $user_query->fetch_assoc();
if (!$user_id || !$user || $user['is_admin'] != 1) {
    die("❌ Access Denied. Admins only.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $checkin_lapse_time = isset($_POST['checkin_lapse_time']) ? (int)$_POST['checkin_lapse_time'] : 1440;

    if ($checkin_lapse_time < 1 || $checkin_lapse_time > 10080) { // Limit to 7 days max
        echo "<script>
            parent.postMessage({ type: 'addon_save_error', message: '❌ Invalid lapse time. Enter a value between 1 and 10080 minutes.' }, '*');
            window.location.href = 'settings.php';
        </script>";
        exit;
    }

    // Update the checkin lapse time
    $stmt = $db->prepare("INSERT INTO settings_checkin_addon (`name`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?");
    $stmt->bind_param("sis", $name, $value, $value);

    $name = 'checkin_lapse_time';
    $value = $checkin_lapse_time;

    if ($stmt->execute()) {
        echo "<script>
            parent.postMessage({ type: 'addon_save_success', message: '✅ Check-in lapse time updated successfully!' }, '*');
            window.location.href = 'settings.php';
        </script>";
    } else {
        echo "<script>
            parent.postMessage({ type: 'addon_save_error', message: '❌ Failed to update setting.' }, '*');
            window.location.href = 'settings.php';
        </script>";
    }

    $stmt->close();
    exit;
} else {
    die("❌ Invalid request.");
}
