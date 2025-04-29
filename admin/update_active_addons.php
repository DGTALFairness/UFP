<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

require_once(__DIR__ . '/../db/database.php');
require_once(__DIR__ . '/../system/functions/functions.php');
session_start();

// Admin verification
$user_id = $_SESSION['user_id'] ?? null;
$user_query = $db->query("SELECT is_admin FROM users WHERE id = '" . (int)$user_id . "'");
$user = $user_query->fetch_assoc();

if (!$user_id || !$user || $user['is_admin'] != 1) {
    die("❌ Access Denied. Admins only.");
}

// Mark all addons as inactive
$db->query("UPDATE active_addons SET is_active = 0");

// Reactivate selected addons
if (!empty($_POST['active_addons']) && is_array($_POST['active_addons'])) {
    foreach ($_POST['active_addons'] as $addonName) {
        $addonSafe = $db->real_escape_string($addonName);
        $db->query("UPDATE active_addons SET is_active = 1 WHERE addon_name = '$addonSafe'");
    }
}

// Success message and redirect
$_SESSION['success_message'] = "✅ Addon states updated!";
header("Location: ../admin/settings.php");
exit;
