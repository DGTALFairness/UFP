<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

require_once(__DIR__ . '/../db/database.php');
require_once(__DIR__ . '/../system/functions/functions.php');
session_start();

// Admin check
$user_id = $_SESSION['user_id'] ?? null;
$user_query = $db->query("SELECT is_admin FROM users WHERE id = '" . (int)$user_id . "'");
$user = $user_query->fetch_assoc();

if (!$user_id || !$user || $user['is_admin'] != 1) {
    die("❌ Access Denied. Admins only.");
}

// Scan /addons/ folder
$addonPath = __DIR__ . '/../addons/';
$foundAddons = [];

foreach (scandir($addonPath) as $file) {
    if ($file === '.' || $file === '..') continue;
    if (is_dir($addonPath . $file)) {
        $foundAddons[] = $file;
    }
}

// Fetch existing addon names
$registeredAddons = [];
$result = $db->query("SELECT addon_name FROM active_addons");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $registeredAddons[] = $row['addon_name'];
    }
}

// Register missing addons
foreach ($foundAddons as $addon) {
    if (!in_array($addon, $registeredAddons)) {
        $addonEscaped = $db->real_escape_string($addon);
        $db->query("INSERT INTO active_addons (addon_name, is_active) VALUES ('$addonEscaped', 0)");
    }
}

$_SESSION['success_message'] = "✅ Addons registered successfully!";
header("Location: ../admin/settings.php");
exit;
