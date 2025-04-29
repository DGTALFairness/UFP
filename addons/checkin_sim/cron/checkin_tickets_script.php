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
$addon_check = $db->query("SELECT is_active FROM active_addons WHERE addon_name = 'checkin_sim' LIMIT 1");
if (!$addon_check || (int) $addon_check->fetch_assoc()['is_active'] !== 1) {
    exit;
}

// Fetch the latest active round
$active_round = $db->query("SELECT round_id FROM rounds WHERE closed = '0' ORDER BY round_id DESC LIMIT 1")->fetch_assoc();

if (!$active_round) {
    exit;
}

$round_id = (int) $active_round['round_id'];
$current_time = time();

// Fetch check-in lapse time from settings (default: 1 minute)
$checkin_lapse_time = (int) getSetting('checkin_lapse_time', 1); // in minutes
$cooldown_seconds = $checkin_lapse_time * 60;

// Fetch 5 random test users
$bot_users = $db->query("SELECT id, username FROM users WHERE username LIKE 'TestUser%' ORDER BY RAND() LIMIT 5");

if ($bot_users->num_rows == 0) {
    exit;
}

while ($bot = $bot_users->fetch_assoc()) {
    $user_id = (int)$bot['id'];
    $username = $bot['username'];

    // Fetch last activity for this bot
    $last_activity = null;
    $last_activity_query = $db->prepare("SELECT last_activity FROM users WHERE id = ?");
    $last_activity_query->bind_param("i", $user_id);
    $last_activity_query->execute();
    $last_activity_query->bind_result($last_activity);
    $last_activity_query->fetch();
    $last_activity_query->close();

    if ($last_activity) {
        $time_since_last = $current_time - (int)$last_activity;

        if ($time_since_last < $cooldown_seconds) {
            continue;
        }
    }

    // Update last activity
    $update_activity = $db->prepare("UPDATE users SET last_activity = ? WHERE id = ?");
    $update_activity->bind_param("ii", $current_time, $user_id);
    if (!$update_activity->execute()) {
        $update_activity->close();
        continue;
    }
    $update_activity->close();

    // Insert check-in ticket
    $insert_ticket = $db->prepare("INSERT INTO current_round_tickets (round_id, user_id, date) VALUES (?, ?, ?)");
    $insert_ticket->bind_param("iii", $round_id, $user_id, $current_time);
    $insert_ticket->execute();
    $insert_ticket->close();
}

?>
