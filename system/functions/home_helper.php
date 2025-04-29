<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

$user_balance = 0;

// Ensure required globals exist
$user_id = $user_id ?? 0;
$entry_price = $entry_price ?? (float)(getSetting('ticket_price') ?? 1.00);


// Fetch round duration setting (weekly/monthly)
$round_duration = $db->query("SELECT value FROM settings WHERE name = 'round_duration' LIMIT 1")->fetch_assoc()['value'] ?? 'weekly';

// Calculate the next reset time for frontend timer
$nextResetTime = ($round_duration === 'monthly') 
    ? strtotime('first day of next month') 
    : strtotime("next Sunday");

// Keep as timestamp for `date()` & JavaScript
$remainingTime = $nextResetTime;

// Fetch user balance only if logged in
if ($user_id) {
    $user_query = $db->query("SELECT account_balance FROM users WHERE id = '" . (int)$user_id . "'");
    if ($user_query->num_rows > 0) {
        $user_balance = $user_query->fetch_assoc()['account_balance'];
    }
}
    
$use_custom_rewards = (int)getSetting('use_custom_rewards');
$reward_labels = json_decode(getSetting('reward_labels') ?? '[]', true);
$active_round = $db->query("SELECT * FROM rounds WHERE closed = '0' ORDER BY round_id DESC LIMIT 1")->fetch_assoc();

if (!$active_round) {

    // Fetch settings
    $round_duration_query     = $db->query("SELECT value FROM settings WHERE name = 'round_duration' LIMIT 1");
    $round_duration_type_query = $db->query("SELECT value FROM settings WHERE name = 'round_duration_type' LIMIT 1");
    $num_winners_query        = $db->query("SELECT value FROM settings WHERE name = 'number_of_winners' LIMIT 1");
    $starting_prize_query     = $db->query("SELECT value FROM settings WHERE name = 'starting_prize' LIMIT 1");
    $ticket_price_query       = $db->query("SELECT value FROM settings WHERE name = 'ticket_price' LIMIT 1");

    // Set values or defaults
    $round_duration     = ($round_duration_query->num_rows > 0) ? (int)$round_duration_query->fetch_assoc()['value'] : 60;
    $round_duration_type = ($round_duration_type_query->num_rows > 0) ? $round_duration_type_query->fetch_assoc()['value'] : 'minutes';
    $num_winners        = ($num_winners_query->num_rows > 0) ? (int)$num_winners_query->fetch_assoc()['value'] : 10;
    $starting_prize     = ($starting_prize_query->num_rows > 0) ? (float)$starting_prize_query->fetch_assoc()['value'] : 10.00;


    // Calculate precise start and end times for syncing with cron
    $nextRoundMinute = strtotime(date('Y-m-d H:i:00', strtotime('+1 minute')));
    $startDate = $nextRoundMinute + 5;
    switch ($round_duration_type) {
        case 'minutes': $expected_end_date = $startDate + ($round_duration * 60) - 10; break;
        case 'hours':   $expected_end_date = $startDate + ($round_duration * 3600) - 10; break;
        case 'days':    $expected_end_date = $startDate + ($round_duration * 86400) - 10; break;
        case 'weeks':   $expected_end_date = $startDate + ($round_duration * 604800) - 10; break;
        case 'months':  $expected_end_date = strtotime('first day of next month', $startDate) - 10; break;
        default:        $expected_end_date = $startDate + ($round_duration * 60) - 10;
    }

    $next_round_id_query = $db->query("SELECT MAX(round_id) AS last_round FROM rounds");
    $next_round_id = ($next_round_id_query->num_rows > 0) ? (int)$next_round_id_query->fetch_assoc()['last_round'] + 1 : 1;

    // Simulate value construction (but skip insert)
    $values = [];
    for ($position = 1; $position <= $num_winners; $position++) {
        $values[] = "('$next_round_id', '$position', '$starting_prize', '$startDate', '$expected_end_date', '$entry_price')";
    }

    $query = "INSERT INTO rounds (round_id, position, prize, date, expected_end_date, entry_price) VALUES " . implode(',', $values);

    // Don't run this:
    // $db->query($query);

    // Skip fetching active round again — since it wasn't created
    $active_round = null;
}

// Fetch ticket fee from `settings` table
$ticket_fee_query = $db->query("SELECT value FROM settings WHERE name = 'ticket_fee' LIMIT 1");
$ticket_fee = ($ticket_fee_query->num_rows > 0) ? (float)$ticket_fee_query->fetch_assoc()['value'] : 0.00; // Default to $0.00 if not set

// If no active round is found, set expected_end_date to 0
$expected_end_date = $active_round ? (int)$active_round['expected_end_date'] : 0;

// Fetch last 100 winners
$result = $db->query("
    SELECT 
        r.round_id, 
        r.position, 
        r.reward_received, 
        r.use_custom_rewards, 
        r.winning_ticket, 
        r.winner_tickets, 
        r.end_date,
        r.winner_id AS user_id,
        u.username
    FROM rounds r
    LEFT JOIN users u ON u.id = r.winner_id
    WHERE r.closed = '1' 
    ORDER BY r.round_id DESC, r.position ASC 
    LIMIT 100
");

$winners = [];
while ($row = $result->fetch_assoc()) {
    $row['use_custom_rewards'] = (int)$row['use_custom_rewards'];
    $winners[] = $row;
}

function timeAgo($timestamp) {
    $time_diff = time() - $timestamp;

    $days = floor($time_diff / (60 * 60 * 24));
    $hours = floor(($time_diff % (60 * 60 * 24)) / (60 * 60));
    $minutes = floor(($time_diff % (60 * 60)) / 60);
    $seconds = $time_diff % 60;

    return sprintf("%02d d %02d h %02d m %02d s Ago", $days, $hours, $minutes, $seconds);
}

// Fetch the latest active round
        if ($active_round) {
            $round_id = (int)$active_round['round_id'];
        
            // Get users who bought tickets in this round
           $ticket_stats_query = $db->query("
            SELECT u.username, COUNT(t.ticket_id) AS total_tickets, (COUNT(t.ticket_id) * s.value) AS total_spent
            FROM current_round_tickets t
            INNER JOIN users u ON u.id = t.user_id
            CROSS JOIN settings s ON s.name = 'ticket_price'
            WHERE t.round_id = '$round_id'
            GROUP BY u.id
            ORDER BY total_tickets DESC
        ");

        
            $ticket_stats = [];
            while ($row = $ticket_stats_query->fetch_assoc()) {
                $ticket_stats[] = $row;
            }
        }
        
$pastRounds = [];
$query = $db->query("
    SELECT DISTINCT ftl.round_id AS id, r.end_date 
    FROM `fairness_ticket_logs` ftl
    LEFT JOIN `rounds` r ON r.round_id = ftl.round_id
    ORDER BY ftl.round_id DESC LIMIT 250
");

if ($query) {
    while ($row = $query->fetch_assoc()) {
        $pastRounds[] = $row;
    }
}