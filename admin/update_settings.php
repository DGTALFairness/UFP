<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

session_start();
require_once('../db/database.php');

// Ensure the user is an admin
$user_id = $_SESSION['user_id'] ?? null;
$user_query = $db->query("SELECT is_admin FROM users WHERE id = '" . (int)$user_id . "'");
$user = $user_query->fetch_assoc();
if (!$user_id || !$user || $user['is_admin'] != 1) {
    die("❌ Access Denied. Admins only.");
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process standard settings
    $starting_prize = (float)$_POST['starting_prize'];
    $round_duration = (int)$_POST['round_duration'];
    $round_duration_type = $_POST['round_duration_type'] ?? 'minutes';
    $reward_prefix = trim($_POST['reward_prefix']);
    $reward_suffix = trim($_POST['reward_suffix']);
    $ticket_middle_text = trim($_POST['ticket_middle_text']);
    $prefix_or_suffix = (int)$_POST['prefix_or_suffix'];

    // Validate ticket_middle_text
    if (!preg_match('/^[a-zA-Z0-9_-]{3,40}$/', $ticket_middle_text) || strpos($ticket_middle_text, ':') !== false || strpos($ticket_middle_text, ';') !== false) {
        $_SESSION['error_message'] = "❌ Invalid Ticket Middle Text.";
        header("Location: settings.php");
        exit;
    }

    // Validate prefix/suffix based on selected mode
    if ($prefix_or_suffix === 1) {
        // Prefix selected, validate and save prefix only
        if (!preg_match('/^[a-zA-Z0-9$#₳¥€£%]{1,12}$/', $reward_prefix)) {
            $_SESSION['error_message'] = "❌ Invalid Reward Prefix.";
            header("Location: settings.php");
            exit;
        }
        $reward_prefix = $db->real_escape_string($reward_prefix);
        $db->query("UPDATE settings SET value = '$reward_prefix' WHERE name = 'reward_prefix'");
    } elseif ($prefix_or_suffix === 2) {
        // Suffix selected, validate and save suffix only
        if (!preg_match('/^[a-zA-Z]{1,12}$/', $reward_suffix)) {
            $_SESSION['error_message'] = "❌ Invalid Reward Suffix.";
            header("Location: settings.php");
            exit;
        }
        $reward_suffix = $db->real_escape_string($reward_suffix);
        $db->query("UPDATE settings SET value = '$reward_suffix' WHERE name = 'reward_suffix'");
    } else {
        $_SESSION['error_message'] = "❌ Invalid option selected for Reward Prefix/Suffix.";
        header("Location: settings.php");
        exit;
    }

    // Sanitize and update remaining settings
    $ticket_middle_text = $db->real_escape_string($ticket_middle_text);
    if (!in_array($round_duration_type, ['minutes', 'hours', 'days', 'weeks', 'months'])) {
        die("❌ Invalid round duration type.");
    }

    $db->query("UPDATE settings SET value = '$starting_prize' WHERE name = 'starting_prize'");
    $db->query("UPDATE settings SET value = '$round_duration' WHERE name = 'round_duration'");
    $db->query("UPDATE settings SET value = '$round_duration_type' WHERE name = 'round_duration_type'");
    $db->query("UPDATE settings SET value = '$ticket_middle_text' WHERE name = 'ticket_middle_text'");
    $db->query("UPDATE settings SET value = '$prefix_or_suffix' WHERE name = 'prefix_or_suffix'");

    $_SESSION['success_message'] = "✅ Settings updated successfully!";
    header("Location: settings.php");
    exit;

} else {
    die("❌ Invalid request.");
}
