<?php 

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

$user_id = $_SESSION['user_id'] ?? null;
$user = null;
$user_balance = 0;
$user_tickets = 0;
$ticket_price = 1.00;
$is_admin = 0;
$total_wins = 0;

$total_tickets = 0;
$total_rounds = 0;
$total_winning_rounds = 0;
$total_winnings = 0;
$user_current_tickets = 0;
$current_round = null;
$ended_rounds = [];
$winning_rounds = [];

// Correctly fetching prefix_or_suffix from the settings table
$prefix_or_suffix_query = $db->query("SELECT value FROM settings WHERE name = 'prefix_or_suffix' LIMIT 1");
$prefix_or_suffix_result = $prefix_or_suffix_query->fetch_assoc();
$prefix_or_suffix = $prefix_or_suffix_result['value'] ?? 2;

// Fetch reward prefix
$reward_prefix_query = $db->query("SELECT value FROM settings WHERE name = 'reward_prefix' LIMIT 1");
$reward_prefix_result = $reward_prefix_query->fetch_assoc();
$reward_prefix = $reward_prefix_result['value'] ?? '$';

// Fetch reward suffix
$reward_suffix_query = $db->query("SELECT value FROM settings WHERE name = 'reward_suffix' LIMIT 1");
$reward_suffix_result = $reward_suffix_query->fetch_assoc();
$reward_suffix = $reward_suffix_result['value'] ?? 'Points';



// Fetch the ticket price from settings table
$ticket_price_query = $db->query("SELECT value FROM settings WHERE name = 'ticket_price' LIMIT 1");
if ($ticket_price_query->num_rows > 0) {
    $ticket_price = (float)$ticket_price_query->fetch_assoc()['value'];
} else {
    $ticket_price = 1.00;
}

if ($user_id) {
    $user_query = $db->query("SELECT id, username, account_balance, is_admin FROM users WHERE id = '" . (int)$user_id . "'");
    $user = $user_query->fetch_assoc();
    $user_balance = $user['account_balance'] ?? 0;
    $is_admin = $user['is_admin'] ?? 0;

    // Fetch current round details
    $current_round_query = $db->query("SELECT round_id, expected_end_date FROM rounds WHERE closed = 0 LIMIT 1");
    $current_round = $current_round_query->fetch_assoc() ?? null;

   // Fetch user's tickets
    $user_current_tickets_query = $db->query("
        SELECT COUNT(*) as ticket_count 
        FROM current_round_tickets 
        WHERE user_id = '" . (int)$user_id . "'
    ");
    $user_tickets = $user_current_tickets_query->fetch_assoc()['ticket_count'] ?? 0;


   $global_stats_query = $db->query("
    SELECT 
        COUNT(*) as total_tickets, 
        COUNT(DISTINCT ct.round_id) as total_rounds,
        (SELECT COUNT(DISTINCT round_id) FROM winning_round_tickets WHERE user_id = ct.user_id) AS total_winning_rounds
    FROM completed_round_tickets ct
    WHERE ct.user_id = '" . (int)$user_id . "'
    ");
    
    // Process fetched global stats
    $global_stats = $global_stats_query->fetch_assoc();
    $total_tickets = $global_stats['total_tickets'] ?? 0;
    $total_rounds = $global_stats['total_rounds'] ?? 0;
    $total_winning_rounds = $global_stats['total_winning_rounds'] ?? 0;

    // Fetch total winnings from `winning_round_tickets`
    $global_winnings_query = $db->query("
        SELECT SUM(reward_amount) as total_winnings 
        FROM winning_round_tickets 
        WHERE user_id = '" . (int)$user_id . "'
    ");
    $global_winnings = $global_winnings_query->fetch_assoc();
    $global_total_winnings = $global_winnings['total_winnings'] ?? 0;

    // Fetch user's winning statistics
    $winning_stats_query = $db->query("
        SELECT COUNT(DISTINCT round_id) as total_winning_rounds, SUM(reward_amount) as total_winnings 
        FROM winning_round_tickets 
        WHERE user_id = '" . (int)$user_id . "'
    ");
    $winning_stats = $winning_stats_query->fetch_assoc();
    $total_winning_rounds = $winning_stats['total_winning_rounds'] ?? 0;
    $total_winnings = $winning_stats['total_winnings'] ?? 0;
    
   // Fetch user's ended round participation with correct ticket IDs
    $ended_rounds_query = $db->query("
    SELECT 
        r.round_id, 
        COUNT(DISTINCT t.ticket_id) AS tickets_bought,  -- Use ticket_id, NOT id!
        GROUP_CONCAT(DISTINCT t.ticket_id ORDER BY t.ticket_id ASC SEPARATOR ', ') AS ticket_ids
    FROM completed_round_tickets t
    JOIN rounds r ON t.round_id = r.round_id
    WHERE t.user_id = '" . (int)$user_id . "' 
    AND r.closed = 1
    GROUP BY r.round_id
    ORDER BY r.round_id DESC
");

while ($row = $ended_rounds_query->fetch_assoc()) {
    $ended_rounds[] = $row;
}

    $winning_rounds_query = $db->query("
    SELECT 
        w.round_id, 
        COUNT(DISTINCT w.ticket_id) AS winning_tickets, 
        SUM(w.reward_amount) AS total_prizes, 
        GROUP_CONCAT(DISTINCT w.ticket_id ORDER BY w.ticket_id ASC) AS ticket_ids,
        MIN(r.use_custom_rewards) AS use_custom_rewards,
        MIN(w.position) AS position
    FROM winning_round_tickets w
    INNER JOIN rounds r ON w.round_id = r.round_id
    WHERE w.user_id = '" . (int)$user_id . "'
    GROUP BY w.round_id
    ORDER BY w.round_id DESC
");

while ($row = $winning_rounds_query->fetch_assoc()) {
    $winning_rounds[] = $row;
}

  }