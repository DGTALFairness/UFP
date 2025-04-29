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

// Sanitize input
$use_custom = (int) ($_POST['use_custom_rewards'] ?? 0);
$num_winners = (int) ($_POST['number_of_winners'] ?? 0);
$payouts_raw = trim($_POST['payouts'] ?? '');
$reward_labels_raw = trim($_POST['reward_labels'] ?? '');

// Basic validation
if (
    $num_winners < 1 ||
    ($use_custom !== 1 && empty($payouts_raw)) ||
    ($use_custom === 1 && empty($reward_labels_raw))
) {
    $_SESSION['error_message'] = "❌ Invalid input.";
    header("Location: settings.php");
    exit;
}

// Handle reward mode
if ($use_custom === 1) {
    $reward_labels_array = json_decode($reward_labels_raw, true);
    if (!is_array($reward_labels_array)) {
        $_SESSION['error_message'] = "❌ Invalid JSON format for reward labels.";
        header("Location: settings.php");
        exit;
    }

    // Save reward_labels
    $stmt = $db->prepare("UPDATE settings SET value = ? WHERE name = 'reward_labels'");
    $stmt->bind_param("s", json_encode($reward_labels_array, JSON_UNESCAPED_UNICODE));
    $stmt->execute();

    // Save reward_number_of_winners
    $db->query("UPDATE settings SET value = '{$num_winners}' WHERE name = 'reward_number_of_winners'");

} else {
    // Handle payout mode
    $payouts_array = json_decode($payouts_raw, true);
    if (!is_array($payouts_array)) {
        $_SESSION['error_message'] = "❌ Invalid JSON format for payouts.";
        header("Location: settings.php");
        exit;
    }

    $total = array_sum($payouts_array);
    if (abs($total - 100) > 0.01) {
        $_SESSION['error_message'] = "❌ Total payout must equal 100%. You entered: {$total}%";
        header("Location: settings.php");
        exit;
    }

    // Save payouts
    $stmt = $db->prepare("UPDATE settings SET value = ? WHERE name = 'payouts'");
    $stmt->bind_param("s", json_encode($payouts_array));
    $stmt->execute();

    // Save number_of_winners
    $db->query("UPDATE settings SET value = '{$num_winners}' WHERE name = 'number_of_winners'");
}

// Always update the toggle for reward mode
$db->query("UPDATE settings SET value = '{$use_custom}' WHERE name = 'use_custom_rewards'");

// Done!
$_SESSION['success_message'] = "✅ Payout/Reward structure updated!";
header("Location: settings.php");
exit;
?>
