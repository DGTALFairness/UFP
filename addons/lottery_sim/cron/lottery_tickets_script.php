<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

// Include database connection & functions
require_once(__DIR__ . '/../../../db/database.php');
require_once(__DIR__ . '/../../../system/functions/functions.php');

// Fetch the latest active round
$active_round = $db->query("SELECT round_id FROM rounds WHERE closed = '0' ORDER BY round_id DESC LIMIT 1")->fetch_assoc();
if (!$active_round) {
    exit;
}

$round_id = (int) $active_round['round_id'];

// Fetch ticket price and fee from settings_lottery_addon
$settings = [];
$result = $db->query("SELECT name, value FROM settings_lottery_addon WHERE name IN ('ticket_price', 'ticket_fee')");
while ($row = $result->fetch_assoc()) {
    $settings[$row['name']] = $row['value'];
}
$ticket_price = isset($settings['ticket_price']) ? (float) $settings['ticket_price'] : 1.00;
$ticket_fee   = isset($settings['ticket_fee']) ? (float) $settings['ticket_fee'] : 0.00;

// Fetch payout percentages
$payout_row = $db->query("SELECT value FROM settings WHERE name = 'payouts' LIMIT 1")->fetch_assoc();
$payouts = json_decode($payout_row['value'] ?? '', true);
if (!is_array($payouts) || empty($payouts)) {
    $payouts = [100];
}

// Check if current round is in reward mode
$reward_mode_row = $db->query("SELECT use_custom_rewards FROM rounds WHERE round_id = '$round_id' LIMIT 1")->fetch_assoc();
$isRewardMode = isset($reward_mode_row['use_custom_rewards']) && (int)$reward_mode_row['use_custom_rewards'] === 1;

// Fetch random test users
$test_users = $db->query("SELECT id FROM users WHERE username LIKE 'TestUser%' ORDER BY RAND() LIMIT 5");
if ($test_users->num_rows == 0) {
    exit;
}

// Loop through test users
while ($user = $test_users->fetch_assoc()) {
    $user_id = $user['id'];
    $tickets_to_buy = rand(1, 10);
    $total_cost = $tickets_to_buy * $ticket_price;
    $total_fee = $tickets_to_buy * $ticket_fee;
    $final_contribution = $total_cost - $total_fee;

    // Check user balance
    $balance_query = $db->query("SELECT account_balance FROM users WHERE id = '$user_id'")->fetch_assoc();
    $user_balance = (float) $balance_query['account_balance'];

    if ($user_balance < $total_cost) {
        continue;
    }

    // Deduct balance
    $db->query("UPDATE users SET account_balance = account_balance - '$total_cost' WHERE id = '$user_id'");

    // Insert tickets
    $ticket_entries = [];
    $current_time = time();
    for ($i = 0; $i < $tickets_to_buy; $i++) {
        $ticket_entries[] = "('$user_id', '$round_id', '$current_time')";
    }
    $db->query("INSERT INTO current_round_tickets (user_id, round_id, date) VALUES " . implode(',', $ticket_entries));

    // Add prize only if NOT in reward mode
    if (!$isRewardMode) {
        $total_percentage = array_sum($payouts);
        if ($total_percentage > 0) {
            foreach ($payouts as $position => $percentage) {
                $rewardAmount = round(($final_contribution * $percentage) / 100, 2);
                $db->query("
                    UPDATE rounds 
                    SET prize = CAST(prize AS DECIMAL(10,2)) + $rewardAmount
                    WHERE round_id = '$round_id' AND position = '".($position + 1)."'
                ");

            }
        }
    }

    // Update ticket count
    $db->query("UPDATE rounds SET tickets_purchased = tickets_purchased + '$tickets_to_buy' WHERE round_id = '$round_id'");
}

?>
