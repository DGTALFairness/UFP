<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

// LOTTERY SIM ADDON - AJAX HANDLERS

if (!isset($action)) return;

global $handled;


if ($action === 'buy_ticket') {
    $handled = true;
    buyTicket();
}

function buyTicket() {
    global $db, $user_id;

    if (!$user_id) {
        safe_json_response([
            "success" => false,
            "message" => '<div class="alert alert-danger">You must be logged in to buy tickets.</div>'
        ]);
    }

    // Step 1: Fetch ticket_price and ticket_fee from settings_lottery_addon table
    $settings = [];
    $addon_settings = $db->query("SELECT name, value FROM settings_lottery_addon WHERE name IN ('ticket_price', 'ticket_fee')");
    while ($row = $addon_settings->fetch_assoc()) {
        $settings[$row['name']] = $row['value'];
    }

    // Fallbacks if not set or invalid
    $ticket_price = isset($settings['ticket_price']) && is_numeric($settings['ticket_price']) ? $settings['ticket_price'] : 11.00;
    $ticket_fee   = isset($settings['ticket_fee']) && is_numeric($settings['ticket_fee']) ? $settings['ticket_fee'] : 22.00;

    // Step 2: Fetch payouts from core settings table
    $core_settings = $db->query("SELECT value FROM settings WHERE name = 'payouts' LIMIT 1")->fetch_assoc();
    $payouts = json_decode($core_settings['value'] ?? '', true);

    if (!is_array($payouts) || empty($payouts)) {
        $payouts = [100];
    }

    $tickets = max(1, min((int)$_POST['tickets_amount'], 200));
    $total_cost = $ticket_price * $tickets;
    $total_fee = $ticket_fee * $tickets;
    $final_pool_contribution = $total_cost - $total_fee;

    $active_round = $db->query("SELECT round_id FROM rounds WHERE closed = 0 ORDER BY round_id DESC LIMIT 1")->fetch_assoc();
    if (!$active_round) {
        safe_json_response(["success" => false, "message" => '<div class="alert alert-danger">No active round found.</div>']);
    }
    $round_id = (int)$active_round['round_id'];

    $round_details = $db->query("SELECT expected_end_date FROM rounds WHERE round_id = '$round_id' LIMIT 1")->fetch_assoc();
    $expected_end_date = (int) $round_details['expected_end_date'] ?? 0;
    if (time() > $expected_end_date) {
        safe_json_response([
            "success" => false,
            "message" => '<div class="alert alert-danger">⏳ This round has ended and is being processed. Please wait for the next round.</div>'
        ]);
    }

    $user_balance_query = $db->query("SELECT account_balance FROM users WHERE id = '$user_id' LIMIT 1");
    $user_balance = $user_balance_query->fetch_assoc()['account_balance'] ?? 0;

    if ($user_balance < $total_cost) {
        safe_json_response([
            "success" => false,
            "message" => '<div class="alert alert-danger">⛔ You do not have enough balance to buy this many tickets.</div>'
        ]);
    }

    $db->query("UPDATE users SET account_balance = account_balance - '$total_cost' WHERE id = '$user_id'");

    $ticket_entries = [];
    for ($i = 0; $i < $tickets; $i++) {
        $ticket_entries[] = "('$user_id', '$round_id', '" . time() . "')";
    }
    $db->query("INSERT INTO current_round_tickets (user_id, round_id, date) VALUES " . implode(',', $ticket_entries));

    // Check if round is in reward mode
    $check_reward_mode = $db->query("SELECT use_custom_rewards FROM rounds WHERE round_id = '$round_id' LIMIT 1")->fetch_assoc();
    $isRewardMode = isset($check_reward_mode['use_custom_rewards']) && (int)$check_reward_mode['use_custom_rewards'] === 1;
    
    // Only update prize if NOT in reward mode
    if (!$isRewardMode) {
        $total_percentage = array_sum($payouts);
        if ($total_percentage > 0) {
            foreach ($payouts as $position => $percentage) {
                $rewardAmount = number_format(($final_pool_contribution * $percentage) / 100, 2, '.', '');
                $db->query("
                    UPDATE rounds 
                    SET prize = FORMAT(CAST(prize AS DECIMAL(10,2)) + $rewardAmount, 2)
                    WHERE round_id = '$round_id' AND position = '".($position + 1)."'
                ");

            }
        }
    }


    $db->query("UPDATE rounds SET tickets_purchased = tickets_purchased + '$tickets' WHERE round_id = '$round_id'");

    $updated_round = $db->query("SELECT SUM(prize) AS total_prize, MAX(tickets_purchased) AS total_tickets FROM rounds WHERE round_id = '$round_id'")->fetch_assoc();
    $new_prize = number_format($updated_round['total_prize'], 2);
    $new_tickets = number_format($updated_round['total_tickets']);

    safe_json_response([
        "success" => true,
        "message" => sprintf(
            '<div class="alert alert-success">You have successfully purchased %d ticket(s) for <b>$%.2f</b>! <br> Fee Deducted: <b>$%.2f</b></div>',
            $tickets,
            $total_cost,
            $total_fee
        ),
        "new_prize" => $new_prize,
        "new_tickets" => $new_tickets
    ]);
}
